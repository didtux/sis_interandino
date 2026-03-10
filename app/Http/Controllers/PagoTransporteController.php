<?php

namespace App\Http\Controllers;

use App\Models\PagoTransporte;
use App\Models\Estudiante;
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

        if ($request->filled('bus')) {
            $query->whereHas('estudiante.rutaTransporte.ruta.asignaciones', function($q) use ($request) {
                $q->where('veh_codigo', $request->bus)->where('asig_estado', 1);
            });
        }

        if ($request->filled('ruta')) {
            $query->whereHas('estudiante.rutaTransporte', function($q) use ($request) {
                $q->where('ruta_codigo', $request->ruta);
            });
        }

        $pagos = $query->orderBy('tpago_fecha_registro', 'desc')->get();
        return view('transporte.pagos.index', compact('pagos'));
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->get();
        return view('transporte.pagos.create', compact('estudiantes'));
    }

    public function historialPagos($est_codigo)
    {
        $gestionActual = date('Y');
        $pagos = PagoTransporte::where('est_codigo', $est_codigo)
            ->whereYear('tpago_fecha_pago', $gestionActual)
            ->orderBy('tpago_fecha_inicio')
            ->get();
        
        $mesesPagados = 0;
        $ultimaVigencia = null;
        
        foreach($pagos as $pago) {
            $inicio = Carbon::parse($pago->tpago_fecha_inicio);
            $fin = Carbon::parse($pago->tpago_fecha_fin);
            $meses = 0;
            $current = $inicio->copy();
            while ($current < $fin) {
                $meses++;
                $current->addMonth();
            }
            $mesesPagados += $meses;
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
        
        foreach($rutas as $ruta) {
            $asignacion = $ruta->asignaciones->first();
            if (!$asignacion) continue;
            
            $vehiculo = $asignacion->vehiculo;
            $chofer = $asignacion->chofer;
            
            $estudiantesRuta = \App\Models\EstudianteRuta::where('ruta_codigo', $ruta->ruta_codigo)
                ->where('ter_estado', 1)
                ->pluck('est_codigo');
            
            if ($estudiantesRuta->isEmpty()) continue;
            
            $mesesPagados = array_fill($mesInicio, $mesFin - $mesInicio + 1, 0);
            $totalRuta = 0;
            
            foreach($estudiantesRuta as $estCodigo) {
                $pagos = PagoTransporte::where('est_codigo', $estCodigo)
                    ->whereYear('tpago_fecha_inicio', $gestion)
                    ->where('tpago_estado', '!=', 'cancelado')
                    ->get();
                
                foreach($pagos as $pago) {
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

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required',
            'tpago_tipo' => 'required|in:mensual,trimestral,semestral,anual',
            'tpago_monto' => 'required|numeric|min:0',
            'tpago_fecha_pago' => 'required|date',
            'meses_pagar' => 'required|integer|min:1'
        ]);

        // Si hay vigencia previa, continuar desde ahí
        if ($request->filled('ultima_vigencia')) {
            $fechaInicio = Carbon::parse($request->ultima_vigencia)->addDay();
        } else {
            $fechaInicio = Carbon::parse($request->tpago_fecha_pago);
        }
        
        $fechaFin = $this->calcularFechaFinHabil($fechaInicio, $request->meses_pagar);
        
        // Calcular monto total (monto mensual * cantidad de meses)
        $montoTotal = $request->tpago_monto * $request->meses_pagar;

        PagoTransporte::create([
            'tpago_codigo' => 'TPAGO' . str_pad(PagoTransporte::max('tpago_id') + 1, 5, '0', STR_PAD_LEFT),
            'est_codigo' => $request->est_codigo,
            'tpago_tipo' => $request->tpago_tipo,
            'tpago_monto' => $montoTotal,
            'tpago_fecha_pago' => $request->tpago_fecha_pago,
            'tpago_fecha_inicio' => $fechaInicio,
            'tpago_fecha_fin' => $fechaFin,
            'tpago_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('pagos-transporte.index')->with('success', 'Pago registrado');
    }

    private function calcularFechaFinHabil($fechaInicio, $meses)
    {
        $fecha = $fechaInicio->copy()->addMonths($meses);
        
        // Si cae en sábado, mover a viernes
        if ($fecha->isSaturday()) {
            $fecha->subDay();
        }
        // Si cae en domingo, mover a viernes
        if ($fecha->isSunday()) {
            $fecha->subDays(2);
        }
        
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
        
        $request->validate([
            'tpago_estado' => 'required|in:vigente,vencido,cancelado',
            'est_codigo' => 'nullable|exists:colegio_estudiantes,est_codigo'
        ]);

        $pago->update([
            'tpago_estado' => $request->tpago_estado,
            'est_codigo' => $request->est_codigo ?? $pago->est_codigo
        ]);
        
        return redirect()->route('pagos-transporte.index')->with('success', 'Pago actualizado');
    }

    public function destroy($id)
    {
        $pago = PagoTransporte::findOrFail($id);
        $pago->update(['tpago_estado' => 'cancelado']);
        return redirect()->route('pagos-transporte.index')->with('success', 'Pago cancelado');
    }
}
