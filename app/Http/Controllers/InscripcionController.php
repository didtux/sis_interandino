<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Estudiante;
use App\Models\PadreFamilia;
use App\Models\Curso;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $query = Inscripcion::with(['estudiante.padres', 'curso', 'padreFamilia', 'pagos', 'descuentos']);

        if ($request->buscar) {
            $query->whereHas('estudiante', function($q) use ($request) {
                $q->where('est_nombres', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_apellidos', 'like', '%' . $request->buscar . '%')
                  ->orWhere('est_ci', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->pfam_codigo) {
            $query->where('pfam_codigo', $request->pfam_codigo);
        }

        if ($request->estado === '0') {
            $query->where('insc_estado', 0);
        } elseif ($request->estado === 'activas') {
            $query->where('insc_estado', '!=', 0);
        }

        if ($request->tipo_factura === '1') {
            $query->where('insc_sin_factura', 1);
        } elseif ($request->tipo_factura === '0') {
            $query->where('insc_sin_factura', 0);
        }

        if ($request->descuento === 'con_descuento') {
            $query->whereHas('descuentos');
        } elseif ($request->descuento === 'sin_descuento') {
            $query->whereDoesntHave('descuentos');
        } elseif ($request->descuento && is_numeric($request->descuento)) {
            $query->whereHas('descuentos', function($q) use ($request) {
                $q->where('desc_id', $request->descuento);
            });
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->paginate(50);
        $descuentos = \App\Models\Descuento::where('desc_estado', 1)->get();
        
        return view('inscripciones.index', compact('inscripciones', 'descuentos'));
    }

    public function reportes(Request $request)
    {
        $estudiantes = Estudiante::visible()->get();
        
        $query = Inscripcion::with(['estudiante', 'curso', 'padreFamilia']);

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->estado !== null && $request->estado !== '') {
            $query->where('insc_estado', $request->estado);
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->get();
        
        return view('inscripciones.reportes', compact('estudiantes', 'inscripciones'));
    }

    public function reportePdf(Request $request)
    {
        $query = Inscripcion::with(['estudiante', 'curso', 'padreFamilia']);

        if ($request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('insc_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->est_codigo) {
            $query->where('est_codigo', $request->est_codigo);
        }

        if ($request->estado === '0') {
            $query->where('insc_estado', 0);
        } elseif ($request->estado === 'activas') {
            $query->where('insc_estado', '!=', 0);
        }

        if ($request->tipo_factura === '1') {
            $query->where('insc_sin_factura', 1);
        } elseif ($request->tipo_factura === '0') {
            $query->where('insc_sin_factura', 0);
        }

        $inscripciones = $query->orderBy('insc_fecha', 'desc')->get();
        
        $total = $inscripciones->where('insc_estado', '!=', 0)->sum('insc_monto_total');
        $pagado = $inscripciones->where('insc_estado', '!=', 0)->sum('insc_monto_pagado');
        $saldo = $inscripciones->where('insc_estado', '!=', 0)->sum('insc_saldo');

        $pdf = \PDF::loadView('inscripciones.reporte-pdf', compact('inscripciones', 'total', 'pagado', 'saldo', 'request'));
        return $pdf->stream('inscripciones_' . time() . '.pdf');
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->get();
        $padres = PadreFamilia::where('pfam_estado', 1)->get();
        $cursos = Curso::visible()->get();
        $descuentos = \App\Models\Descuento::where('desc_estado', 1)->get();
        return view('inscripciones.create', compact('estudiantes', 'padres', 'cursos', 'descuentos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'cur_codigo' => 'required|exists:colegio_cursos,cur_codigo',
            'insc_monto_total' => 'required|numeric|min:0',
            'insc_monto_pagado' => 'nullable|numeric|min:0|max:300',
            'insc_gestion' => 'required'
        ]);

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
            $request->validate(['pfam_codigo' => 'required|exists:cole_padresfamilia,pfam_codigo']);
            $pfamCodigo = $request->pfam_codigo;
        }

        $montoTotal = $request->insc_monto_total;
        $montoDescuento = $request->insc_monto_descuento ?? 0;
        $montoFinal = $request->insc_monto_final ?? $montoTotal;
        $montoPagado = min($request->insc_monto_pagado ?? 0, 300);
        $sinFactura = $request->insc_sin_factura ?? 0;

        $inscripcion = Inscripcion::create([
            'insc_codigo' => 'INSC' . time(),
            'est_codigo' => $request->est_codigo,
            'pfam_codigo' => $pfamCodigo,
            'cur_codigo' => $request->cur_codigo,
            'insc_gestion' => $request->insc_gestion,
            'insc_monto_total' => $montoTotal,
            'insc_monto_descuento' => $montoDescuento,
            'insc_monto_final' => $montoFinal,
            'insc_monto_pagado' => $montoPagado,
            'insc_saldo' => $montoFinal - $montoPagado,
            'insc_concepto' => $request->insc_concepto,
            'insc_estado' => 1,
            'insc_usuario' => auth()->user()->us_codigo,
            'insc_sin_factura' => $sinFactura
        ]);

        // Registrar descuento si existe
        if ($request->desc_id) {
            $inscripcion->descuentos()->attach($request->desc_id, [
                'inscdesc_monto_descuento' => $montoDescuento
            ]);
        }

        // Si hay pago inicial, registrarlo
        if ($montoPagado > 0) {
            // Generar código de recibo según si es con o sin factura
            $prefijoRecibo = $sinFactura ? 'TAL' : 'REC';
            $ultimoRecibo = InscripcionPago::where('inscpago_recibo', 'like', $prefijoRecibo . '%')
                ->orderBy('inscpago_id', 'desc')
                ->first();
            
            if ($ultimoRecibo) {
                $numero = intval(substr($ultimoRecibo->inscpago_recibo, 3)) + 1;
            } else {
                $numero = 1;
            }
            
            $codigoRecibo = $prefijoRecibo . str_pad($numero, 6, '0', STR_PAD_LEFT);
            
            InscripcionPago::create([
                'inscpago_codigo' => 'PAGO' . time(),
                'insc_codigo' => $inscripcion->insc_codigo,
                'inscpago_monto' => $montoPagado,
                'inscpago_concepto' => 'Pago inicial de inscripción',
                'inscpago_usuario' => auth()->user()->us_codigo,
                'inscpago_recibo' => $codigoRecibo
            ]);
        }

        return redirect()->route('inscripciones.index')->with('success', 'Inscripción registrada');
    }

    public function registrarPago(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        
        $request->validate([
            'monto' => 'required|numeric|min:0|max:' . $inscripcion->insc_saldo
        ]);

        $pago = InscripcionPago::create([
            'inscpago_codigo' => 'PAGO' . time(),
            'insc_codigo' => $inscripcion->insc_codigo,
            'inscpago_monto' => $request->monto,
            'inscpago_concepto' => $request->concepto ?? 'Pago de inscripción',
            'inscpago_usuario' => auth()->user()->us_codigo,
            'inscpago_recibo' => 'REC' . time()
        ]);

        $inscripcion->insc_monto_pagado += $request->monto;
        $inscripcion->insc_saldo -= $request->monto;
        
        if ($inscripcion->insc_saldo <= 0) {
            $inscripcion->insc_estado = 2; // Pagada
        }
        
        $inscripcion->save();

        return redirect()->back()->with('success', 'Pago registrado');
    }

    public function anular($id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->insc_estado = 0;
        $inscripcion->save();
        
        return response()->json(['success' => true, 'message' => 'Inscripción anulada']);
    }

    public function actualizarDescuento(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        
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
                'est_id' => $inscripcion->est_codigo,
                'pfam_id' => $padre->pfam_codigo,
                'estpad_estado' => 1
            ]);
            
            $inscripcion->pfam_codigo = $padre->pfam_codigo;
        } elseif ($request->filled('pfam_codigo')) {
            $inscripcion->pfam_codigo = $request->pfam_codigo;
        }
        
        $request->validate(['desc_id' => 'required|exists:descuentos,desc_id']);
        
        $descuento = \App\Models\Descuento::findOrFail($request->desc_id);
        
        // Calcular nuevo monto con descuento
        $montoTotal = $inscripcion->insc_monto_total;
        $montoDescuento = $montoTotal * ($descuento->desc_porcentaje / 100);
        $montoFinal = $montoTotal - $montoDescuento;
        
        // Actualizar inscripción
        $inscripcion->insc_monto_descuento = $montoDescuento;
        $inscripcion->insc_monto_final = $montoFinal;
        $inscripcion->insc_saldo = $montoFinal - $inscripcion->insc_monto_pagado;
        $inscripcion->save();
        
        // Actualizar relación con descuento
        $inscripcion->descuentos()->sync([
            $descuento->desc_id => ['inscdesc_monto_descuento' => $montoDescuento]
        ]);
        
        return redirect()->route('inscripciones.index')->with('success', 'Inscripción actualizada');
    }

    public function eliminarCargaMasiva(Request $request)
    {
        $year = date('Y');
        $eliminados = 0;
        $estudiantesEliminados = 0;
        
        // Obtener inscripciones de la gestión actual con código INSC
        $inscripciones = Inscripcion::where('insc_gestion', $year)
            ->where('insc_codigo', 'like', 'INSC%')
            ->get();
        
        $estudiantesCodigos = [];
        
        foreach($inscripciones as $insc) {
            $estudiantesCodigos[] = $insc->est_codigo;
            
            // Eliminar pagos de mensualidades relacionados
            \App\Models\Pago::where('est_codigo', $insc->est_codigo)
                ->whereYear('pagos_fecha', $year)
                ->delete();
            
            // Eliminar pagos de inscripción
            InscripcionPago::where('insc_codigo', $insc->insc_codigo)->delete();
            
            // Eliminar relación con descuentos
            $insc->descuentos()->detach();
            
            // Eliminar inscripción
            $insc->delete();
            $eliminados++;
        }
        
        // Eliminar estudiantes creados en la carga (que no tienen otras inscripciones NI pagos de transporte)
        foreach(array_unique($estudiantesCodigos) as $estCodigo) {
            $estudiante = Estudiante::where('est_codigo', $estCodigo)->first();
            if($estudiante) {
                // Verificar si tiene otras inscripciones
                $tieneOtrasInscripciones = Inscripcion::where('est_codigo', $estCodigo)->exists();
                
                // Verificar si tiene pagos de transporte
                $tienePagosTransporte = \App\Models\PagoTransporte::where('est_codigo', $estCodigo)->exists();
                
                if(!$tieneOtrasInscripciones && !$tienePagosTransporte) {
                    // Eliminar relación con padres
                    \DB::table('rela_estudiantespadres')->where('est_id', $estCodigo)->delete();
                    
                    // Eliminar estudiante
                    $estudiante->delete();
                    $estudiantesEliminados++;
                }
            }
        }
        
        return redirect()->route('inscripciones.index')->with('success', "Eliminados: $eliminados inscripciones y $estudiantesEliminados estudiantes");
    }

    public function cargarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
            'mes_inscripcion' => 'required|integer|min:1|max:12'
        ]);
        
        $mesInscripcion = intval($request->mes_inscripcion);
        $file = $request->file('archivo');
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $data = $sheet->toArray(null, true, true, true);
        
        $year = date('Y');
        $registrados = 0;
        $saltados = [
            'anulados' => 0,
            'sin_datos' => 0,
            'monto_cero' => 0,
            'ya_existe' => 0
        ];
        
        foreach($data as $index => $row) {
            if($index == 1) continue;
            
            $numFactura = trim($row['B'] ?? ''); // Columna B: Número factura
            $ci = trim($row['C'] ?? ''); // Columna C: CI
            $nombreCompleto = trim($row['D'] ?? ''); // Columna D: NOMBRE
            $cursoNombre = trim($row['E'] ?? ''); // Columna E: CURSO
            
            // Debug: registrar fila 62
            if($index == 62) {
                \Log::info('Fila 62 Excel', [
                    'ci' => $ci,
                    'nombre' => $nombreCompleto,
                    'curso' => $cursoNombre,
                    'row_completo' => $row
                ]);
            }
            
            // Validar que al menos tenga CI o nombre
            if(empty($ci) && empty($nombreCompleto)) {
                $saltados['sin_datos']++;
                continue;
            }
            
            // Si el nombre contiene "anulado", saltar ANTES de crear estudiante
            if(stripos($nombreCompleto, 'anulado') !== false) {
                $saltados['anulados']++;
                continue;
            }
            
            // Si el apellido es "ANULADO", saltar
            $partes = explode(' ', $nombreCompleto, 2);
            $apellidos = $partes[0] ?? '';
            if(strtoupper(trim($apellidos)) === 'ANULADO') {
                $saltados['anulados']++;
                continue;
            }
            
            // Determinar si es sin factura (TAL) o con factura (REC)
            $sinFactura = empty($numFactura) ? 1 : 0;
            $prefijoRecibo = $sinFactura ? 'TAL' : 'REC';
            
            // Limpiar CI: extraer solo números
            $ciLimpio = preg_replace('/[^0-9]/', '', $ci);
            
            // Buscar estudiante por CI (prioritario) o por nombre
            $estudiante = null;
            $estudianteExistente = false;
            
            // 1. Buscar por CI si existe
            if(!empty($ciLimpio) && strlen($ciLimpio) >= 5) {
                $estudiante = Estudiante::whereRaw('REGEXP_REPLACE(est_ci, "[^0-9]", "") = ?', [$ciLimpio])->first();
                if($estudiante) {
                    $estudianteExistente = true;
                }
            }
            
            // 2. Si no encuentra por CI, buscar por nombre completo (búsqueda estricta)
            if(!$estudiante && !empty($nombreCompleto)) {
                $nombreLimpio = strtoupper(trim($nombreCompleto));
                $palabras = array_filter(explode(' ', $nombreLimpio));
                
                // Buscar estudiante que coincida con al menos 70% de las palabras
                if(count($palabras) >= 3) {
                    $estudiantes = Estudiante::all();
                    $mejorCoincidencia = null;
                    $maxPorcentaje = 0;
                    
                    foreach($estudiantes as $est) {
                        $nombreCompletoEst = strtoupper($est->est_nombres . ' ' . $est->est_apellidos);
                        $apellidosNombresEst = strtoupper($est->est_apellidos . ' ' . $est->est_nombres);
                        $coincidencias = 0;
                        
                        foreach($palabras as $palabra) {
                            if(strpos($nombreCompletoEst, $palabra) !== false || strpos($apellidosNombresEst, $palabra) !== false) {
                                $coincidencias++;
                            }
                        }
                        
                        $porcentaje = $coincidencias / count($palabras);
                        // Requerir al menos 70% de coincidencia y mínimo 3 palabras
                        if($coincidencias >= 3 && $porcentaje >= 0.7 && $porcentaje > $maxPorcentaje) {
                            $maxPorcentaje = $porcentaje;
                            $mejorCoincidencia = $est;
                        }
                    }
                    
                    if($mejorCoincidencia) {
                        $estudiante = $mejorCoincidencia;
                        $estudianteExistente = true;
                    }
                }
            }
            
            // Buscar curso del Excel (solo para estudiantes nuevos)
            $cursoExcel = null;
            if(!empty($cursoNombre)) {
                $cursoExcel = Curso::where('cur_nombre', 'like', '%' . $cursoNombre . '%')->first();
                if(!$cursoExcel) {
                    $cursoExcel = Curso::where('cur_nombre', 'like', '%Kinder%')
                        ->orWhere('cur_nombre', 'like', '%PreKinder%')
                        ->first();
                }
                if(!$cursoExcel) {
                    $cursoExcel = Curso::visible()->first();
                }
            }
            
            // Si no encuentra, crear estudiante básico
            if(!$estudiante) {
                $palabras = array_filter(explode(' ', trim($nombreCompleto)));
                $totalPalabras = count($palabras);
                
                // Asumir: primeras 2 palabras = nombres, últimas 2 = apellidos
                if($totalPalabras >= 3) {
                    $nombres = implode(' ', array_slice($palabras, 0, 2));
                    $apellidos = implode(' ', array_slice($palabras, 2));
                } else {
                    $nombres = $palabras[0] ?? $nombreCompleto;
                    $apellidos = $palabras[1] ?? '';
                }
                
                // Obtener último código de estudiante
                $ultimoEstudiante = Estudiante::orderBy('est_codigo', 'desc')->first();
                $nuevoNumero = 1;
                if($ultimoEstudiante && preg_match('/Est(\d+)/', $ultimoEstudiante->est_codigo, $matches)) {
                    $nuevoNumero = intval($matches[1]) + 1;
                }
                
                $estudiante = Estudiante::create([
                    'est_codigo' => 'Est' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT),
                    'est_nombres' => $nombres,
                    'est_apellidos' => $apellidos,
                    'est_ci' => $ci,
                    'cur_codigo' => $cursoExcel ? $cursoExcel->cur_codigo : Curso::visible()->first()->cur_codigo,
                    'est_visible' => 1,
                    'est_fecha' => now()
                ]);
                $estudianteExistente = false;
            }
            
            // Eliminar registros fantasma ANTES de verificar
            Inscripcion::where('insc_gestion', $year)
                ->where(function($q) {
                    $q->whereNull('est_codigo')
                      ->orWhere('est_codigo', '')
                      ->orWhereNull('pfam_codigo')
                      ->orWhere('pfam_codigo', '');
                })
                ->delete();
            
            // Verificar si ya existe inscripción válida
            $inscripcionExistente = Inscripcion::where('est_codigo', $estudiante->est_codigo)
                ->where('insc_gestion', $year)
                ->where('insc_estado', '!=', 0)
                ->first();
            
            if($inscripcionExistente) {
                // Actualizar inscripción existente con nuevos datos
                $inscripcionExistente->update([
                    'insc_monto_total' => $montoTotal,
                    'insc_monto_descuento' => $montoDescuento,
                    'insc_monto_final' => $montoFinal,
                    'insc_monto_pagado' => 300,
                    'insc_saldo' => $montoFinal - 300,
                    'insc_sin_factura' => $sinFactura
                ]);
                
                // Actualizar descuento si existe
                if($descuento) {
                    $inscripcionExistente->descuentos()->sync([
                        $descuento->desc_id => ['inscdesc_monto_descuento' => $montoDescuento]
                    ]);
                }
                
                \Log::info('Inscripción actualizada', [
                    'fila' => $index,
                    'estudiante_codigo' => $estudiante->est_codigo,
                    'inscripcion_codigo' => $inscripcionExistente->insc_codigo
                ]);
                
                $registrados++; // Contar como registrado
                continue;
            }
            
            // Cargar padre asignado al estudiante (recargar relación)
            $estudiante->load('padres');
            $padre = $estudiante->padres->first();
            
            // Si no tiene padre asignado, crear uno por defecto
            if(!$padre) {
                $padre = PadreFamilia::firstOrCreate(
                    ['pfam_nombres' => 'PADRE EJEMPLO'],
                    [
                        'pfam_codigo' => 'Pad' . str_pad(PadreFamilia::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
                        'pfam_ci' => '0000000',
                        'pfam_estado' => 1
                    ]
                );
                
                \DB::table('rela_estudiantespadres')->insertOrIgnore([
                    'est_id' => $estudiante->est_codigo,
                    'pfam_id' => $padre->pfam_codigo,
                    'estpad_estado' => 1
                ]);
            }
            
            $montoFinal = abs(floatval($row['I'] ?? 0)); // Columna I: SUB TOTAL
            $montoPagado = abs(floatval($row['J'] ?? 0)); // Columna J: PAGADO
            $saldo = abs(floatval($row['K'] ?? 0)); // Columna K: SALDO
            $montoTotal = $montoPagado + $saldo; // Total = Pagado + Saldo
            $porcentajeDesc = abs(floatval($row['L'] ?? 0)); // Columna L: % DESCUENTO
            $montoDescuento = abs(floatval($row['M'] ?? 0)); // Columna M: DESCUENTO
            $tipoDescuento = trim($row['G'] ?? ''); // Columna G: TIPO DESCUENTO
            $mesesPagados = intval($row['P'] ?? 0); // Columna P: MESES PAGADOS
            
            if($montoFinal <= 0) {
                $saltados['monto_cero']++;
                continue;
            }
            
            $descuento = null;
            if($montoDescuento > 0 && !empty($tipoDescuento)) {
                $descuento = \App\Models\Descuento::firstOrCreate(
                    ['desc_nombre' => $tipoDescuento],
                    [
                        'desc_codigo' => 'DESC' . str_pad(\App\Models\Descuento::max('desc_id') + 1, 4, '0', STR_PAD_LEFT),
                        'desc_porcentaje' => $porcentajeDesc,
                        'desc_estado' => 1
                    ]
                );
            }
            
            $inscripcion = Inscripcion::create([
                'insc_codigo' => 'INSC' . str_pad(Inscripcion::max('insc_id') + 1, 6, '0', STR_PAD_LEFT),
                'est_codigo' => $estudiante->est_codigo,
                'pfam_codigo' => $padre->pfam_codigo,
                'cur_codigo' => $estudiante->cur_codigo,
                'insc_gestion' => $year,
                'insc_monto_total' => $montoTotal,
                'insc_monto_descuento' => $montoDescuento,
                'insc_monto_final' => $montoFinal,
                'insc_monto_pagado' => 300,
                'insc_saldo' => $montoFinal - 300,
                'insc_concepto' => 'Inscripción ' . $year,
                'insc_estado' => 1,
                'insc_usuario' => auth()->user()->us_codigo,
                'insc_sin_factura' => $sinFactura
            ]);
            
            \Log::info('Inscripción creada', [
                'fila' => $index,
                'ci_excel' => $ci,
                'nombre_excel' => $nombreCompleto,
                'estudiante_codigo' => $estudiante->est_codigo,
                'inscripcion_codigo' => $inscripcion->insc_codigo
            ]);
            
            if($descuento) {
                $inscripcion->descuentos()->attach($descuento->desc_id, ['inscdesc_monto_descuento' => $montoDescuento]);
            }
            
            // Pago de inscripción (300 Bs)
            $ultimoRecibo = InscripcionPago::where('inscpago_recibo', 'like', $prefijoRecibo . '%')->orderBy('inscpago_id', 'desc')->first();
            $numero = $ultimoRecibo ? intval(substr($ultimoRecibo->inscpago_recibo, 3)) + 1 : 1;
            $codigoRecibo = $prefijoRecibo . str_pad($numero, 6, '0', STR_PAD_LEFT);
            
            InscripcionPago::create([
                'inscpago_codigo' => 'PAGO' . str_pad(InscripcionPago::max('inscpago_id') + 1, 6, '0', STR_PAD_LEFT),
                'insc_codigo' => $inscripcion->insc_codigo,
                'inscpago_monto' => 300,
                'inscpago_concepto' => 'Pago de inscripción',
                'inscpago_usuario' => auth()->user()->us_codigo,
                'inscpago_recibo' => $codigoRecibo
            ]);
            
            if($mesesPagados > 0) {
                $montoMensualidad = $montoFinal / 10;
                $mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                
                // Primera mensualidad = mensualidad - 300 (pago de inscripción)
                $primeraMensualidad = $montoMensualidad - 300;
                
                for($i = 0; $i < $mesesPagados; $i++) {
                    $mes = $mesInscripcion + $i;
                    if($mes > 12) break;
                    
                    // Primer pago es la primera mensualidad (ajustada), resto son cuotas completas
                    $montoPago = ($i == 0) ? $primeraMensualidad : $montoMensualidad;
                    
                    $ultimoPago = \App\Models\Pago::where('pagos_codigo', 'like', $prefijoRecibo . '%')->orderBy('pagos_id', 'desc')->first();
                    $numeroPago = $ultimoPago ? intval(substr($ultimoPago->pagos_codigo, 3)) + 1 : 1;
                    $codigoPago = $prefijoRecibo . str_pad($numeroPago, 5, '0', STR_PAD_LEFT);
                    
                    $fechaPago = \Carbon\Carbon::create($year, $mesInscripcion, 15);
                    
                    \App\Models\Pago::create([
                        'pagos_codigo' => $codigoPago,
                        'men_codigo' => 'MEN' . str_pad(\App\Models\Pago::max('pagos_id') + 1, 5, '0', STR_PAD_LEFT),
                        'est_codigo' => $estudiante->est_codigo,
                        'pfam_codigo' => $padre->pfam_codigo,
                        'prod_codigo' => 'MENSUALIDAD',
                        'pagos_precio' => $montoPago,
                        'pagos_nombres' => 'Mensualidad ' . $mesesNombres[$mes],
                        'pagos_usuario' => auth()->user()->us_codigo,
                        'pagos_descuento' => 0,
                        'concepto' => 'Mensualidad ' . $mesesNombres[$mes],
                        'tipo' => 1,
                        'pagos_fecha' => $fechaPago,
                        'pagos_sin_factura' => $sinFactura
                    ]);
                }
            }
            
            $registrados++;
        }
        
        $mensaje = "Registrados: $registrados | Anulados: {$saltados['anulados']} | Sin datos: {$saltados['sin_datos']} | Monto 0: {$saltados['monto_cero']} | Ya existe: {$saltados['ya_existe']}";
        
        return redirect()->route('inscripciones.index')->with('success', $mensaje);
    }
}
