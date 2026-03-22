<?php

namespace App\Http\Controllers;

use App\Models\PagoTransporte;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use App\Models\EstudianteRuta;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PagoTransporteController extends Controller
{
    public function index(Request $request)
    {
        $query = PagoTransporte::with(['estudiante.curso', 'estudiante.rutaTransporte.ruta.asignaciones' => function($q) {
            $q->where('asig_estado', 1)->with('vehiculo');
        }]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('tpago_fecha_pago', [$request->fecha_inicio, $request->fecha_fin]);
        }
        if ($request->filled('estado')) {
            $query->where('tpago_estado', $request->estado);
        }
        if ($request->filled('estudiante')) {
            $query->where('est_codigo', $request->estudiante);
        }
        if ($request->filled('ruta')) {
            $query->whereHas('estudiante.rutaTransporte', function($q) use ($request) {
                $q->where('ruta_codigo', $request->ruta);
            });
        }

        $pagos = $query->orderBy('tpago_fecha_registro', 'desc')->paginate(30);

        // Agrupar por tpago_codigo para vista agrupada
        $codigosEnPagina = $pagos->pluck('tpago_codigo')->unique()->values();
        $pagosRecibo = [];
        $codigosConjuntos = [];

        if ($codigosEnPagina->isNotEmpty()) {
            $todos = PagoTransporte::with('estudiante.curso')
                ->whereIn('tpago_codigo', $codigosEnPagina)
                ->orderBy('est_codigo')
                ->get();

            foreach ($todos->groupBy('tpago_codigo') as $codigo => $grupo) {
                $items = $grupo->map(function($p) {
                    return [
                        'tpago_id' => $p->tpago_id,
                        'estudiante' => ($p->estudiante->est_nombres ?? '') . ' ' . ($p->estudiante->est_apellidos ?? ''),
                        'curso' => $p->estudiante->curso->cur_nombre ?? 'N/A',
                        'tipo' => $p->tpago_tipo,
                        'monto' => $p->tpago_monto,
                        'fecha_inicio' => $p->tpago_fecha_inicio,
                        'fecha_fin' => $p->tpago_fecha_fin,
                        'estado' => $p->tpago_estado,
                    ];
                })->values()->toArray();
                $pagosRecibo[$codigo] = $items;
                if (count($items) > 1) {
                    $codigosConjuntos[] = $codigo;
                }
            }
        }

        $estudiantes = Estudiante::visible()->get();

        return view('transporte.pagos.index', compact('pagos', 'estudiantes', 'pagosRecibo', 'codigosConjuntos'));
    }

    public function create()
    {
        $year = date('Y');

        // Padres que tienen hijos visibles (no requiere ruta asignada, primero pagan)
        $padres = PadreFamilia::activo()
            ->whereHas('estudiantes', function($q) {
                $q->where('est_visible', 1);
            })
            ->orderBy('pfam_nombres')
            ->get();

        // Todos los estudiantes visibles (pueden o no tener ruta asignada)
        $estudiantes = Estudiante::visible()
            ->with(['curso', 'padres', 'rutaTransporte.ruta.asignaciones' => function($q) {
                $q->where('asig_estado', 1)->with(['vehiculo', 'chofer']);
            }])
            ->get();

        $gestion = $year;
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];

        $estudiantesData = [];
        foreach ($estudiantes as $est) {
            $ruta = $est->rutaTransporte;
            $rutaNombre = $ruta && $ruta->ruta ? $ruta->ruta->ruta_nombre : 'Sin asignar';
            $asignacion = $ruta && $ruta->ruta ? $ruta->ruta->asignaciones->where('asig_estado', 1)->first() : null;
            $vehiculo = $asignacion && $asignacion->vehiculo ? 'Bus ' . ($asignacion->vehiculo->veh_numero_bus ?? $asignacion->vehiculo->veh_placa) : '-';
            $chofer = $asignacion && $asignacion->chofer ? $asignacion->chofer->chof_nombres . ' ' . $asignacion->chofer->chof_apellidos : '-';

            // Pagos activos del año
            $pagos = PagoTransporte::where('est_codigo', $est->est_codigo)
                ->whereYear('tpago_fecha_pago', $gestion)
                ->where('tpago_estado', '!=', 'cancelado')
                ->orderBy('tpago_fecha_inicio')
                ->get();

            $mesesPagados = [];
            $totalPagado = 0;
            $ultimaVigencia = null;

            foreach ($pagos as $pago) {
                $totalPagado += $pago->tpago_monto;
                $inicio = Carbon::parse($pago->tpago_fecha_inicio);
                $fin = Carbon::parse($pago->tpago_fecha_fin);
                $current = $inicio->copy()->startOfMonth();
                $finMes = $fin->copy()->startOfMonth();
                while ($current < $finMes) {
                    $m = (int)$current->format('n');
                    if ($m >= 2 && $m <= 11 && !in_array($m, $mesesPagados)) {
                        $mesesPagados[] = $m;
                    }
                    $current->addMonth();
                }
                if (!$ultimaVigencia || $fin > $ultimaVigencia) {
                    $ultimaVigencia = $fin;
                }
            }
            sort($mesesPagados);

            $historial = $pagos->map(function($p) {
                return [
                    'codigo' => $p->tpago_codigo,
                    'fecha' => $p->tpago_fecha_pago,
                    'tipo' => $p->tpago_tipo,
                    'monto' => (float)$p->tpago_monto,
                    'vigencia' => $p->tpago_fecha_inicio . ' al ' . $p->tpago_fecha_fin,
                    'estado' => $p->tpago_estado,
                ];
            })->values()->toArray();

            $estudiantesData[$est->est_codigo] = [
                'est_codigo' => $est->est_codigo,
                'nombre' => $est->est_nombres . ' ' . $est->est_apellidos,
                'curso' => $est->curso->cur_nombre ?? 'N/A',
                'ruta' => $rutaNombre,
                'vehiculo' => $vehiculo,
                'chofer' => $chofer,
                'meses_pagados' => $mesesPagados,
                'total_pagado' => $totalPagado,
                'ultima_vigencia' => $ultimaVigencia ? $ultimaVigencia->format('Y-m-d') : null,
                'historial' => $historial,
                'padres' => $est->padres->pluck('pfam_codigo')->toArray(),
            ];
        }

        return view('transporte.pagos.create', compact('padres', 'estudiantesData'));
    }

    public function historialPagos($est_codigo)
    {
        $gestionActual = date('Y');
        $pagos = PagoTransporte::where('est_codigo', $est_codigo)
            ->whereYear('tpago_fecha_pago', $gestionActual)
            ->where('tpago_estado', '!=', 'cancelado')
            ->orderBy('tpago_fecha_inicio')
            ->get();

        $mesesPagados = 0;
        $ultimaVigencia = null;

        foreach ($pagos as $pago) {
            $inicio = Carbon::parse($pago->tpago_fecha_inicio);
            $fin = Carbon::parse($pago->tpago_fecha_fin);
            $current = $inicio->copy();
            while ($current < $fin) {
                $mesesPagados++;
                $current->addMonth();
            }
            if (!$ultimaVigencia || $fin > $ultimaVigencia) {
                $ultimaVigencia = $fin;
            }
        }

        return response()->json([
            'pagos' => $pagos,
            'mesesPagados' => $mesesPagados,
            'ultimaVigencia' => $ultimaVigencia ? $ultimaVigencia->format('d-m-Y') : null
        ]);
    }

    public function store(Request $request)
    {
        $estudiantesInput = $request->input('estudiantes', []);
        if (empty($estudiantesInput)) {
            return back()->withErrors(['error' => 'Debe seleccionar al menos un estudiante.'])->withInput();
        }

        // Un solo código para toda la transacción (solo formato TPAGO00000)
        $ultimo = PagoTransporte::whereRaw("tpago_codigo REGEXP '^TPAGO[0-9]{5}$'")->max('tpago_codigo');
        $numSiguiente = $ultimo ? intval(substr($ultimo, 5)) + 1 : 1;
        $codigoRecibo = 'TPAGO' . str_pad($numSiguiente, 5, '0', STR_PAD_LEFT);

        $tipos = [
            1 => 'mensual', 2 => 'bimestral', 3 => 'trimestral',
            4 => 'cuatrimestral', 5 => 'quinquemestral', 6 => 'semestral',
            7 => '7 meses', 8 => '8 meses', 9 => '9 meses', 10 => 'anual'
        ];

        foreach ($estudiantesInput as $estData) {
            $estCodigo = $estData['est_codigo'];
            $mesesPagar = intval($estData['meses_pagar']);
            $montoMensual = floatval($estData['monto_mensual']);
            $montoTotal = $montoMensual * $mesesPagar;
            $tpagoTipo = $tipos[$mesesPagar] ?? $mesesPagar . ' meses';

            $ultimaVigencia = $estData['ultima_vigencia'] ?? null;
            if ($ultimaVigencia) {
                $fechaInicio = Carbon::parse($ultimaVigencia)->addDay();
            } else {
                $fechaInicio = Carbon::parse($request->tpago_fecha_pago ?? now());
            }

            $fechaFin = $this->calcularFechaFinHabil($fechaInicio, $mesesPagar);

            PagoTransporte::create([
                'tpago_codigo' => $codigoRecibo,
                'est_codigo' => $estCodigo,
                'tpago_tipo' => $tpagoTipo,
                'tpago_monto' => $montoTotal,
                'tpago_fecha_pago' => $request->tpago_fecha_pago ?? now()->toDateString(),
                'tpago_fecha_inicio' => $fechaInicio,
                'tpago_fecha_fin' => $fechaFin,
                'tpago_usuario_registro' => auth()->user()->us_codigo
            ]);
        }

        return redirect()->route('pagos-transporte.index')->with('success', 'Pago(s) registrado(s) exitosamente');
    }

    private function calcularFechaFinHabil($fechaInicio, $meses)
    {
        $fecha = $fechaInicio->copy()->addMonths($meses);
        if ($fecha->isSaturday()) $fecha->subDay();
        if ($fecha->isSunday()) $fecha->subDays(2);
        return $fecha;
    }

    public function edit($id)
    {
        $pago = PagoTransporte::findOrFail($id);
        $estudiantes = Estudiante::visible()->get();
        return view('transporte.pagos.edit', compact('pago', 'estudiantes'));
    }

    public function update(Request $request, $id)
    {
        $pago = PagoTransporte::findOrFail($id);

        $rules = [
            'tpago_estado' => 'required|in:vigente,vencido,cancelado',
            'est_codigo' => 'nullable|exists:colegio_estudiantes,est_codigo'
        ];

        if ($request->filled('tpago_monto') && !$pago->tpago_monto_modificado) {
            $rules['tpago_monto'] = 'numeric|min:0';
        }

        $request->validate($rules);

        $data = [
            'tpago_estado' => $request->tpago_estado,
            'est_codigo' => $request->est_codigo ?? $pago->est_codigo
        ];

        if ($request->filled('tpago_monto') && !$pago->tpago_monto_modificado) {
            $data['tpago_monto'] = $request->tpago_monto;
            $data['tpago_monto_modificado'] = 1;
        }

        $pago->update($data);
        return redirect()->route('pagos-transporte.index')->with('success', 'Pago actualizado');
    }

    public function anular($id)
    {
        $pago = PagoTransporte::findOrFail($id);
        PagoTransporte::where('tpago_codigo', $pago->tpago_codigo)->update(['tpago_estado' => 'cancelado']);
        return response()->json(['success' => true, 'message' => 'Pago(s) anulado(s)']);
    }

    public function destroy($id)
    {
        $pago = PagoTransporte::findOrFail($id);
        $pago->update(['tpago_estado' => 'cancelado']);
        return redirect()->route('pagos-transporte.index')->with('success', 'Pago cancelado');
    }

    public function reporteIngresos(Request $request)
    {
        $mesInicio = $request->mes_inicio ?? 2;
        $mesFin = $request->mes_fin ?? 11;
        $gestion = $request->gestion ?? date('Y');

        $rutas = \App\Models\Ruta::where('ruta_estado', 1)
            ->with(['asignaciones' => function($q) {
                $q->where('asig_estado', 1)->with(['vehiculo', 'chofer']);
            }])
            ->get();

        $datosReporte = [];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        foreach ($rutas as $ruta) {
            $asignacion = $ruta->asignaciones->first();
            if (!$asignacion) continue;

            $vehiculo = $asignacion->vehiculo;
            $chofer = $asignacion->chofer;

            $estudiantesRuta = EstudianteRuta::where('ruta_codigo', $ruta->ruta_codigo)
                ->where('ter_estado', 1)
                ->pluck('est_codigo');

            if ($estudiantesRuta->isEmpty()) continue;

            $mesesPagados = array_fill($mesInicio, $mesFin - $mesInicio + 1, 0);
            $totalRuta = 0;

            foreach ($estudiantesRuta as $estCodigo) {
                $pagos = PagoTransporte::where('est_codigo', $estCodigo)
                    ->whereYear('tpago_fecha_inicio', $gestion)
                    ->where('tpago_estado', '!=', 'cancelado')
                    ->get();

                foreach ($pagos as $pago) {
                    $inicio = Carbon::parse($pago->tpago_fecha_inicio);
                    $fin = Carbon::parse($pago->tpago_fecha_fin);
                    $mesesDuracion = max(1, $inicio->diffInMonths($fin));
                    $montoPorMes = $pago->tpago_monto / $mesesDuracion;

                    $current = $inicio->copy()->startOfMonth();
                    $finMes = $fin->copy()->startOfMonth();

                    while ($current < $finMes) {
                        $mes = (int)$current->format('n');
                        if ($mes >= $mesInicio && $mes <= $mesFin) {
                            $mesesPagados[$mes] += $montoPorMes;
                        }
                        $current->addMonth();
                    }
                    $totalRuta += $pago->tpago_monto;
                }
            }

            $datosReporte[] = [
                'ruta' => $ruta->ruta_nombre,
                'bus_numero' => $vehiculo && $vehiculo->veh_numero_bus ? $vehiculo->veh_numero_bus : '',
                'chofer' => $chofer ? $chofer->chof_nombres . ' ' . $chofer->chof_apellidos : '',
                'meses' => $mesesPagados,
                'total' => $totalRuta
            ];
        }

        return view('transporte.pagos.reporte-ingresos', compact('datosReporte', 'mesInicio', 'mesFin', 'gestion', 'meses'));
    }
}
