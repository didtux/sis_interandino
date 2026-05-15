<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaPeriodo;
use App\Models\NotaDimension;
use App\Models\CursoMateriaDocente;
use App\Models\Estudiante;
use App\Models\ListaCurso;
use App\Models\Curso;
use App\Models\Asistencia;
use App\Models\Atraso;
use App\Models\Permiso;
use App\Models\RegistroEnfermeria;
use App\Models\CasoPsicopedagogia;
use App\Models\MateriaGrupo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaReporteController extends Controller
{
    // ─── Reporte Personal del Estudiante ─────────────────────────────────────
    public function personal(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required',
            'gestion'    => 'required|integer',
        ]);

        $gestion    = intval($request->gestion);
        $estudiante = Estudiante::with('curso')
            ->where('est_codigo', $request->est_codigo)
            ->firstOrFail();

        $periodos = NotaPeriodo::activo()->gestion($gestion)
            ->orderBy('periodo_numero')->get();

        $listaNumero = ListaCurso::where('est_codigo', $request->est_codigo)
            ->where('lista_gestion', $gestion)
            ->value('lista_numero');

        // Materias activas del curso
        $curmatdocs = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $estudiante->cur_codigo)
            ->where('curmatdoc_estado', 1)
            ->get();

        // Armar estructura: [curmatdoc_id] => [nombre, periodos[pn]=promedio, promedio_anual]
        $notasPorMateria = [];
        foreach ($curmatdocs as $cmd) {
            $notasPorMateria[$cmd->curmatdoc_id] = [
                'nombre'  => $cmd->materia->mat_nombre ?? '-',
                'periodos' => [],
                'promedio' => 0,
            ];
        }

        $notas = Nota::where('est_codigo', $request->est_codigo)
            ->whereIn('curmatdoc_id', $curmatdocs->pluck('curmatdoc_id'))
            ->whereHas('periodo', fn($q) => $q->where('periodo_gestion', $gestion))
            ->with('periodo')
            ->get();

        foreach ($notas as $nota) {
            $notasPorMateria[$nota->curmatdoc_id]['periodos'][$nota->periodo->periodo_numero]
                = $nota->nota_promedio_trimestral;
        }

        foreach ($notasPorMateria as &$mat) {
            $vals = array_filter($mat['periodos'], fn($v) => $v > 0);
            $mat['promedio'] = count($vals) > 0
                ? round(array_sum($vals) / count($vals), 0)
                : 0;
        }
        unset($mat);

        // ── Asistencia por periodo ──────────────────────────────────────────
        // Misma lógica que ConcejoController: periodos disjuntos, exclusión de fines de semana,
        // permisos solapados se reparten sin duplicar, faltas = días hábiles trabajados sin
        // presencia y sin permiso vigente.
        $periodosOrden = $periodos->sortBy('periodo_fecha_inicio')->values();
        $rangos = [];
        $lastEnd = null;
        foreach ($periodosOrden as $p) {
            $ini = \Carbon\Carbon::parse($p->periodo_fecha_inicio);
            if ($lastEnd && $ini->lessThanOrEqualTo($lastEnd)) {
                $ini = $lastEnd->copy()->addDay();
            }
            $finP = \Carbon\Carbon::parse($p->periodo_fecha_fin);
            if ($finP->lessThan($ini)) continue;
            $rangos[$p->periodo_numero] = ['inicio'=>$ini->format('Y-m-d'), 'fin'=>$finP->format('Y-m-d')];
            $lastEnd = $finP;
        }

        $vistosFaltas = []; $vistosDiasPerm = []; $vistosAtrasos = [];
        $asistenciaPorPeriodo = [];
        foreach ($rangos as $pn => $rg) {
            $inicio = $rg['inicio'];
            $fin    = $rg['fin'];

            $diasTrab = DB::table('colegio_asistencia')
                ->whereBetween('asis_fecha', [$inicio, $fin])
                ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
                ->select('asis_fecha')->distinct()->pluck('asis_fecha');

            $presFechas = DB::table('colegio_asistencia')
                ->where('estud_codigo', $request->est_codigo)
                ->whereBetween('asis_fecha', [$inicio, $fin])
                ->select('asis_fecha')->distinct()->pluck('asis_fecha');

            $atrasos = 0;
            $atrasosRows = DB::table('asistencia_atrasos')
                ->where('estud_codigo', $request->est_codigo)
                ->whereBetween('atraso_fecha', [$inicio, $fin])
                ->select('atraso_fecha','atraso_hora')->get();
            foreach ($atrasosRows as $a) {
                $key = (string)$a->atraso_fecha.'|'.(string)$a->atraso_hora;
                if (!isset($vistosAtrasos[$key])) { $vistosAtrasos[$key] = true; $atrasos++; }
            }

            // Cobertura de días con permiso (incluso permisos que comenzaron antes)
            $permisosCobertura = DB::table('asistencia_permisos')
                ->where('estud_codigo', $request->est_codigo)
                ->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fin)
                ->where('permiso_fecha_fin', '>=', $inicio)
                ->select('permiso_fecha_inicio','permiso_fecha_fin')->get();
            $diasTrabSet = collect($diasTrab)->map(fn($f)=>(string)$f)->flip();
            $licencias = 0;
            foreach ($permisosCobertura as $perm) {
                $cur = \Carbon\Carbon::parse(max($perm->permiso_fecha_inicio, $inicio));
                $end = \Carbon\Carbon::parse(min($perm->permiso_fecha_fin, $fin));
                while ($cur <= $end) {
                    $f = $cur->format('Y-m-d');
                    if ($diasTrabSet->has($f) && !isset($vistosDiasPerm[$f])) {
                        $vistosDiasPerm[$f] = true;
                        $licencias++;
                    }
                    $cur->addDay();
                }
            }

            $presSet = collect($presFechas)->map(fn($f)=>(string)$f)->flip();
            $faltas = 0;
            foreach ($diasTrab as $f) {
                $f = (string) $f;
                if (isset($vistosFaltas[$f])) continue;
                if ($presSet->has($f) || isset($vistosDiasPerm[$f])) continue;
                $dow = (int) date('w', strtotime($f));
                if ($dow === 0 || $dow === 6) continue;
                $vistosFaltas[$f] = true;
                $faltas++;
            }

            $asistenciaPorPeriodo[$pn] = [
                'atrasos'        => $atrasos,
                'licencias'      => $licencias,
                'faltas'         => $faltas,
                'diasTrabajados' => $diasTrab->count(),
            ];
        }

        // ── Enfermería por periodo ──────────────────────────────────────────
        $enfermeriaPorPeriodo = [];
        foreach ($periodos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');

            $registros = RegistroEnfermeria::where('est_codigo', $request->est_codigo)
                ->where('enf_tipo_persona', 'ESTUDIANTE')
                ->where('enf_estado', 1)
                ->whereBetween('enf_fecha', [$inicio, $fin])
                ->get();

            $enfermeriaPorPeriodo[$periodo->periodo_numero] = [
                'higiene'  => $registros->where('enf_dx_detalle', 'HIGIENE PERSONAL')->count(),
                'atencion' => $registros->where('enf_dx_detalle', 'ATENCIÓN MÉDICA')->count(),
            ];
        }

        // ── Control y Seguimiento (Psicopedagogía) por periodo ─────────────
        $controlPorPeriodo = [];
        foreach ($periodos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');

            $casos = CasoPsicopedagogia::where('est_codigo', $request->est_codigo)
                ->where('psico_estado', 1)
                ->whereBetween('psico_fecha', [$inicio, $fin])
                ->get();

            $controlPorPeriodo[$periodo->periodo_numero] = [
                'llamadas_si'     => $casos->count(),
                'compromisos_si'  => $casos->filter(fn($c) =>
                    !empty($c->psico_acuerdo))->count(),
            ];
        }

        // Grupos de materias
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'));
        $gruposActivos = collect($gruposMap)->unique('grupo_id')->values();

        $pdf = Pdf::loadView('notas.reporte-personal-pdf', compact(
            'estudiante', 'periodos', 'notasPorMateria',
            'asistenciaPorPeriodo', 'enfermeriaPorPeriodo',
            'controlPorPeriodo', 'gestion', 'listaNumero',
            'gruposMap', 'gruposActivos', 'curmatdocs'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('reporte-personal-' . $request->est_codigo . '-' . $gestion . '.pdf');
    }

    /**
     * Construye mapa de grupos: [mat_codigo => grupo sintético] basado en mat_campo.
     * Cada campo (área) se considera un grupo. Sólo se agrupa si hay 2+ materias
     * con el mismo campo dentro del set de mat_codigos del curso.
     */
    private function buildGruposMap($matCodigos)
    {
        $codigos = $matCodigos->toArray();
        $materias = \App\Models\Materia::whereIn('mat_codigo', $codigos)
            ->whereNotNull('mat_campo')->where('mat_campo', '!=', '')
            ->orderBy('mat_orden')->get();

        $porCampo = $materias->groupBy(fn($m) => trim((string) $m->mat_campo));

        // Construye un objeto por campo. Sólo grupos con 2+ materias en el curso.
        $gruposPorCampo = [];
        foreach ($porCampo as $campo => $mats) {
            if ($mats->count() < 2) continue;
            $gruposPorCampo[$campo] = (object) [
                'grupo_id'             => 'campo_' . md5($campo),
                'grupo_nombre'         => $campo,
                'materias'             => $mats->values(),
                'materiasPromediables' => $mats->where('mat_promediable', 1)->values(),
            ];
        }

        $map = [];
        foreach ($materias as $mat) {
            $campo = trim((string) $mat->mat_campo);
            if (isset($gruposPorCampo[$campo])) {
                $map[$mat->mat_codigo] = $gruposPorCampo[$campo];
            }
        }
        return $map;
    }

    /**
     * Calcula promedio de grupo para un estudiante en un periodo.
     * Promedio = suma de promedios de materias del grupo / cantidad de materias.
     */
    private function calcPromedioGrupo($grupo, $estCodigo, $notasMatrix, $curmatdocs, $periodoNum)
    {
        // Solo materias marcadas como promediables (detalle_promediable = 1)
        $matCodigosGrupo = $grupo->materiasPromediables->pluck('mat_codigo');
        $suma = 0;
        $count = 0;
        foreach ($curmatdocs as $cmd) {
            if ($matCodigosGrupo->contains($cmd->mat_codigo)) {
                $val = $notasMatrix[$estCodigo][$cmd->curmatdoc_id][$periodoNum] ?? 0;
                $suma += $val;
                $count++;
            }
        }
        return $count > 0 ? round($suma / $count, 0) : 0;
    }

    // ─── Reporte Centralizador ────────────────────────────────────────────────
    public function centralizador(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'gestion'    => 'required|integer',
        ]);

        $gestion = intval($request->gestion);
        $curso   = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();

        $periodos = NotaPeriodo::activo()->gestion($gestion)
            ->orderBy('periodo_numero')->get();

        $lista = ListaCurso::where('cur_codigo', $request->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        // Incluye retirados para que aparezcan con su número de lista y en rojo
        $estudiantes = Estudiante::where('cur_codigo', $request->cur_codigo)
            ->orderBy('est_apellidos')->get();

        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();
        }

        $curmatdocs = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $request->cur_codigo)
            ->where('curmatdoc_estado', 1)
            ->get();

        // Matriz: [est_codigo][curmatdoc_id][periodo_num] = nota_promedio
        $notasMatrix = [];
        Nota::whereIn('curmatdoc_id', $curmatdocs->pluck('curmatdoc_id'))
            ->whereHas('periodo', fn($q) => $q->where('periodo_gestion', $gestion))
            ->where('nota_estado', 2)
            ->with('periodo')
            ->get()
            ->each(function ($nota) use (&$notasMatrix) {
                $notasMatrix[$nota->est_codigo][$nota->curmatdoc_id][$nota->periodo->periodo_numero]
                    = $nota->nota_promedio_trimestral;
            });

        // Grupos de materias
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'));
        $gruposActivos = collect($gruposMap)->unique('grupo_id')->values();

        // Enfermería y Psicopedagogía por estudiante y periodo
        $enfPorEstudiante   = [];
        $psicoPorEstudiante = [];
        $estCodigos = $estudiantes->pluck('est_codigo');

        foreach ($periodos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');
            $pn     = $periodo->periodo_numero;

            RegistroEnfermeria::where('enf_tipo_persona', 'ESTUDIANTE')
                ->where('enf_estado', 1)
                ->whereBetween('enf_fecha', [$inicio, $fin])
                ->whereIn('est_codigo', $estCodigos)
                ->get()
                ->groupBy('est_codigo')
                ->each(function ($recs, $estCod) use (&$enfPorEstudiante, $pn) {
                    $enfPorEstudiante[$estCod][$pn] = $recs->count();
                });

            CasoPsicopedagogia::where('psico_estado', 1)
                ->whereBetween('psico_fecha', [$inicio, $fin])
                ->whereIn('est_codigo', $estCodigos)
                ->get()
                ->groupBy('est_codigo')
                ->each(function ($casos, $estCod) use (&$psicoPorEstudiante, $pn) {
                    $psicoPorEstudiante[$estCod][$pn] = $casos->count();
                });
        }

        $pdf = Pdf::loadView('notas.reporte-centralizador-pdf', compact(
            'curso', 'periodos', 'curmatdocs', 'estudiantes', 'notasMatrix',
            'lista', 'gestion', 'enfPorEstudiante', 'psicoPorEstudiante',
            'gruposMap', 'gruposActivos'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('centralizador-' . preg_replace('/\s+/', '_', $curso->cur_nombre) . '-' . $gestion . '.pdf');
    }

    // ─── Reporte General de Trimestres ───────────────────────────────────────
    public function general(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required',
            'gestion'    => 'required|integer',
        ]);

        $gestion = intval($request->gestion);
        $curso   = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();

        $periodos = NotaPeriodo::activo()->gestion($gestion)
            ->orderBy('periodo_numero')->get();

        $periodoFiltroId = $request->filled('periodo_id') ? intval($request->periodo_id) : null;
        $periodosActivos = $periodoFiltroId
            ? $periodos->where('periodo_id', $periodoFiltroId)->values()
            : $periodos;

        if ($periodosActivos->isEmpty()) {
            return back()->with('error', 'No se encontró el periodo seleccionado.');
        }

        $lista = ListaCurso::where('cur_codigo', $request->cur_codigo)
            ->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        // Incluye retirados (mostrados con su número de lista y en rojo)
        $estudiantes = Estudiante::where('cur_codigo', $request->cur_codigo)
            ->orderBy('est_apellidos')->get();

        if ($lista->isNotEmpty()) {
            $estudiantes = $estudiantes->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();
        }

        $curmatdocs = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $request->cur_codigo)
            ->where('curmatdoc_estado', 1)
            ->get();

        $dimensiones = NotaDimension::activo()->gestion($gestion)
            ->orderBy('dimension_orden')->get();

        // Matriz notas: [periodo_num][curmatdoc_id][est_codigo] = Nota(con detalles)
        $notasMatrix = [];
        Nota::with('detalles')
            ->whereIn('curmatdoc_id', $curmatdocs->pluck('curmatdoc_id'))
            ->whereIn('periodo_id', $periodosActivos->pluck('periodo_id'))
            ->with('periodo')
            ->get()
            ->each(function ($nota) use (&$notasMatrix) {
                $notasMatrix[$nota->periodo->periodo_numero][$nota->curmatdoc_id][$nota->est_codigo] = $nota;
            });

        // Asistencia por periodo y estudiante
        $asistenciaMatrix = [];
        foreach ($periodosActivos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');
            $pn     = $periodo->periodo_numero;

            $diasTrabajados = Asistencia::whereBetween('asis_fecha', [$inicio, $fin])
                ->select('asis_fecha')->distinct()->count();

            foreach ($estudiantes as $est) {
                $presencias = Asistencia::where('estud_codigo', $est->est_codigo)
                    ->whereBetween('asis_fecha', [$inicio, $fin])->count();

                $atrasos = Atraso::where('estud_codigo', $est->est_codigo)
                    ->whereBetween('atraso_fecha', [$inicio, $fin])->count();

                $licencias = Permiso::where('estud_codigo', $est->est_codigo)
                    ->where('permiso_estado', 1)
                    ->where('permiso_fecha_inicio', '<=', $fin)
                    ->where('permiso_fecha_fin', '>=', $inicio)->count();

                $faltas = max(0, $diasTrabajados - $presencias - $licencias);

                $asistenciaMatrix[$pn][$est->est_codigo] = compact(
                    'presencias', 'atrasos', 'licencias', 'faltas', 'diasTrabajados'
                );
            }
        }

        // Grupos de materias
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'));
        $gruposActivos = collect($gruposMap)->unique('grupo_id')->values();

        $pdf = Pdf::loadView('notas.reporte-general-pdf', compact(
            'curso', 'periodos', 'periodosActivos', 'curmatdocs',
            'estudiantes', 'dimensiones', 'notasMatrix',
            'lista', 'gestion', 'asistenciaMatrix',
            'gruposMap', 'gruposActivos'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('general-' . preg_replace('/\s+/', '_', $curso->cur_nombre) . '-' . $gestion . '.pdf');
    }

    /** Centralizador anual: 3 trimestres + promedio anual por curso */
    public function centralizadorAnual(Request $request)
    {
        $cursoCod = $request->input('curso');
        if (!$cursoCod) abort(400, 'Falta curso');
        $gestion = (int) $request->input('gestion', date('Y'));

        $curso     = Curso::where('cur_codigo', $cursoCod)->firstOrFail();
        $config    = DB::table('sistema_configuracion')->first();
        $periodos  = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $asignaciones = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $cursoCod)->where('curmatdoc_estado', 1)->get();
        $materias  = $asignaciones->pluck('materia')->filter()->sortBy('mat_orden')->values();

        $estudiantes = Estudiante::where('cur_codigo', $cursoCod)
            ->leftJoin('colegio_lista_curso', function($j) use ($gestion, $cursoCod){
                $j->whereRaw('colegio_estudiantes.est_codigo COLLATE utf8mb4_unicode_ci = colegio_lista_curso.est_codigo COLLATE utf8mb4_unicode_ci')
                  ->where('colegio_lista_curso.lista_gestion', $gestion)
                  ->where('colegio_lista_curso.cur_codigo', $cursoCod);
            })
            ->select('colegio_estudiantes.*', 'colegio_lista_curso.lista_numero')
            ->orderByRaw('colegio_lista_curso.lista_numero IS NULL ASC')
            ->orderBy('colegio_lista_curso.lista_numero')
            ->orderBy('colegio_estudiantes.est_apellidos')
            ->get();

        $curmatIds = $asignaciones->pluck('curmatdoc_id');
        $notas = Nota::whereIn('curmatdoc_id', $curmatIds)
            ->whereIn('periodo_id', $periodos->pluck('periodo_id'))->get();

        $asignByCurmat = $asignaciones->keyBy('curmatdoc_id');
        $matriz = []; // [est][mat][per] = promedio
        foreach ($notas as $n) {
            $matCod = optional($asignByCurmat->get($n->curmatdoc_id))->mat_codigo;
            if ($matCod) $matriz[$n->est_codigo][$matCod][$n->periodo_id] = $n->nota_promedio_trimestral;
        }

        $pdf = Pdf::loadView('notas.centralizador-anual-pdf', compact(
            'curso','config','periodos','materias','estudiantes','matriz','gestion'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('centralizador-anual-'.$cursoCod.'-'.$gestion.'.pdf');
    }

    /** Cuadro de Honor: por curso, nivel o colegio */
    public function cuadroHonor(Request $request)
    {
        $tipo      = $request->input('tipo', 'curso'); // curso | nivel | colegio
        $cursoCod  = $request->input('curso');
        $nivelIn   = $request->input('nivel');
        $periodoId = $request->input('periodo_id'); // opcional: trimestre específico
        $gestion   = (int) $request->input('gestion', date('Y'));
        $config    = DB::table('sistema_configuracion')->first();

        // Etiqueta del trimestre para el título
        $trimestreLabel = '';
        if ($periodoId) {
            $per = DB::table('notas_config_periodos')->where('periodo_id', $periodoId)->first();
            if ($per) {
                $trimestreLabel = ' — ' . ($per->periodo_nombre ?? ($per->periodo_numero.'° TRIMESTRE'));
            }
        } else {
            $trimestreLabel = ' — ANUAL';
        }

        // Si llega tipo=nivel sin nivel explícito pero con curso, deriva el nivel del curso
        if ($tipo === 'nivel' && !$nivelIn && $cursoCod) {
            $nivelIn = Curso::where('cur_codigo', $cursoCod)->value('cur_nivel');
        }

        // ── tipo = colegio: top 3 por curso, agrupado por curso ordenado ──
        if ($tipo === 'colegio') {
            $sqlCol = "
                SELECT t.* FROM (
                    SELECT e.est_codigo,
                           CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre,
                           c.cur_codigo, c.cur_nombre, c.cur_nivel, c.cur_orden,
                           ROUND(SUM(n.nota_promedio_trimestral), 2) AS suma,
                           ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio
                    FROM colegio_estudiantes e
                    JOIN colegio_cursos c ON c.cur_codigo COLLATE utf8mb4_unicode_ci = e.cur_codigo COLLATE utf8mb4_unicode_ci
                    JOIN colegio_notas n ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci
                    JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id
                    WHERE e.est_visible = 1
                      AND p.periodo_gestion = ?
            ";
            $paramsCol = [$gestion];
            if ($periodoId) { $sqlCol .= " AND n.periodo_id = ? "; $paramsCol[] = $periodoId; }
            $sqlCol .= "
                    GROUP BY e.est_codigo, e.est_apellidos, e.est_nombres, c.cur_codigo, c.cur_nombre, c.cur_nivel, c.cur_orden
                ) t
                ORDER BY t.cur_orden ASC, t.cur_nombre ASC, t.promedio DESC, t.suma DESC
            ";
            $allRows = DB::select($sqlCol, $paramsCol);

            // Agrupar por curso y tomar top 3 de cada uno
            $porCurso = [];
            foreach ($allRows as $r) {
                $key = $r->cur_codigo;
                if (!isset($porCurso[$key])) {
                    $porCurso[$key] = [
                        'cur_nombre' => $r->cur_nombre,
                        'cur_nivel'  => $r->cur_nivel,
                        'cur_orden'  => $r->cur_orden,
                        'rows'       => [],
                    ];
                }
                if (count($porCurso[$key]['rows']) < 3) {
                    $porCurso[$key]['rows'][] = $r;
                }
            }
            $titulo = 'CUADRO DE HONOR — INSTITUCIÓN' . $trimestreLabel;
            $pdf = Pdf::loadView('notas.cuadro-honor-institucional-pdf', compact('porCurso','titulo','config','gestion'))
                ->setPaper('letter');
            return $pdf->stream('cuadro-honor-colegio-'.$gestion.'.pdf');
        }

        // ── tipo = curso o nivel: ranking lineal ──
        $sql = "
            SELECT e.est_codigo,
                   CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre,
                   c.cur_nombre, c.cur_nivel,
                   ROUND(SUM(n.nota_promedio_trimestral), 2) AS suma,
                   ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio
            FROM colegio_estudiantes e
            JOIN colegio_cursos c ON c.cur_codigo COLLATE utf8mb4_unicode_ci = e.cur_codigo COLLATE utf8mb4_unicode_ci
            JOIN colegio_notas n ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci
            JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id
            WHERE e.est_visible = 1
              AND p.periodo_gestion = ?
        ";
        $params = [$gestion];
        if ($periodoId) { $sql .= " AND n.periodo_id = ? "; $params[] = $periodoId; }

        if ($tipo === 'curso' && $cursoCod) {
            $sql .= " AND e.cur_codigo = ? ";
            $params[] = $cursoCod;
        } elseif ($tipo === 'nivel' && $nivelIn) {
            $sql .= " AND c.cur_nivel = ? ";
            $params[] = $nivelIn;
        }
        $sql .= " GROUP BY e.est_codigo, e.est_apellidos, e.est_nombres, c.cur_nombre, c.cur_nivel
                  ORDER BY promedio DESC, suma DESC ";

        $rows = DB::select($sql, $params);

        // ── Si tipo=curso: calcular ranking comparativo del curso vs otros ──
        $rankingCursos = [];
        if ($tipo === 'curso' && $cursoCod) {
            $sqlRank = "
                SELECT c.cur_codigo, c.cur_nombre, c.cur_nivel, c.cur_orden,
                       ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio_curso,
                       COUNT(DISTINCT e.est_codigo) AS estudiantes
                FROM colegio_estudiantes e
                JOIN colegio_cursos c ON c.cur_codigo COLLATE utf8mb4_unicode_ci = e.cur_codigo COLLATE utf8mb4_unicode_ci
                JOIN colegio_notas n ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci
                JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id
                WHERE e.est_visible = 1 AND p.periodo_gestion = ?
            ";
            $paramsRank = [$gestion];
            if ($periodoId) { $sqlRank .= " AND n.periodo_id = ? "; $paramsRank[] = $periodoId; }
            $sqlRank .= " GROUP BY c.cur_codigo, c.cur_nombre, c.cur_nivel, c.cur_orden
                          ORDER BY promedio_curso DESC ";
            $rankingCursos = DB::select($sqlRank, $paramsRank);
        }

        $titulo = match($tipo) {
            'nivel'   => 'CUADRO DE HONOR — NIVEL ' . ($nivelIn ?? '') . $trimestreLabel,
            default   => 'CUADRO DE HONOR — ' . (Curso::where('cur_codigo', $cursoCod)->value('cur_nombre') ?? '') . $trimestreLabel,
        };

        $cursoActual = $cursoCod ? Curso::where('cur_codigo', $cursoCod)->first() : null;
        $trimestreNombre = '';
        if ($periodoId) {
            $per = DB::table('notas_config_periodos')->where('periodo_id', $periodoId)->first();
            $trimestreNombre = $per->periodo_nombre ?? (($per->periodo_numero ?? '') . '° TRIMESTRE');
        } else {
            $trimestreNombre = 'ANUAL';
        }

        $pdf = Pdf::loadView('notas.cuadro-honor-pdf', compact(
            'rows','rankingCursos','cursoCod','cursoActual','titulo','config','gestion','tipo','trimestreNombre'
        ))->setPaper('letter');
        return $pdf->stream('cuadro-honor-'.$tipo.'-'.$gestion.'.pdf');
    }

    /** Top 3 por curso */
    public function top3PorCurso(Request $request)
    {
        $gestion = (int) $request->input('gestion', date('Y'));
        $config  = DB::table('sistema_configuracion')->first();

        $sql = "
            SELECT cc.cur_codigo, cc.cur_nombre, cc.cur_orden,
                   e.est_codigo,
                   CONCAT(e.est_apellidos, ' ', e.est_nombres) AS nombre,
                   ROUND(SUM(n.nota_promedio_trimestral), 2) AS suma,
                   ROUND(AVG(n.nota_promedio_trimestral), 2) AS promedio
            FROM colegio_lista_curso lc
            JOIN colegio_estudiantes e ON e.est_codigo COLLATE utf8mb4_unicode_ci = lc.est_codigo COLLATE utf8mb4_unicode_ci AND e.est_visible = 1
            JOIN colegio_cursos cc      ON cc.cur_codigo COLLATE utf8mb4_unicode_ci = lc.cur_codigo COLLATE utf8mb4_unicode_ci
            JOIN colegio_notas n        ON n.est_codigo COLLATE utf8mb4_unicode_ci = e.est_codigo COLLATE utf8mb4_unicode_ci
            WHERE lc.lista_gestion = ?
            GROUP BY cc.cur_codigo, cc.cur_nombre, cc.cur_orden, e.est_codigo, e.est_apellidos, e.est_nombres
            ORDER BY cc.cur_orden ASC, suma DESC
        ";
        $rows = DB::select($sql, [$gestion]);

        $porCurso = [];
        foreach ($rows as $r) {
            $porCurso[$r->cur_codigo]['nombre'] = $r->cur_nombre;
            $porCurso[$r->cur_codigo]['rows'][] = $r;
        }
        foreach ($porCurso as &$g) {
            $g['rows'] = array_slice($g['rows'], 0, 3);
        }
        unset($g);

        $pdf = Pdf::loadView('notas.top3-pdf', compact('porCurso','config','gestion'))->setPaper('letter');
        return $pdf->stream('top3-cursos-'.$gestion.'.pdf');
    }

    /** Boletín individual por estudiante */
    public function boletin($estCodigo, Request $request)
    {
        $gestion    = (int) $request->input('gestion', date('Y'));
        $config     = DB::table('sistema_configuracion')->first();
        $estudiante = Estudiante::with('curso')->where('est_codigo', $estCodigo)->firstOrFail();
        $periodos   = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $rows = DB::select("
            SELECT m.mat_codigo, m.mat_nombre, m.mat_abreviatura, m.mat_orden,
                   n.periodo_id, ROUND(n.nota_promedio_trimestral) AS prom
            FROM colegio_notas n
            JOIN colegio_curso_materia_docente cmd
              ON cmd.curmatdoc_id = n.curmatdoc_id
            JOIN colegio_materias m
              ON CONVERT(m.mat_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = cmd.mat_codigo COLLATE utf8mb4_unicode_ci
            WHERE n.est_codigo = ?
              AND n.periodo_id IN (".implode(',', $periodos->pluck('periodo_id')->all() ?: [0]).")
            ORDER BY m.mat_orden ASC
        ", [$estCodigo]);

        $matriz = [];
        foreach ($rows as $r) {
            $matriz[$r->mat_codigo]['nombre'] = $r->mat_nombre;
            $matriz[$r->mat_codigo]['per'][$r->periodo_id] = $r->prom;
        }

        $pdf = Pdf::loadView('notas.boletin-pdf', compact(
            'estudiante','periodos','matriz','config','gestion'
        ))->setPaper('letter');
        return $pdf->stream('boletin-'.$estCodigo.'.pdf');
    }
}
