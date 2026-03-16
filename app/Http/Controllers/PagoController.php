<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use App\Models\Curso;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PagoController extends Controller
{
    public function index()
    {
        $query = Pago::with('estudiante.curso', 'padreFamilia');

        if (request()->filled('fecha_inicio')) {
            $query->whereDate('pagos_fecha', '>=', request('fecha_inicio'));
        }
        if (request()->filled('fecha_fin')) {
            $query->whereDate('pagos_fecha', '<=', request('fecha_fin'));
        }
        if (request()->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) {
                $q->where('cur_codigo', request('cur_codigo'));
            });
        }
        if (request()->filled('est_codigo')) {
            $query->where('est_codigo', request('est_codigo'));
        }
        if (request('estado') === '0') {
            $query->where('pagos_estado', 0);
        } elseif (request('estado') === 'activos') {
            $query->where('pagos_estado', 1);
        }

        $pagos = $query->orderBy('pagos_fecha', 'desc')->paginate(20);

        // Agrupar pagos por pagos_codigo para recibos conjuntos y vista agrupada
        $codigosEnPagina = $pagos->pluck('pagos_codigo')->unique()->values();
        $pagosRecibo = [];
        $codigosConjuntos = []; // códigos que tienen >1 pago
        if ($codigosEnPagina->isNotEmpty()) {
            $todos = Pago::with('estudiante.curso')
                ->whereIn('pagos_codigo', $codigosEnPagina)
                ->orderBy('est_codigo')
                ->get();
            foreach ($todos->groupBy('pagos_codigo') as $codigo => $grupo) {
                $items = $grupo->map(function($p) {
                    return [
                        'pagos_id' => $p->pagos_id,
                        'estudiante' => ($p->estudiante->est_nombres ?? '') . ' ' . ($p->estudiante->est_apellidos ?? ''),
                        'curso' => $p->estudiante->curso->cur_nombre ?? 'N/A',
                        'concepto' => $p->concepto,
                        'monto' => $p->pagos_precio - $p->pagos_descuento,
                        'estado' => $p->pagos_estado,
                    ];
                })->values()->toArray();
                $pagosRecibo[$codigo] = $items;
                if (count($items) > 1) {
                    $codigosConjuntos[] = $codigo;
                }
            }
        }

        $estudiantes = Estudiante::visible()->get();
        $cursos = Curso::visible()->get();
        return view('pagos.index', compact('pagos', 'estudiantes', 'cursos', 'pagosRecibo', 'codigosConjuntos'));
    }

    public function create()
    {
        $year = date('Y');

        // Cargar padres que tienen estudiantes inscritos
        $padres = PadreFamilia::activo()
            ->whereHas('estudiantes', function($q) use ($year) {
                $q->where('est_visible', 1)
                  ->whereHas('inscripcion', function($qi) use ($year) {
                      $qi->where('insc_gestion', $year)->where('insc_estado', 1);
                  });
            })
            ->orderBy('pfam_nombres')
            ->get();

        // Preparar datos de estudiantes inscritos con su info de pagos
        $estudiantes = Estudiante::visible()
            ->with([
                'curso',
                'padres',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)
                      ->where('insc_estado', 1)
                      ->with(['descuentos', 'padreFamilia']);
                }
            ])
            ->whereHas('inscripcion', function($q) use ($year) {
                $q->where('insc_gestion', $year)->where('insc_estado', 1);
            })
            ->get();

        $estudiantesData = [];
        foreach ($estudiantes as $est) {
            if (!$est->inscripcion) continue;

            $insc = $est->inscripcion;
            $montoFinal = $insc->insc_monto_final ?? 0;
            $montoMensualidad = $montoFinal > 0 ? $montoFinal / 10 : 0;
            $primeraCuota = max(0, $montoMensualidad - 300);

            // Pagos activos del año
            $pagos = Pago::where('est_codigo', $est->est_codigo)
                ->whereYear('pagos_fecha', $year)
                ->where('pagos_estado', 1)
                ->orderBy('pagos_fecha', 'asc')
                ->get();

            $saldoAcumulado = $pagos->sum('pagos_precio');
            $mesesPagados = [];

            if ($saldoAcumulado >= $primeraCuota && $primeraCuota > 0) {
                $mesesPagados[] = 2;
                $saldoRestante = $saldoAcumulado - $primeraCuota;
                $mesesAdicionales = $montoMensualidad > 0 ? floor($saldoRestante / $montoMensualidad) : 0;
                for ($i = 1; $i <= $mesesAdicionales && ($i + 2) <= 11; $i++) {
                    $mesesPagados[] = $i + 2;
                }
            } elseif ($saldoAcumulado > 0 && $primeraCuota == 0) {
                $mesesAdicionales = $montoMensualidad > 0 ? floor($saldoAcumulado / $montoMensualidad) : 0;
                for ($i = 0; $i < $mesesAdicionales && ($i + 2) <= 11; $i++) {
                    $mesesPagados[] = $i + 2;
                }
            }

            $proximaCuota = count($mesesPagados) == 0 ? $primeraCuota : $montoMensualidad;

            // Historial de pagos para mostrar en la vista
            $historial = $pagos->map(function($p) {
                return [
                    'fecha' => $p->pagos_fecha ? $p->pagos_fecha->format('d/m/Y') : '-',
                    'concepto' => $p->concepto,
                    'monto' => $p->pagos_precio,
                    'codigo' => $p->pagos_codigo,
                ];
            })->values()->toArray();

            // Descuentos aplicados
            $descuentos = $insc->descuentos->map(function($d) {
                return $d->desc_nombre . ' (-Bs. ' . number_format($d->pivot->inscdesc_monto_descuento, 2) . ')';
            })->toArray();

            $estudiantesData[$est->est_codigo] = [
                'est_codigo' => $est->est_codigo,
                'nombre' => $est->est_nombres . ' ' . $est->est_apellidos,
                'curso' => $est->curso->cur_nombre ?? 'N/A',
                'mensualidad' => $montoMensualidad,
                'proxima_cuota' => $proximaCuota,
                'meses_pagados' => $mesesPagados,
                'sin_factura' => $insc->insc_sin_factura ?? 0,
                'padres' => $est->padres->pluck('pfam_codigo')->toArray(),
                'monto_total' => $insc->insc_monto_total ?? 0,
                'monto_descuento' => $insc->insc_monto_descuento ?? 0,
                'monto_final' => $montoFinal,
                'primera_cuota' => $primeraCuota,
                'total_pagado' => $saldoAcumulado,
                'saldo_pendiente' => max(0, $montoFinal - $saldoAcumulado),
                'descuentos' => $descuentos,
                'historial' => $historial,
            ];
        }

        return view('pagos.create', compact('padres', 'estudiantesData'));
    }

    public function store(Request $request)
    {
        // Crear padre nuevo si aplica
        if ($request->filled('pfam_nombre_nuevo')) {
            $padre = PadreFamilia::create([
                'pfam_codigo' => 'Pad' . str_pad(PadreFamilia::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
                'pfam_nombres' => $request->pfam_nombre_nuevo,
                'pfam_ci' => '0000000',
                'pfam_estado' => 1
            ]);
            $pfamCodigo = $padre->pfam_codigo;
        } else {
            $request->validate(['pfam_codigo' => 'required']);
            $pfamCodigo = $request->pfam_codigo;
        }

        $estudiantesInput = $request->input('estudiantes', []);
        if (empty($estudiantesInput)) {
            return back()->withErrors(['error' => 'Debe seleccionar al menos un estudiante.'])->withInput();
        }

        $year = date('Y');
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // Un solo código de recibo para toda la transacción
        $sinFactura = intval($estudiantesInput[array_key_first($estudiantesInput)]['sin_factura'] ?? 0);
        if ($sinFactura) {
            $ultimo = Pago::where('pagos_codigo', 'like', 'TAL%')->max('pagos_codigo');
            $codigoRecibo = 'TAL' . str_pad(($ultimo ? intval(substr($ultimo, 3)) : 0) + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $ultimo = Pago::where('pagos_codigo', 'like', 'REC%')->max('pagos_codigo');
            $codigoRecibo = 'REC' . str_pad(($ultimo ? intval(substr($ultimo, 3)) : 0) + 1, 5, '0', STR_PAD_LEFT);
        }

        foreach ($estudiantesInput as $estData) {
            $estCodigo = $estData['est_codigo'];
            $mesInicio = intval($estData['mes']);
            $cantidadCuotas = intval($estData['cantidad_cuotas']);
            $montoCuota = floatval($estData['pagos_precio']);
            $sinFactura = intval($estData['sin_factura'] ?? 0);

            // Validar duplicados
            for ($i = 0; $i < $cantidadCuotas; $i++) {
                $mesActual = $mesInicio + $i;
                if ($mesActual > 11) break;

                $existe = Pago::where('est_codigo', $estCodigo)
                    ->whereYear('pagos_fecha', $year)
                    ->where('pagos_estado', 1)
                    ->where('concepto', 'like', '%' . $mesesNombres[$mesActual] . '%')
                    ->exists();

                if ($existe) {
                    $est = Estudiante::where('est_codigo', $estCodigo)->first();
                    return back()->withErrors(['error' => ($est->est_nombres ?? '') . ' ya tiene pagado ' . $mesesNombres[$mesActual] . '.'])->withInput();
                }
            }

            // Registrar pagos — todos con el mismo código de recibo
            for ($i = 0; $i < $cantidadCuotas; $i++) {
                $mesActual = $mesInicio + $i;
                if ($mesActual > 11) break;

                Pago::create([
                    'pagos_codigo' => $codigoRecibo,
                    'men_codigo' => 'PAGO' . str_pad(Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                    'est_codigo' => $estCodigo,
                    'pfam_codigo' => $pfamCodigo,
                    'prod_codigo' => 'MENSUALIDAD',
                    'pagos_precio' => $montoCuota,
                    'pagos_nombres' => 'Mensualidad ' . $mesesNombres[$mesActual],
                    'pagos_usuario' => auth()->user()->us_codigo ?? 'ADMIN',
                    'pagos_descuento' => 0,
                    'concepto' => 'Mensualidad ' . $mesesNombres[$mesActual],
                    'tipo' => 1,
                    'pagos_fecha' => now(),
                    'pagos_sin_factura' => $sinFactura ? 1 : 0
                ]);
            }
        }

        return redirect()->route('pagos.index')->with('success', 'Pago(s) registrado(s) exitosamente');
    }

    public function show($id)
    {
        $pago = Pago::with('estudiante', 'padreFamilia')->findOrFail($id);
        return view('pagos.show', compact('pago'));
    }

    public function reportePdf(Request $request)
    {
        $query = Pago::with('estudiante.curso', 'padreFamilia')->where('pagos_estado', 1);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('pagos_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('pagos_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }
        if ($request->filled('est_codigo')) {
            $query->where('est_codigo', $request->est_codigo);
        }

        $pagos = $query->orderBy('est_codigo')->get();
        
        $pdf = Pdf::loadView('pagos.reporte-mensualidades-pdf', compact('pagos', 'request'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('reporte-mensualidades-' . date('Y-m-d') . '.pdf');
    }

    public function reporteExcel(Request $request)
    {
        return response()->download(storage_path('app/reporte-pagos.xlsx'));
    }

    public function getPadresByEstudiante($est_codigo)
    {
        $estudiante = Estudiante::where('est_codigo', $est_codigo)->with('padres')->first();
        return response()->json($estudiante ? $estudiante->padres : []);
    }

    public function getEstudianteInscripcion($est_codigo)
    {
        $year = date('Y');
        $estudiante = Estudiante::where('est_codigo', $est_codigo)
            ->with([
                'padres',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)
                      ->where('insc_estado', 1)
                      ->with('descuentos');
                }
            ])
            ->first();
        
        if ($estudiante && $estudiante->inscripcion) {
            $pagosRealizados = Pago::where('est_codigo', $est_codigo)
                ->whereYear('pagos_fecha', $year)
                ->where('pagos_estado', 1)
                ->count();
            
            $estudiante->inscripcion->pagos_realizados = $pagosRealizados;
        }
        
        return response()->json([
            'inscripcion' => $estudiante->inscripcion ?? null,
            'padres' => $estudiante->padres ?? []
        ]);
    }

    public function anular($id)
    {
        $pago = Pago::findOrFail($id);
        // Anular todos los pagos con el mismo código (pago conjunto)
        Pago::where('pagos_codigo', $pago->pagos_codigo)->update(['pagos_estado' => 0]);
        
        return response()->json(['success' => true, 'message' => 'Pago(s) anulado(s)']);
    }

    public function resumenAnual()
    {
        $cursos = Curso::visible()->get();
        return view('pagos.resumen-anual', compact('cursos'));
    }

    public function resumenAnualPdf(Request $request)
    {
        $year = $request->year ?? date('Y');
        
        $query = Pago::with('estudiante.curso')
            ->whereYear('pagos_fecha', $year)
            ->where('pagos_estado', 1);

        if ($request->filled('cur_codigo')) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('cur_codigo', $request->cur_codigo);
            });
        }

        $pagos = $query->get();
        
        $cursos = $request->filled('cur_codigo') 
            ? Curso::where('cur_codigo', $request->cur_codigo)->get()
            : Curso::visible()->orderBy('cur_nombre')->get();
        
        $pdf = Pdf::loadView('pagos.resumen-anual-pdf', compact('pagos', 'year', 'cursos'))
            ->setPaper('legal', 'landscape');
        return $pdf->stream('resumen-anual-' . $year . '.pdf');
    }

    public function resumenAnualExcel(Request $request)
    {
        $year = $request->year ?? date('Y');
        $query = Estudiante::visible()->with(['curso', 'pagos' => function($q) use ($year) {
            $q->whereYear('pagos_fecha', $year);
        }]);

        if ($request->filled('cur_codigo')) {
            $query->where('cur_codigo', $request->cur_codigo);
        }

        $estudiantes = $query->orderBy('cur_codigo')->get();
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ResumenAnualExport($estudiantes, $year), 
            'resumen-anual-' . $year . '.xlsx'
        );
    }

    public function mora()
    {
        $year = date('Y');
        $mesActual = date('n');
        
        $query = Estudiante::visible()
            ->with([
                'curso',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)->where('insc_estado', 1);
                },
                'pagos' => function($q) use ($year) {
                    $q->whereYear('pagos_fecha', $year)->where('pagos_estado', 1);
                }
            ]);

        if (request()->filled('cur_codigo')) {
            $query->where('cur_codigo', request('cur_codigo'));
        }
        if (request()->filled('mes')) {
            $mesActual = request('mes');
        }

        $estudiantes = $query->orderBy('cur_codigo')->orderBy('est_apellidos')->get();
        
        $estudiantesEnMora = $estudiantes->filter(function($est) use ($mesActual, $year) {
            if ($est->pagos->count() > 0) {
                $mesesPagados = [];
                foreach($est->pagos as $pago) {
                    $mesesCubiertos = $pago->meses_cubiertos;
                    $mesesPagados = array_merge($mesesPagados, $mesesCubiertos);
                }
                $mesesPagados = array_unique($mesesPagados);
                
                if ($mesActual >= 2 && $mesActual <= 11) {
                    return !in_array($mesActual, $mesesPagados);
                }
            }
            elseif ($est->inscripcion && $mesActual >= 2 && $mesActual <= 11) {
                return true;
            }
            
            return false;
        });

        $cursos = Curso::visible()->get();
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        return view('pagos.mora', compact('estudiantesEnMora', 'cursos', 'mesActual', 'mesesNombres'));
    }

    public function moraPdf(Request $request)
    {
        $year = date('Y');
        $mesActual = $request->mes ?? date('n');
        
        $query = Estudiante::visible()
            ->with([
                'curso',
                'inscripcion' => function($q) use ($year) {
                    $q->where('insc_gestion', $year)->where('insc_estado', 1);
                },
                'pagos' => function($q) use ($year) {
                    $q->whereYear('pagos_fecha', $year)->where('pagos_estado', 1);
                }
            ]);

        if ($request->filled('cur_codigo')) {
            $query->where('cur_codigo', $request->cur_codigo);
        }

        $estudiantes = $query->orderBy('cur_codigo')->orderBy('est_apellidos')->get();
        
        $estudiantesEnMora = $estudiantes->filter(function($est) use ($mesActual) {
            if ($est->pagos->count() > 0) {
                $mesesPagados = [];
                foreach($est->pagos as $pago) {
                    $mesesPagados = array_merge($mesesPagados, $pago->meses_cubiertos);
                }
                $mesesPagados = array_unique($mesesPagados);
                
                if ($mesActual >= 2 && $mesActual <= 11) {
                    return !in_array($mesActual, $mesesPagados);
                }
            }
            elseif ($est->inscripcion && $mesActual >= 2 && $mesActual <= 11) {
                return true;
            }
            return false;
        });

        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        $pdf = Pdf::loadView('pagos.mora-pdf', compact('estudiantesEnMora', 'mesActual', 'mesesNombres', 'year'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('estudiantes-mora-' . date('Y-m-d') . '.pdf');
    }
}
