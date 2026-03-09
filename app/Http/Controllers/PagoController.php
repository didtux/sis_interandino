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
        $estudiantes = Estudiante::visible()->get();
        $cursos = Curso::visible()->get();
        return view('pagos.index', compact('pagos', 'estudiantes', 'cursos'));
    }

    public function create()
    {
        $year = date('Y');
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
        
        // Agregar conteo de pagos realizados y meses pagados
        foreach ($estudiantes as $estudiante) {
            if ($estudiante->inscripcion) {
                $pagos = Pago::where('est_codigo', $estudiante->est_codigo)
                    ->whereYear('pagos_fecha', $year)
                    ->where('pagos_estado', 1)
                    ->orderBy('pagos_fecha', 'asc')
                    ->get();
                
                $estudiante->inscripcion->pagos_realizados = $pagos->count();
                $estudiante->inscripcion->historial_pagos = $pagos;
                
                // Calcular meses pagados y próxima cuota
                $mesesPagados = [];
                $montoMensualidad = $estudiante->inscripcion->insc_monto_final / 10;
                $montoPagadoInicial = 300; // Pago de inscripción
                $saldoAcumulado = 0; // Solo pagos de mensualidades
                
                foreach ($pagos as $pago) {
                    $saldoAcumulado += $pago->pagos_precio;
                }
                
                // Calcular cuántos meses completos se han pagado
                // Primera mensualidad = mensualidad - 300
                $primeraMensualidad = $montoMensualidad - $montoPagadoInicial;
                
                if ($saldoAcumulado >= $primeraMensualidad) {
                    $mesesPagados[] = 2; // Febrero pagado
                    $saldoRestante = $saldoAcumulado - $primeraMensualidad;
                    
                    // Calcular meses adicionales pagados
                    $mesesAdicionales = floor($saldoRestante / $montoMensualidad);
                    for ($i = 1; $i <= $mesesAdicionales && ($i + 2) <= 11; $i++) {
                        $mesesPagados[] = $i + 2;
                    }
                }
                
                $estudiante->inscripcion->meses_pagados = $mesesPagados;
                
                // Próxima cuota: si no ha pagado nada, es primera cuota (mensualidad - 300), sino es cuota completa
                if (count($mesesPagados) == 0) {
                    $estudiante->inscripcion->proxima_cuota = $primeraMensualidad;
                } else {
                    $estudiante->inscripcion->proxima_cuota = $montoMensualidad;
                }
            }
        }
        
        return view('pagos.create', compact('estudiantes'));
    }

    public function store(Request $request)
    {
        // Si se ingresó un nuevo padre, crearlo
        if ($request->filled('pfam_nombre_nuevo')) {
            $padre = PadreFamilia::create([
                'pfam_codigo' => 'Pad' . str_pad(PadreFamilia::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
                'pfam_nombres' => $request->pfam_nombre_nuevo,
                'pfam_ci' => '0000000',
                'pfam_estado' => 1
            ]);
            
            // Vincular padre con estudiante
            \DB::table('rela_estudiantespadres')->insert([
                'est_id' => $request->est_codigo,
                'pfam_id' => $padre->pfam_codigo,
                'estpad_estado' => 1
            ]);
            
            $pfamCodigo = $padre->pfam_codigo;
        } else {
            $request->validate(['pfam_codigo' => 'required']);
            $pfamCodigo = $request->pfam_codigo;
        }

        $request->validate([
            'est_codigo' => 'required',
            'pagos_precio' => 'required|numeric',
            'mes' => 'required|integer|min:2|max:11',
            'cantidad_cuotas' => 'required|integer|min:1|max:10',
            'sin_factura' => 'required|in:0,1'
        ]);

        $cantidadCuotas = $request->cantidad_cuotas;
        $mesInicio = $request->mes;
        $year = date('Y');
        $sinFactura = $request->sin_factura == 1;
        $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // Validar que no existan pagos duplicados
        for ($i = 0; $i < $cantidadCuotas; $i++) {
            $mesActual = $mesInicio + $i;
            if ($mesActual > 11) break;

            $pagoExistente = Pago::where('est_codigo', $request->est_codigo)
                ->whereYear('pagos_fecha', $year)
                ->whereMonth('pagos_fecha', $mesActual)
                ->where('pagos_estado', 1)
                ->exists();

            if ($pagoExistente) {
                return back()->withErrors(['error' => 'El estudiante ya tiene registrado el pago de ' . $mesesNombres[$mesActual] . '.'])->withInput();
            }
        }

        // Registrar pagos
        for ($i = 0; $i < $cantidadCuotas; $i++) {
            $mesActual = $mesInicio + $i;
            if ($mesActual > 11) break;

            // Generar código según tipo de factura
            if ($sinFactura) {
                $ultimoTAL = Pago::where('pagos_codigo', 'like', 'TAL%')->max('pagos_codigo');
                $numeroTAL = $ultimoTAL ? intval(substr($ultimoTAL, 3)) + 1 : 1;
                $codigo = 'TAL' . str_pad($numeroTAL, 5, '0', STR_PAD_LEFT);
            } else {
                $ultimoREC = Pago::where('pagos_codigo', 'like', 'REC%')->max('pagos_codigo');
                $numeroREC = $ultimoREC ? intval(substr($ultimoREC, 3)) + 1 : 1;
                $codigo = 'REC' . str_pad($numeroREC, 5, '0', STR_PAD_LEFT);
            }

            Pago::create([
                'pagos_codigo' => $codigo,
                'men_codigo' => 'PAGO' . str_pad(Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                'est_codigo' => $request->est_codigo,
                'pfam_codigo' => $pfamCodigo,
                'prod_codigo' => 'MENSUALIDAD',
                'pagos_precio' => $request->pagos_precio,
                'pagos_nombres' => $request->pagos_nombres ?? 'Mensualidad ' . $mesesNombres[$mesActual],
                'pagos_usuario' => auth()->user()->us_codigo ?? 'ADMIN',
                'pagos_descuento' => 0,
                'concepto' => 'Mensualidad ' . $mesesNombres[$mesActual],
                'tipo' => 1,
                'pagos_fecha' => now(),
                'pagos_sin_factura' => $sinFactura ? 1 : 0
            ]);
        }

        return redirect()->route('pagos.index')->with('success', 'Mensualidad(es) registrada(s) exitosamente');
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
            // Contar pagos de mensualidades realizados
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
        $pago->pagos_estado = 0;
        $pago->save();
        
        return response()->json(['success' => true, 'message' => 'Pago anulado']);
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
        
        // Obtener TODOS los estudiantes visibles con pagos del año
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
        
        // Filtrar estudiantes en mora (con o sin inscripción)
        $estudiantesEnMora = $estudiantes->filter(function($est) use ($mesActual, $year) {
            // Si tiene pagos del año, verificar si pagó el mes actual
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
            // Si tiene inscripción pero no pagos, está en mora
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