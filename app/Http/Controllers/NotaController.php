<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaDetalle;
use App\Models\NotaPeriodo;
use App\Models\NotaDimension;
use App\Models\CursoMateriaDocente;
use App\Models\Estudiante;
use App\Models\ListaCurso;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Asistencia;
use App\Models\AsistenciaClase;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\RegistroEnfermeria;
use App\Models\CasoPsicopedagogia;
use App\Models\FechaFestiva;
use App\Models\MateriaGrupo;
use App\Models\ImportacionExcel;
use App\Services\ImportarExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $query = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->where('curmatdoc_estado', 1);

        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id) {
            $query->where('doc_codigo', $user->us_entidad_id);
        }
        if ($request->filled('cur_codigo')) {
            $curCodigos = is_array($request->cur_codigo) ? $request->cur_codigo : [$request->cur_codigo];
            $query->whereIn('cur_codigo', $curCodigos);
        }
        if ($request->filled('mat_codigo')) {
            $matCodigos = is_array($request->mat_codigo) ? $request->mat_codigo : [$request->mat_codigo];
            $query->whereIn('mat_codigo', $matCodigos);
        }
        if ($request->filled('buscar')) {
            $query->whereHas('docente', function($q) use ($request) {
                $q->where('doc_nombres', 'like', '%'.$request->buscar.'%')
                  ->orWhere('doc_apellidos', 'like', '%'.$request->buscar.'%');
            });
        }
        if ($request->filled('estado')) {
            $estadoFiltro = intval($request->estado);
            $query->whereHas('notas', fn($q) => $q->where('nota_estado', $estadoFiltro));
        }

        $asignaciones = $query->get();

        // Filtrar cursos y materias según el rol
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id) {
            $misAsignaciones = CursoMateriaDocente::where('curmatdoc_estado', 1)
                ->where('doc_codigo', $user->us_entidad_id)->get();
            $misCurCodigos = $misAsignaciones->pluck('cur_codigo')->unique();
            $misMatCodigos = $misAsignaciones->pluck('mat_codigo')->unique();
            $cursos   = \App\Models\Curso::visible()->whereIn('cur_codigo', $misCurCodigos)->orderBy('cur_nombre')->get();
            $materias = \App\Models\Materia::visible()->whereIn('mat_codigo', $misMatCodigos)->orderBy('mat_nombre')->get();
        } else {
            $cursos   = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
            $materias = \App\Models\Materia::visible()->orderBy('mat_nombre')->get();
        }

        // ── Datos del Dashboard ──────────────────────────────────────────────
        $periodoIds = $periodos->pluck('periodo_id');

        $statAprobadas  = Nota::whereIn('periodo_id', $periodoIds)->where('nota_estado', 2)->count();
        $statEnviadas   = Nota::whereIn('periodo_id', $periodoIds)->where('nota_estado', 1)->count();
        $statBorradores = Nota::whereIn('periodo_id', $periodoIds)->where('nota_estado', 0)->count();
        $statRechazadas = Nota::whereIn('periodo_id', $periodoIds)->where('nota_estado', 3)->count();

        // Ranking: top 10 estudiantes (solo notas aprobadas)
        $ranking = DB::table('colegio_notas as n')
            ->join('colegio_estudiantes as e', DB::raw('n.est_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('e.est_codigo COLLATE utf8mb4_unicode_ci'))
            ->join('colegio_cursos as c', DB::raw('e.cur_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('c.cur_codigo COLLATE utf8mb4_unicode_ci'))
            ->whereIn('n.periodo_id', $periodoIds)
            ->where('n.nota_estado', 2)
            ->where('n.nota_promedio_trimestral', '>', 0)
            ->select(
                'n.est_codigo',
                'e.est_nombres', 'e.est_apellidos',
                'c.cur_nombre',
                DB::raw('ROUND(AVG(n.nota_promedio_trimestral), 1) as promedio')
            )
            ->groupBy('n.est_codigo', 'e.est_nombres', 'e.est_apellidos', 'c.cur_nombre')
            ->orderByDesc('promedio')
            ->limit(10)
            ->get();

        // Estudiantes en peligro (promedio < 51)
        $enPeligro = DB::table('colegio_notas as n')
            ->join('colegio_estudiantes as e', DB::raw('n.est_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('e.est_codigo COLLATE utf8mb4_unicode_ci'))
            ->join('colegio_cursos as c', DB::raw('e.cur_codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('c.cur_codigo COLLATE utf8mb4_unicode_ci'))
            ->whereIn('n.periodo_id', $periodoIds)
            ->where('n.nota_estado', 2)
            ->where('n.nota_promedio_trimestral', '>', 0)
            ->select(
                'n.est_codigo',
                'e.est_nombres', 'e.est_apellidos',
                'c.cur_nombre',
                DB::raw('ROUND(AVG(n.nota_promedio_trimestral), 1) as promedio')
            )
            ->groupBy('n.est_codigo', 'e.est_nombres', 'e.est_apellidos', 'c.cur_nombre')
            ->having('promedio', '<', 51)
            ->orderBy('promedio')
            ->limit(10)
            ->get();

        // Todos los estudiantes (para selector de reporte personal)
        // Docentes: solo estudiantes de sus cursos asignados
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id) {
            $todosEstudiantes = Estudiante::visible()
                ->whereIn('cur_codigo', $misCurCodigos)
                ->select('est_codigo', 'est_nombres', 'est_apellidos', 'cur_codigo')
                ->orderBy('est_apellidos')->get();
        } else {
            $todosEstudiantes = Estudiante::visible()
                ->select('est_codigo', 'est_nombres', 'est_apellidos', 'cur_codigo')
                ->orderBy('est_apellidos')->get();
        }

        return view('notas.index', compact(
            'asignaciones', 'periodos', 'dimensiones', 'cursos', 'materias',
            'statAprobadas', 'statEnviadas', 'statBorradores', 'statRechazadas',
            'ranking', 'enPeligro', 'todosEstudiantes', 'gestion'
        ));
    }

    /**
     * Vista Rendimiento: matriz estudiantes × materias con promedio trimestral.
     */
    public function rendimiento(Request $request)
    {
        $gestion = date('Y');
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $cursos   = Curso::visible()->ordenado()->get();

        $cursoCod  = $request->input('cur_codigo');
        $periodoId = $request->input('periodo_id', optional($periodos->first())->periodo_id);
        $buscar    = trim((string) $request->input('buscar', ''));

        $estudiantes = collect();
        $materias    = collect();
        $matriz      = []; // [est_codigo][mat_codigo] => promedio
        $asignaciones = collect();

        if ($cursoCod && $periodoId) {
            $asignaciones = CursoMateriaDocente::with('materia')
                ->where('cur_codigo', $cursoCod)
                ->where('curmatdoc_estado', 1)
                ->get();

            $materias = $asignaciones->pluck('materia')->filter()->sortBy('mat_orden')->values();

            $eq = Estudiante::where('colegio_estudiantes.cur_codigo', $cursoCod)
                ->leftJoin('colegio_lista_curso', function($j) use ($gestion, $cursoCod){
                    $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo COLLATE utf8mb4_unicode_ci')
                      ->where('colegio_lista_curso.lista_gestion', $gestion)
                      ->where('colegio_lista_curso.cur_codigo', $cursoCod);
                })
                ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero');

            if ($buscar !== '') {
                $eq->where(function($w) use ($buscar) {
                    $w->where('colegio_estudiantes.est_nombres','like',"%{$buscar}%")
                      ->orWhere('colegio_estudiantes.est_apellidos','like',"%{$buscar}%");
                });
            }

            $estudiantes = $eq->orderByRaw('colegio_lista_curso.lista_numero IS NULL ASC')
                ->orderBy('colegio_lista_curso.lista_numero','asc')
                ->orderBy('colegio_estudiantes.est_apellidos')
                ->get();

            $curmatIds = $asignaciones->pluck('curmatdoc_id');
            $notas = Nota::whereIn('curmatdoc_id', $curmatIds)
                ->where('periodo_id', $periodoId)
                ->get();

            $asignByCurmat = $asignaciones->keyBy('curmatdoc_id');
            foreach ($notas as $n) {
                $matCod = optional($asignByCurmat->get($n->curmatdoc_id))->mat_codigo;
                if ($matCod) {
                    $matriz[$n->est_codigo][$matCod] = [
                        'promedio' => $n->nota_promedio_trimestral,
                        'curmatdoc_id' => $n->curmatdoc_id,
                        'nota_id' => $n->nota_id,
                    ];
                }
            }
        }

        return view('notas.rendimiento', compact(
            'periodos','cursos','cursoCod','periodoId','buscar',
            'estudiantes','materias','matriz','asignaciones','gestion'
        ));
    }

    public function calificar($curmatdocId, $periodoId)
    {
        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        // Verificar si estamos dentro del rango del periodo
        $hoy = now()->toDateString();
        $enRango = $hoy >= $periodo->periodo_fecha_inicio->toDateString() && $hoy <= $periodo->periodo_fecha_fin->toDateString();

        $estudiantes = Estudiante::where('colegio_estudiantes.cur_codigo', $asignacion->cur_codigo)
            ->leftJoin('colegio_lista_curso', function ($j) use ($gestion, $asignacion) {
                $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo')
                  ->where('colegio_lista_curso.lista_gestion', $gestion)
                  ->where('colegio_lista_curso.cur_codigo', $asignacion->cur_codigo);
            })
            ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero')
            ->orderBy('colegio_lista_curso.lista_numero')
            ->orderBy('colegio_estudiantes.est_apellidos')->get();

        $notasExistentes = Nota::with('detalles')
            ->where('curmatdoc_id', $curmatdocId)
            ->where('periodo_id', $periodoId)->get()->keyBy('est_codigo');

        $estadoNotas = $notasExistentes->isNotEmpty() ? $notasExistentes->first()->nota_estado : 0;
        $esEditable = $enRango && in_array($estadoNotas, [0, 3]) || !$notasExistentes->count();

        // Datos de auditoría para mostrar al docente
        $notaInfo = $notasExistentes->isNotEmpty() ? $notasExistentes->first() : null;
        $observacionAdmin = $notaInfo->nota_observacion ?? null;
        $fechaAprobacion = $notaInfo->nota_fecha_aprobacion ?? null;
        $aprobadoPor = null;
        if ($notaInfo && $notaInfo->nota_aprobado_por) {
            $aprobadoPor = \App\Models\User::find($notaInfo->nota_aprobado_por);
        }

        return view('notas.calificar', compact(
            'asignacion', 'periodo', 'dimensiones', 'estudiantes',
            'notasExistentes', 'estadoNotas', 'enRango', 'esEditable',
            'observacionAdmin', 'fechaAprobacion', 'aprobadoPor'
        ));
    }

    public function guardar(Request $request)
    {
        $curmatdocId = $request->input('curmatdoc_id');
        $periodoId = $request->input('periodo_id');
        $notasInput = $request->input('notas', []);
        $accion = $request->input('accion', 'guardar');

        $asignacion = CursoMateriaDocente::findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);

        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            abort(403);
        }

        // Validar rango de fechas
        $hoy = now()->toDateString();
        if ($hoy < $periodo->periodo_fecha_inicio->toDateString() || $hoy > $periodo->periodo_fecha_fin->toDateString()) {
            return back()->with('error', 'No se pueden registrar notas fuera del rango del periodo (' . $periodo->periodo_fecha_inicio->format('d/m/Y') . ' - ' . $periodo->periodo_fecha_fin->format('d/m/Y') . ')');
        }

        $gestion = $periodo->periodo_gestion;
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        foreach ($notasInput as $estCodigo => $dimData) {
            $notaData = ['nota_estado' => $accion === 'enviar' ? 1 : 0];
            if ($accion === 'enviar') {
                $notaData['nota_enviado_por'] = auth()->user()->us_id;
                $notaData['nota_fecha_envio'] = now();
            } else {
                $notaData['nota_guardado_por'] = auth()->user()->us_id;
                $notaData['nota_fecha_guardado'] = now();
            }

            $nota = Nota::updateOrCreate(
                ['periodo_id' => $periodoId, 'curmatdoc_id' => $curmatdocId, 'est_codigo' => $estCodigo],
                $notaData
            );

            $promedioTrimestral = 0;

            foreach ($dimensiones as $dim) {
                $valores = $dimData[$dim->dimension_id] ?? [];
                $suma = 0;
                $count = 0;

                for ($col = 1; $col <= $dim->dimension_columnas; $col++) {
                    $val = isset($valores[$col]) && $valores[$col] !== '' ? floatval($valores[$col]) : null;
                    NotaDetalle::updateOrCreate(
                        ['nota_id' => $nota->nota_id, 'dimension_id' => $dim->dimension_id, 'columna_num' => $col],
                        ['detalle_valor' => $val ?? 0]
                    );
                    if ($val !== null && $val > 0) {
                        $suma += $val;
                        $count++;
                    }
                }

                // Promedio: si hay notas, promedio de las ingresadas; si solo 1 columna, es el valor directo
                $promDim = $count > 0 ? ($dim->dimension_columnas == 1 ? $suma : $suma / $count) : 0;
                // Acumula con decimales (decimal(5,2)). El redondeo se aplica solo al mostrar.
                $promedioTrimestral += $promDim;
            }

            $nota->update([
                'nota_promedio_trimestral' => round($promedioTrimestral, 2)
            ]);
        }

        $msg = $accion === 'enviar' ? 'Notas enviadas para aprobación' : 'Notas guardadas como borrador';
        return redirect()->route('notas.calificar', [$curmatdocId, $periodoId])->with('success', $msg);
    }

    public function aprobar(Request $request, $curmatdocId, $periodoId)
    {
        $accion = $request->input('accion');
        Nota::where('curmatdoc_id', $curmatdocId)->where('periodo_id', $periodoId)->update([
            'nota_estado' => $accion === 'aprobar' ? 2 : 3,
            'nota_fecha_aprobacion' => now(),
            'nota_aprobado_por' => auth()->user()->us_id,
            'nota_observacion' => $request->input('observacion'),
        ]);
        $msg = $accion === 'aprobar' ? 'Notas aprobadas exitosamente' : 'Notas rechazadas';
        return redirect()->route('notas.calificar', [$curmatdocId, $periodoId])->with('success', $msg);
    }

    public function configuracion()
    {
        $gestion = date('Y');
        $periodos = NotaPeriodo::where('periodo_gestion', $gestion)->orderBy('periodo_numero')->get();
        $dimensiones = NotaDimension::where('dimension_gestion', $gestion)->orderBy('dimension_orden')->get();
        return view('notas.configuracion', compact('periodos', 'dimensiones', 'gestion'));
    }

    public function guardarPeriodo(Request $request)
    {
        $request->validate([
            'periodo_nombre' => 'required|max:50',
            'periodo_numero' => 'required|integer|min:1',
            'periodo_fecha_inicio' => 'required|date',
            'periodo_fecha_fin' => 'required|date|after:periodo_fecha_inicio',
        ]);
        NotaPeriodo::updateOrCreate(
            ['periodo_id' => $request->periodo_id],
            $request->only('periodo_nombre', 'periodo_numero', 'periodo_fecha_inicio', 'periodo_fecha_fin', 'periodo_gestion', 'periodo_estado')
        );
        return redirect()->route('notas.configuracion')->with('success', 'Periodo guardado');
    }

    public function eliminarPeriodo($id)
    {
        if (Nota::where('periodo_id', $id)->exists()) {
            return back()->with('error', 'No se puede eliminar, tiene notas asociadas');
        }
        NotaPeriodo::findOrFail($id)->delete();
        return redirect()->route('notas.configuracion')->with('success', 'Periodo eliminado');
    }

    public function guardarDimension(Request $request)
    {
        $request->validate([
            'dimension_nombre' => 'required|max:50',
            'dimension_valor_max' => 'required|integer|min:1|max:100',
            'dimension_columnas' => 'required|integer|min:1|max:10',
        ]);
        NotaDimension::updateOrCreate(
            ['dimension_id' => $request->dimension_id],
            $request->only('dimension_nombre', 'dimension_valor_max', 'dimension_columnas', 'dimension_orden', 'dimension_gestion', 'dimension_estado')
        );
        return redirect()->route('notas.configuracion')->with('success', 'Dimensión guardada');
    }

    public function eliminarDimension($id)
    {
        NotaDimension::findOrFail($id)->delete();
        return redirect()->route('notas.configuracion')->with('success', 'Dimensión eliminada');
    }

    // ═══════════════════════════════════════════════════════════════
    // REPORTE VALORACIÓN TRIMESTRAL (formato Excel)
    // ═══════════════════════════════════════════════════════════════

    public function reporteValoracion($curmatdocId, $periodoId)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($curmatdocId);
        $periodo = NotaPeriodo::findOrFail($periodoId);
        $gestion = $periodo->periodo_gestion;
        $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

        $estudiantes = Estudiante::where('colegio_estudiantes.cur_codigo', $asignacion->cur_codigo)
            ->leftJoin('colegio_lista_curso', function ($j) use ($gestion, $asignacion) {
                $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo')
                  ->where('colegio_lista_curso.lista_gestion', $gestion)
                  ->where('colegio_lista_curso.cur_codigo', $asignacion->cur_codigo);
            })
            ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero')
            ->orderBy('colegio_lista_curso.lista_numero')
            ->orderBy('colegio_estudiantes.est_apellidos')->get();

        $notasExistentes = Nota::with('detalles')
            ->where('curmatdoc_id', $curmatdocId)
            ->where('periodo_id', $periodoId)->get()->keyBy('est_codigo');

        $data = [];
        foreach ($estudiantes as $i => $est) {
            $nota = $notasExistentes[$est->est_codigo] ?? null;
            $detallesMap = [];
            if ($nota) {
                foreach ($nota->detalles as $det) {
                    $detallesMap[$det->dimension_id][$det->columna_num] = $det->detalle_valor;
                }
            }

            $fila = [
                'numero' => $est->lista_numero ?? ($i + 1),
                'nombre' => $est->est_apellidos . ' ' . $est->est_nombres,
                'dimensiones' => [],
                'promedio_trimestral' => $nota->nota_promedio_trimestral ?? 0,
                'rango' => '',
            ];

            foreach ($dimensiones as $dim) {
                $valores = [];
                $suma = 0; $count = 0;
                for ($c = 1; $c <= $dim->dimension_columnas; $c++) {
                    $val = $detallesMap[$dim->dimension_id][$c] ?? 0;
                    $valores[$c] = $val;
                    if ($val > 0) { $suma += $val; $count++; }
                }
                $prom = $count > 0 ? ($dim->dimension_columnas == 1 ? $suma : round($suma / $count)) : 0;
                $fila['dimensiones'][$dim->dimension_id] = [
                    'valores' => $valores,
                    'promedio' => $prom,
                ];
            }

            // Rango según sistema boliviano
            $pt = $fila['promedio_trimestral'];
            if ($pt < 20) $fila['rango'] = '';
            elseif ($pt < 51) $fila['rango'] = 'ED';
            elseif ($pt < 67) $fila['rango'] = 'DA';
            elseif ($pt < 85) $fila['rango'] = 'DO';
            else $fila['rango'] = 'DP';

            $data[] = $fila;
        }

        $pdf = Pdf::loadView('notas.reporte-valoracion-pdf', compact(
            'asignacion', 'periodo', 'dimensiones', 'data', 'gestion'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('valoracion-' . $asignacion->cur_codigo . '-' . $asignacion->mat_codigo . '-T' . $periodo->periodo_numero . '.pdf');
    }

    // ═══════════════════════════════════════════════════════════════
    // IMPORTAR EXCEL
    // ═══════════════════════════════════════════════════════════════

    public function importarExcel(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'curmatdoc_id' => 'required|integer',
            'periodo_id' => 'required|integer',
            'tipo_importacion' => 'required|in:notas,asistencia,ambos',
        ]);

        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($request->curmatdoc_id);
        $periodo = NotaPeriodo::findOrFail($request->periodo_id);
        $gestion = $periodo->periodo_gestion;
        $trimestre = $periodo->periodo_numero;
        $tipo = $request->tipo_importacion;

        // Verificar permisos
        $user = auth()->user();
        if ($user->us_entidad_tipo === 'docente' && $user->us_entidad_id && $user->us_entidad_id !== $asignacion->doc_codigo) {
            return back()->with('error', 'No tiene permisos para importar en esta asignación.');
        }

        // Guardar archivo temporal
        $archivo = $request->file('archivo');
        $nombreArchivo = 'import_' . time() . '_' . $archivo->getClientOriginalName();
        $rutaArchivo = $archivo->storeAs('imports', $nombreArchivo, 'local');
        $rutaCompleta = storage_path('app/' . $rutaArchivo);

        try {
            $service = new ImportarExcelService();
            $service->cargar($rutaCompleta);

            // Validar estructura del Excel
            $erroresEstructura = $service->validarEstructura($trimestre, $tipo);
            if (!empty($erroresEstructura)) {
                @unlink($rutaCompleta);
                return back()->with('error', 'Error en estructura del Excel: ' . implode(' | ', $erroresEstructura));
            }

            // Validar configuración del sistema
            if (in_array($tipo, ['notas', 'ambos'])) {
                $erroresConfig = $service->validarConfiguracion($gestion);
                if (!empty($erroresConfig)) {
                    @unlink($rutaCompleta);
                    return back()->with('error', 'Error de configuración: ' . implode(' | ', $erroresConfig));
                }
            }

            // Parsear datos
            $data = [];
            if (in_array($tipo, ['notas', 'ambos'])) {
                $data['notas'] = $service->parsearNotas($trimestre, $asignacion->cur_codigo, $gestion);
            }
            if (in_array($tipo, ['asistencia', 'ambos'])) {
                $data['asistencia'] = $service->parsearAsistencia($trimestre, $asignacion->cur_codigo);
            }

            $resumen = $service->getResumen();
            $errores = $service->getErrores();

            // Guardar como borrador de importación
            $importacion = ImportacionExcel::create([
                'curmatdoc_id' => $asignacion->curmatdoc_id,
                'periodo_id' => $periodo->periodo_id,
                'import_tipo' => $tipo,
                'import_data' => $data,
                'import_errores' => $errores,
                'import_resumen' => $resumen,
                'import_estado' => 0,
                'import_usuario_id' => auth()->user()->us_id,
                'import_archivo' => $nombreArchivo,
                'import_fecha' => now(),
            ]);

            return redirect()->route('notas.importar-preview', $importacion->import_id);

        } catch (\Exception $e) {
            @unlink($rutaCompleta);
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    public function importarPreview($importId)
    {
        $importacion = ImportacionExcel::findOrFail($importId);

        if ($importacion->import_estado != 0) {
            return redirect()->route('notas.calificar', [$importacion->curmatdoc_id, $importacion->periodo_id])
                ->with('error', 'Esta importación ya fue procesada.');
        }

        // Verificar permisos
        if ($importacion->import_usuario_id != auth()->user()->us_id && auth()->user()->rol_id != 1) {
            abort(403);
        }

        $asignacion = CursoMateriaDocente::with(['curso', 'materia', 'docente'])->findOrFail($importacion->curmatdoc_id);
        $periodo = NotaPeriodo::findOrFail($importacion->periodo_id);
        $dimensiones = NotaDimension::activo()->gestion($periodo->periodo_gestion)->orderBy('dimension_orden')->get();

        return view('notas.importar-preview', compact('importacion', 'asignacion', 'periodo', 'dimensiones'));
    }

    public function importarConfirmar($importId)
    {
        $importacion = ImportacionExcel::findOrFail($importId);

        if ($importacion->import_estado != 0) {
            return redirect()->route('notas.calificar', [$importacion->curmatdoc_id, $importacion->periodo_id])
                ->with('error', 'Esta importación ya fue procesada.');
        }

        if ($importacion->import_usuario_id != auth()->user()->us_id && auth()->user()->rol_id != 1) {
            abort(403);
        }

        $asignacion = CursoMateriaDocente::findOrFail($importacion->curmatdoc_id);
        $periodo = NotaPeriodo::findOrFail($importacion->periodo_id);
        $gestion = $periodo->periodo_gestion;
        $data = $importacion->import_data;
        $tipo = $importacion->import_tipo;

        DB::beginTransaction();
        try {
            // ── Importar NOTAS ──
            if (in_array($tipo, ['notas', 'ambos']) && !empty($data['notas'])) {
                $dimensiones = NotaDimension::activo()->gestion($gestion)->orderBy('dimension_orden')->get();

                foreach ($data['notas'] as $notaEst) {
                    $nota = Nota::updateOrCreate(
                        ['periodo_id' => $periodo->periodo_id, 'curmatdoc_id' => $asignacion->curmatdoc_id, 'est_codigo' => $notaEst['est_codigo']],
                        [
                            'nota_estado' => 0, // Borrador
                            'nota_guardado_por' => auth()->user()->us_id,
                            'nota_fecha_guardado' => now(),
                        ]
                    );

                    $promedioTrimestral = 0;

                    foreach ($notaEst['dimensiones'] as $dimId => $dimData) {
                        $dim = $dimensiones->firstWhere('dimension_id', $dimId);
                        if (!$dim) continue;

                        $valores = $dimData['valores'] ?? [];
                        $suma = 0;
                        $count = 0;

                        for ($col = 1; $col <= $dim->dimension_columnas; $col++) {
                            $val = $valores[$col] ?? 0;
                            NotaDetalle::updateOrCreate(
                                ['nota_id' => $nota->nota_id, 'dimension_id' => $dimId, 'columna_num' => $col],
                                ['detalle_valor' => $val]
                            );
                            if ($val > 0) { $suma += $val; $count++; }
                        }

                        $promDim = $count > 0 ? ($dim->dimension_columnas == 1 ? $suma : $suma / $count) : 0;
                        $promedioTrimestral += $promDim;
                    }

                    $nota->update(['nota_promedio_trimestral' => round($promedioTrimestral, 2)]);
                }
            }

            // ── Importar ASISTENCIA ──
            if (in_array($tipo, ['asistencia', 'ambos']) && !empty($data['asistencia'])) {
                foreach ($data['asistencia'] as $asisEst) {
                    foreach ($asisEst['registros'] as $reg) {
                        AsistenciaClase::updateOrCreate(
                            [
                                'curmatdoc_id' => $asignacion->curmatdoc_id,
                                'periodo_id' => $periodo->periodo_id,
                                'est_codigo' => $asisEst['est_codigo'],
                                'asiscl_fecha' => $reg['fecha'],
                            ],
                            [
                                'asiscl_estado' => $reg['estado'],
                                'asiscl_registrado_por' => auth()->user()->us_id,
                            ]
                        );
                    }
                }
            }

            $importacion->update([
                'import_estado' => 1,
                'import_fecha_confirmacion' => now(),
            ]);

            DB::commit();

            // Limpiar archivo
            $rutaArchivo = storage_path('app/imports/' . $importacion->import_archivo);
            @unlink($rutaArchivo);

            $msg = 'Importación completada exitosamente.';
            if (in_array($tipo, ['notas', 'ambos'])) $msg .= ' Notas guardadas como BORRADOR.';
            if (in_array($tipo, ['asistencia', 'ambos'])) $msg .= ' Asistencia registrada.';

            return redirect()->route('notas.calificar', [$asignacion->curmatdoc_id, $periodo->periodo_id])
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('notas.importar-preview', $importId)
                ->with('error', 'Error al confirmar importación: ' . $e->getMessage());
        }
    }

    public function importarCancelar($importId)
    {
        $importacion = ImportacionExcel::findOrFail($importId);

        if ($importacion->import_usuario_id != auth()->user()->us_id && auth()->user()->rol_id != 1) {
            abort(403);
        }

        $curmatdocId = $importacion->curmatdoc_id;
        $periodoId = $importacion->periodo_id;

        // Limpiar archivo
        $rutaArchivo = storage_path('app/imports/' . $importacion->import_archivo);
        @unlink($rutaArchivo);

        $importacion->update(['import_estado' => 2]);

        return redirect()->route('notas.calificar', [$curmatdocId, $periodoId])
            ->with('success', 'Importación cancelada.');
    }

    // ═══════════════════════════════════════════════════════════════
    // REPORTES
    // ═══════════════════════════════════════════════════════════════

    private function getEstudiantesOrdenados($curCodigo, $gestion)
    {
        $lista = ListaCurso::where('cur_codigo', $curCodigo)
            ->where('lista_gestion', $gestion)->pluck('lista_numero', 'est_codigo');

        // Incluye retirados; en los PDFs se renderizan en rojo con badge RETIRADO
        $estudiantes = Estudiante::where('cur_codigo', $curCodigo)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get();

        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();
        }
        return [$estudiantes, $lista];
    }

    private function getMateriasDelCurso($curCodigo)
    {
        return CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $curCodigo)->where('curmatdoc_estado', 1)
            ->get()->sortBy(fn($cmd) => $cmd->materia->mat_orden ?? 999)
            ->unique('mat_codigo');
    }

    private function getNotaPromedio($estCodigo, $curCodigo, $matCodigo, $periodoId)
    {
        $nota = Nota::where('est_codigo', $estCodigo)
            ->where('periodo_id', $periodoId)
            ->where('nota_estado', 2)
            ->whereHas('cursoMateriaDocente', fn($q) => $q->where('cur_codigo', $curCodigo)->where('mat_codigo', $matCodigo))
            ->first();
        return $nota ? round($nota->nota_promedio_trimestral) : 0;
    }

    /**
     * Calcula días hábiles (lun-vie) en un rango, excluyendo festivos.
     */
    private function diasHabilesRango($fechaInicio, $fechaFin, $year)
    {
        $feriados = FechaFestiva::activo()->feriado()
            ->whereYear('festivo_fecha', $year)
            ->pluck('festivo_fecha')->map(fn($f) => $f->format('Y-m-d'))->toArray();

        $dias = 0;
        $current = Carbon::parse($fechaInicio)->copy();
        $fin = Carbon::parse($fechaFin);
        while ($current <= $fin) {
            if ($current->isWeekday() && !in_array($current->format('Y-m-d'), $feriados)) {
                $dias++;
            }
            $current->addDay();
        }
        return $dias;
    }

    /**
     * Asistencia institucional por trimestre usando las fechas exactas del periodo.
     * Cuenta solo lun-vie, excluye festivos.
     */
    private function getAsistenciaTrimestreEst($estCodigo, $periodo, $year)
    {
        $fechaInicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
        $fechaFin = $periodo->periodo_fecha_fin->format('Y-m-d');

        $totalDiasHabiles = $this->diasHabilesRango($fechaInicio, $fechaFin, $year);

        $asis = Asistencia::where('estud_codigo', $estCodigo)
            ->whereBetween('asis_fecha', [$fechaInicio, $fechaFin])
            ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
            ->distinct('asis_fecha')->count('asis_fecha');

        $perm = Permiso::where('estud_codigo', $estCodigo)->where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '>=', $fechaInicio)
            ->where('permiso_fecha_inicio', '<=', $fechaFin)
            ->count();

        $atr = Atraso::where('estud_codigo', $estCodigo)
            ->whereBetween('atraso_fecha', [$fechaInicio, $fechaFin])
            ->whereRaw('DAYOFWEEK(atraso_fecha) BETWEEN 2 AND 6')
            ->count();

        $falt = max(0, $totalDiasHabiles - $asis - $perm);

        return [
            'dt' => $asis,
            'ta' => $atr,
            'tl' => $perm,
            'tf' => $falt,
            'total' => $totalDiasHabiles,
        ];
    }

    private function getEnfermeriaTrimestreEst($estCodigo, $periodo)
    {
        $higiene = RegistroEnfermeria::activo()->where('est_codigo', $estCodigo)
            ->where('enf_tipo_persona', 'ESTUDIANTE')
            ->where('enf_dx_detalle', 'LIKE', '%HIGIENE%')
            ->whereBetween('enf_fecha', [$periodo->periodo_fecha_inicio, $periodo->periodo_fecha_fin])->count();
        $atencion = RegistroEnfermeria::activo()->where('est_codigo', $estCodigo)
            ->where('enf_tipo_persona', 'ESTUDIANTE')
            ->where('enf_dx_detalle', 'NOT LIKE', '%HIGIENE%')
            ->whereBetween('enf_fecha', [$periodo->periodo_fecha_inicio, $periodo->periodo_fecha_fin])->count();
        return compact('higiene', 'atencion');
    }

    private function getPsicoTrimestreEst($estCodigo, $periodo)
    {
        $llamadas = CasoPsicopedagogia::activo()->where('est_codigo', $estCodigo)
            ->whereBetween('psico_fecha', [$periodo->periodo_fecha_inicio, $periodo->periodo_fecha_fin])->count();
        $compromisosSi = CasoPsicopedagogia::activo()->where('est_codigo', $estCodigo)
            ->where('psico_tipo_acuerdo', '!=', 'NINGUNO')
            ->whereBetween('psico_fecha', [$periodo->periodo_fecha_inicio, $periodo->periodo_fecha_fin])->count();
        $compromisosNo = CasoPsicopedagogia::activo()->where('est_codigo', $estCodigo)
            ->where('psico_tipo_acuerdo', 'NINGUNO')
            ->whereBetween('psico_fecha', [$periodo->periodo_fecha_inicio, $periodo->periodo_fecha_fin])->count();
        return ['llamadas_si' => $llamadas, 'llamadas_no' => 0, 'compromisos_si' => $compromisosSi, 'compromisos_no' => $compromisosNo];
    }

    /**
     * REPORTE 1: Personal del Estudiante (boletín individual)
     */
    public function reportePersonal(Request $request)
    {
        $request->validate(['est_codigo' => 'required']);
        $gestion = $request->input('gestion', date('Y'));

        $estudiante = Estudiante::where('est_codigo', $request->est_codigo)->with('curso')->firstOrFail();
        $curso = $estudiante->curso;
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $lista = ListaCurso::where('cur_codigo', $curso->cur_codigo)
            ->where('lista_gestion', $gestion)->where('est_codigo', $estudiante->est_codigo)->first();
        $nroLista = $lista->lista_numero ?? '-';

        $asignaciones = $this->getMateriasDelCurso($curso->cur_codigo);

        // Agrupar materias por campo
        $materiasPorCampo = $asignaciones->groupBy(fn($cmd) => $cmd->materia->mat_campo ?: 'SIN CAMPO');

        // Notas por materia y periodo
        $notasData = [];
        foreach ($asignaciones as $cmd) {
            $mat = $cmd->mat_codigo;
            $notasData[$mat] = ['nombre' => $cmd->materia->mat_nombre, 'trimestres' => [], 'promedio' => 0];
            $suma = 0; $count = 0;
            foreach ($periodos as $p) {
                $val = $this->getNotaPromedio($estudiante->est_codigo, $curso->cur_codigo, $mat, $p->periodo_id);
                $notasData[$mat]['trimestres'][$p->periodo_numero] = $val;
                if ($val > 0) { $suma += $val; $count++; }
            }
            $notasData[$mat]['promedio'] = $count > 0 ? round($suma / $count) : 0;
        }

        // Promedios por campo
        $promediosCampo = [];
        foreach ($materiasPorCampo as $campo => $cmds) {
            foreach ($periodos as $p) {
                $s = 0; $c = 0;
                foreach ($cmds as $cmd) {
                    $v = $notasData[$cmd->mat_codigo]['trimestres'][$p->periodo_numero] ?? 0;
                    if ($v > 0) { $s += $v; $c++; }
                }
                $promediosCampo[$campo][$p->periodo_numero] = $c > 0 ? round($s / $c) : 0;
            }
            $vals = array_filter(array_values($promediosCampo[$campo]));
            $promediosCampo[$campo]['anual'] = count($vals) > 0 ? round(array_sum($vals) / count($vals)) : 0;
        }

        // Asistencia, enfermería, psicopedagogía por trimestre
        $year = intval($gestion);
        $asistData = []; $enfData = []; $psicoData = [];
        foreach ($periodos as $p) {
            $asistData[$p->periodo_numero] = $this->getAsistenciaTrimestreEst($estudiante->est_codigo, $p, $year);
            $enfData[$p->periodo_numero] = $this->getEnfermeriaTrimestreEst($estudiante->est_codigo, $p);
            $psicoData[$p->periodo_numero] = $this->getPsicoTrimestreEst($estudiante->est_codigo, $p);
        }

        // Grupos derivados de mat_campo: cada campo (área) es un grupo natural.
        [$gruposActivos, $gruposMap] = $this->buildGruposPorCampo();

        $pdf = Pdf::loadView('notas.reporte-personal-pdf', compact(
            'estudiante', 'curso', 'periodos', 'materiasPorCampo', 'notasData',
            'promediosCampo', 'asistData', 'enfData', 'psicoData', 'nroLista', 'gestion',
            'gruposMap', 'gruposActivos'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('boletin-' . $estudiante->est_codigo . '.pdf');
    }

    /**
     * REPORTE 2: Centralizador (todas las materias de un curso, por trimestre o anual)
     */
    public function reporteCentralizador(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);
        $gestion = $request->input('gestion', date('Y'));
        $year = intval($gestion);
        $periodoId = $request->input('periodo_id');

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();
        $periodos = $periodoId
            ? NotaPeriodo::where('periodo_id', $periodoId)->get()
            : NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        [$estudiantes, $lista] = $this->getEstudiantesOrdenados($curso->cur_codigo, $gestion);
        $asignaciones = $this->getMateriasDelCurso($curso->cur_codigo);

        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes en ' . $curso->cur_nombre);

        // Construir datos: por cada estudiante, por cada materia, los 3 trimestres + promedio
        $data = [];
        foreach ($estudiantes as $est) {
            $fila = ['estudiante' => $est, 'materias' => [], 'suma' => 0, 'promedio' => 0];
            $sumaGeneral = 0; $countGeneral = 0;

            foreach ($asignaciones as $cmd) {
                $mat = $cmd->mat_codigo;
                $trimestres = [];
                $sumaMat = 0; $countMat = 0;
                foreach ($periodos as $p) {
                    $val = $this->getNotaPromedio($est->est_codigo, $curso->cur_codigo, $mat, $p->periodo_id);
                    $trimestres[$p->periodo_numero] = $val;
                    if ($val > 0) { $sumaMat += $val; $countMat++; }
                }
                $promMat = $countMat > 0 ? round($sumaMat / $countMat, 1) : 0;
                $fila['materias'][$mat] = ['trimestres' => $trimestres, 'promedio' => $promMat];
                $sumaGeneral += $sumaMat;
                if ($promMat > 0) $countGeneral++;
            }

            $fila['suma'] = $sumaGeneral;
            $fila['promedio'] = $countGeneral > 0 ? round($sumaGeneral / ($countGeneral * $periodos->count()), 1) : 0;

            // Asistencia institucional (DT, TA, TL, TF)
            $asist = [];
            foreach ($periodos as $p) {
                $asist[$p->periodo_numero] = $this->getAsistenciaTrimestreEst($est->est_codigo, $p, $year);
            }
            $fila['asistencia'] = $asist;

            // Psicopedagogía
            $psico = [];
            foreach ($periodos as $p) {
                $psico[$p->periodo_numero] = $this->getPsicoTrimestreEst($est->est_codigo, $p);
            }
            $fila['psico'] = $psico;

            // Enfermería total
            $enfTotal = RegistroEnfermeria::activo()->where('est_codigo', $est->est_codigo)
                ->where('enf_tipo_persona', 'ESTUDIANTE')
                ->whereYear('enf_fecha', $year)->count();
            $fila['enfermeria'] = $enfTotal;

            $data[] = $fila;
        }

        // Grupos derivados de mat_campo: cada campo (área) es un grupo natural.
        [$gruposActivos, $gruposMap] = $this->buildGruposPorCampo();

        $pdf = Pdf::loadView('notas.reporte-centralizador-pdf', compact(
            'curso', 'periodos', 'asignaciones', 'data', 'lista', 'gestion',
            'gruposMap', 'gruposActivos'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('centralizador-' . $curso->cur_codigo . '.pdf');
    }

    /**
     * REPORTE 3: General de Notas (por trimestre o todos, con detalle de dimensiones)
     */
    public function reporteGeneral(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);
        $gestion = $request->input('gestion', date('Y'));
        $year = intval($gestion);
        $periodoId = $request->input('periodo_id');

        $curso = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();
        $periodos = $periodoId
            ? NotaPeriodo::where('periodo_id', $periodoId)->get()
            : NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        [$estudiantes, $lista] = $this->getEstudiantesOrdenados($curso->cur_codigo, $gestion);
        $asignaciones = $this->getMateriasDelCurso($curso->cur_codigo);

        if ($estudiantes->isEmpty()) return back()->with('error', 'No hay estudiantes en ' . $curso->cur_nombre);

        // Datos: por estudiante, por materia, por trimestre = nota + promedio materia
        $data = [];
        foreach ($estudiantes as $est) {
            $fila = ['estudiante' => $est, 'materias' => [], 'suma' => 0, 'promedio' => 0];
            $sumaTotal = 0; $countTotal = 0;

            foreach ($asignaciones as $cmd) {
                $mat = $cmd->mat_codigo;
                $trimestres = [];
                $sumaMat = 0; $countMat = 0;
                foreach ($periodos as $p) {
                    $val = $this->getNotaPromedio($est->est_codigo, $curso->cur_codigo, $mat, $p->periodo_id);
                    $trimestres[$p->periodo_numero] = $val;
                    if ($val > 0) { $sumaMat += $val; $countMat++; }
                }
                $promMat = $countMat > 0 ? round($sumaMat / $countMat, 1) : 0;
                $fila['materias'][$mat] = ['trimestres' => $trimestres, 'promedio' => $promMat];
                $sumaTotal += $sumaMat;
                if ($promMat > 0) $countTotal++;
            }

            $fila['suma'] = $sumaTotal;
            $fila['promedio'] = $countTotal > 0 ? round($sumaTotal / ($countTotal * $periodos->count()), 1) : 0;

            // Asistencia
            $asist = [];
            foreach ($periodos as $p) {
                $asist[$p->periodo_numero] = $this->getAsistenciaTrimestreEst($est->est_codigo, $p, $year);
            }
            $fila['asistencia'] = $asist;

            $data[] = $fila;
        }

        $esTrimestre = $periodoId ? true : false;
        $periodoNombre = $periodoId ? $periodos->first()->periodo_nombre : 'AÑO ESCOLAR';

        // Grupos derivados de mat_campo: cada campo (área) es un grupo natural.
        [$gruposActivos, $gruposMap] = $this->buildGruposPorCampo();

        $pdf = Pdf::loadView('notas.reporte-general-pdf', compact(
            'curso', 'periodos', 'asignaciones', 'data', 'lista', 'gestion',
            'esTrimestre', 'periodoNombre', 'gruposMap', 'gruposActivos'
        ))->setPaper('letter', 'landscape');

        return $pdf->stream('notas-general-' . $curso->cur_codigo . '.pdf');
    }

    /**
     * Construye los grupos de materias a partir de mat_campo.
     * Devuelve [Collection $gruposActivos, array $gruposMap (mat_codigo => grupo)].
     * Cada "grupo" es un objeto sintético con grupo_id, grupo_nombre, materias y materiasPromediables.
     */
    private function buildGruposPorCampo()
    {
        $materias = \App\Models\Materia::visible()
            ->whereNotNull('mat_campo')->where('mat_campo', '!=', '')
            ->orderBy('mat_orden')->get();

        $porCampo = $materias->groupBy(fn($m) => trim((string) $m->mat_campo));

        $grupos = collect();
        $map = [];
        foreach ($porCampo as $campo => $mats) {
            if ($mats->count() < 2) continue; // sólo campos con 2+ materias se consideran grupo
            $grupo = (object) [
                'grupo_id'             => 'campo_' . md5($campo),
                'grupo_nombre'         => $campo,
                'materias'             => $mats->values(),
                'materiasPromediables' => $mats->where('mat_promediable', 1)->values(),
            ];
            $grupos->push($grupo);
            foreach ($mats as $m) {
                $map[$m->mat_codigo] = $grupo;
            }
        }
        return [$grupos, $map];
    }
}
