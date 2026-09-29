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

        // ── Asistencia por periodo (Service unificado con dedup global) ────────
        $periodosOrden = $periodos->sortBy('periodo_numero')->values();
        $resumenAsis   = (new \App\Services\AsistenciaResumenService())
            ->resumenPorTrimestre($request->est_codigo, $periodosOrden);

        $asistenciaPorPeriodo = [];
        foreach ($resumenAsis as $pn => $r) {
            $asistenciaPorPeriodo[$pn] = [
                'atrasos'        => $r['atrasos'],
                'licencias'      => $r['licencias_dias'],
                'faltas'         => $r['faltas'],
                'diasTrabajados' => $r['dias_trabajados_curso'],
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
        // En COMPROMISOS sólo entran los ESCRITOS: tener texto en psico_acuerdo
        // no alcanza, porque los acuerdos verbales también lo llevan.
        // La vista lee esto como $psicoData, con las mismas cuatro claves que
        // arma NotaController::getPsicoTrimestreEst().
        $psicoData = [];
        foreach ($periodos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');

            $casos = CasoPsicopedagogia::where('est_codigo', $request->est_codigo)
                ->where('psico_estado', 1)
                ->whereBetween('psico_fecha', [$inicio, $fin])
                ->get();

            $escritos = $casos->where('psico_tipo_acuerdo', 'ESCRITO')->count();

            $psicoData[$periodo->periodo_numero] = [
                'llamadas_si'     => $casos->count(),
                'llamadas_no'     => 0,
                'compromisos_si'  => $escritos,
                'compromisos_no'  => max(0, $casos->count() - $escritos),
            ];
        }

        // Grupos de materias (config por curso del estudiante)
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'), $estudiante->cur_codigo);
        $gruposActivos = collect($gruposMap)->unique('grupo_id')->values();

        $pdf = Pdf::loadView('notas.reporte-personal-pdf', compact(
            'estudiante', 'periodos', 'notasPorMateria',
            'asistenciaPorPeriodo', 'enfermeriaPorPeriodo',
            'psicoData', 'gestion', 'listaNumero',
            'gruposMap', 'gruposActivos', 'curmatdocs'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('reporte-personal-' . $request->est_codigo . '-' . $gestion . '.pdf');
    }

    /**
     * Construye mapa de grupos: [mat_codigo => grupo sintético] basado en mat_campo.
     * Cada campo (área) se considera un grupo. Sólo se agrupa si hay 2+ materias
     * con el mismo campo dentro del set de mat_codigos del curso.
     */
    private function buildGruposMap($matCodigos, ?string $curCodigo = null)
    {
        // Si tenemos contexto de curso, delegamos al MateriaCursoService (config por curso).
        if ($curCodigo) {
            [, $map] = (new \App\Services\MateriaCursoService())->gruposPorCampo($curCodigo);
            $codigosSet = collect($matCodigos)->flip();
            // Filtrar el map al subset de materias presentes (defensa).
            return collect($map)->filter(fn($v, $k) => $codigosSet->has($k))->all();
        }

        // Fallback global (legacy).
        $codigos = $matCodigos->toArray();
        $materias = \App\Models\Materia::whereIn('mat_codigo', $codigos)
            ->whereNotNull('mat_campo')->where('mat_campo', '!=', '')
            ->orderBy('mat_orden')->get();

        $porCampo = $materias->groupBy(fn($m) => trim((string) $m->mat_campo));

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

        // Grupos de materias (config por curso)
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'), $curso->cur_codigo);
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

        // Asistencia por periodo y estudiante (Service unificado con dedup global por estudiante)
        $asistenciaMatrix = [];
        $service = new \App\Services\AsistenciaResumenService();
        $periodosOrdenGen = $periodosActivos->sortBy('periodo_numero')->values();
        foreach ($estudiantes as $est) {
            $resumen = $service->resumenPorTrimestre($est->est_codigo, $periodosOrdenGen);
            foreach ($resumen as $pn => $r) {
                $asistenciaMatrix[$pn][$est->est_codigo] = [
                    'presencias'     => $r['presencias'],
                    'atrasos'        => $r['atrasos'],
                    'licencias'      => $r['licencias_dias'],
                    'faltas'         => $r['faltas'],
                    'diasTrabajados' => $r['dias_trabajados_curso'],
                ];
            }
        }

        // Grupos de materias (config por curso)
        $gruposMap = $this->buildGruposMap($curmatdocs->pluck('mat_codigo'), $curso->cur_codigo ?? null);
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
        // La pantalla manda 'curso'; otros enlaces del sistema usan 'cur_codigo'.
        $cursoCod = $request->input('curso') ?: $request->input('cur_codigo');
        if (!$cursoCod) abort(400, 'Falta el curso');
        $gestion = (int) $request->input('gestion', date('Y'));

        $curso     = Curso::where('cur_codigo', $cursoCod)->firstOrFail();
        $config    = DB::table('sistema_configuracion')->first();
        $periodos  = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $asignaciones = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $cursoCod)->where('curmatdoc_estado', 1)->get();
        $materias  = $asignaciones->pluck('materia')->filter()->sortBy('mat_orden')->values();

        // cur_codigo existe en las dos tablas del join: sin calificar, MariaDB
        // rechaza la consulta con "Column 'cur_codigo' in where clause is ambiguous".
        $estudiantes = Estudiante::where('colegio_estudiantes.cur_codigo', $cursoCod)
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

    /**
     * Puntajes de cuadro de honor de los estudiantes de los cursos dados: la
     * SUMA y el PROMEDIO que imprime el centralizador (BoletinNotasService).
     *
     * Antes cada variante del cuadro tenía su consulta SQL: promediaba el
     * decimal, contaba notas sin aprobar y de asignaciones inactivas, y el
     * top 3 mezclaba gestiones. El puesto no se podía verificar con el boletín.
     *
     * Devuelve filas ordenadas por promedio y, a igual promedio, por suma.
     * Los estudiantes sin ninguna nota aprobada quedan afuera.
     */
    private function puntajesHonor($cursos, $periodos, ?int $periodoNumero): array
    {
        $service = new \App\Services\BoletinNotasService();
        $cursosPorCodigo = collect($cursos)->keyBy('cur_codigo');

        $estudiantes = Estudiante::whereIn('cur_codigo', $cursosPorCodigo->keys())
            ->where('est_visible', 1)
            ->get(['est_codigo', 'est_apellidos', 'est_nombres', 'cur_codigo']);

        $rows = [];
        foreach ($estudiantes as $e) {
            $c = $cursosPorCodigo->get($e->cur_codigo);
            $pt = $service->puntaje($e->est_codigo, $e->cur_codigo, $periodos, $periodoNumero);
            if ($pt['materias'] === 0) continue;
            $rows[] = (object) [
                'est_codigo' => $e->est_codigo,
                'nombre'     => trim($e->est_apellidos . ' ' . $e->est_nombres),
                'cur_codigo' => $c->cur_codigo,
                'cur_nombre' => $c->cur_nombre,
                'cur_nivel'  => $c->cur_nivel,
                'cur_orden'  => $c->cur_orden,
                'suma'       => $pt['suma'],
                'promedio'   => $pt['promedio'],
                'exacto'     => $pt['exacto'],
                'materias'   => $pt['materias'],
            ];
        }

        usort($rows, fn($a, $b) => [$b->exacto, $b->suma, $a->nombre] <=> [$a->exacto, $a->suma, $b->nombre]);
        return $rows;
    }

    /** Ranking de cursos: promedio de los puntajes de sus estudiantes. */
    private function rankingCursosHonor(array $rows): array
    {
        $ranking = [];
        foreach (collect($rows)->groupBy('cur_codigo') as $cur => $ests) {
            $f = $ests->first();
            $ranking[] = (object) [
                'cur_codigo'     => $cur,
                'cur_nombre'     => $f->cur_nombre,
                'cur_nivel'      => $f->cur_nivel,
                'cur_orden'      => $f->cur_orden,
                'promedio_curso' => round($ests->avg('promedio'), 2),
                'estudiantes'    => $ests->count(),
            ];
        }
        usort($ranking, fn($a, $b) => $b->promedio_curso <=> $a->promedio_curso);
        return $ranking;
    }

    /** Cuadro de Honor: por curso, nivel o colegio */
    public function cuadroHonor(Request $request)
    {
        $tipo      = $request->input('tipo', 'curso'); // curso | nivel | colegio | ue | ue-nivel
        $cursoCod  = $request->input('curso');
        $nivelIn   = $request->input('nivel');
        $periodoId = $request->input('periodo_id'); // opcional: trimestre específico
        $gestion   = (int) $request->input('gestion', date('Y'));
        $config    = DB::table('sistema_configuracion')->first();

        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        // Etiqueta del trimestre para el título
        $periodoNumero   = null;
        $trimestreLabel  = ' — ANUAL';
        $trimestreNombre = 'ANUAL';
        if ($periodoId) {
            $per = NotaPeriodo::find($periodoId);
            if ($per) {
                $periodoNumero   = (int) $per->periodo_numero;
                $trimestreNombre = $per->periodo_nombre ?: ($per->periodo_numero . '° TRIMESTRE');
                $trimestreLabel  = ' — ' . $trimestreNombre;
            }
        }

        // Si llega tipo=nivel sin nivel explícito pero con curso, deriva el nivel del curso
        if ($tipo === 'nivel' && !$nivelIn && $cursoCod) {
            $nivelIn = Curso::where('cur_codigo', $cursoCod)->value('cur_nivel');
        }

        $todosLosCursos = Curso::orderBy('cur_orden')->orderBy('cur_nombre')->get();

        // ── tipo = ue / ue-nivel: ranking de CURSOS por promedio del curso ──
        if (in_array($tipo, ['ue', 'ue-nivel'])) {
            $rankingCursos = $this->rankingCursosHonor(
                $this->puntajesHonor($todosLosCursos, $periodos, $periodoNumero)
            );

            if ($tipo === 'ue-nivel') {
                // Agrupar por nivel manteniendo el ranking dentro de cada nivel.
                $porNivel = [];
                foreach ($rankingCursos as $r) {
                    $nivel = $r->cur_nivel ?: 'SIN NIVEL';
                    if (!isset($porNivel[$nivel])) $porNivel[$nivel] = [];
                    $porNivel[$nivel][] = $r;
                }
                // Orden de niveles (Inicial → Primario → Secundario → otros)
                $ordenNivel = ['INICIAL'=>1,'PRIMARIA'=>2,'PRIMARIO'=>2,'SECUNDARIA'=>3,'SECUNDARIO'=>3];
                uksort($porNivel, function($a,$b) use ($ordenNivel){
                    return ($ordenNivel[strtoupper($a)] ?? 99) <=> ($ordenNivel[strtoupper($b)] ?? 99);
                });
                $titulo = 'CUADRO DE HONOR POR NIVEL' . $trimestreLabel;
                $pdf = Pdf::loadView('notas.cuadro-honor-nivel-pdf', compact('porNivel','titulo','config','gestion','trimestreNombre'))
                    ->setPaper('letter');
                return $pdf->stream('cuadro-honor-nivel-'.$gestion.'.pdf');
            }

            $titulo = 'CUADRO DE HONOR — UNIDAD EDUCATIVA' . $trimestreLabel;
            $pdf = Pdf::loadView('notas.cuadro-honor-ue-pdf', compact('rankingCursos','titulo','config','gestion','trimestreNombre'))
                ->setPaper('letter');
            return $pdf->stream('cuadro-honor-ue-'.$gestion.'.pdf');
        }

        // ── tipo = colegio: top 3 por curso, agrupado por curso ordenado ──
        if ($tipo === 'colegio') {
            $porCurso = $this->top3PorCursoHonor(
                $this->puntajesHonor($todosLosCursos, $periodos, $periodoNumero), $todosLosCursos
            );
            $titulo = 'CUADRO DE HONOR — INSTITUCIÓN' . $trimestreLabel;
            $pdf = Pdf::loadView('notas.cuadro-honor-institucional-pdf', compact('porCurso','titulo','config','gestion'))
                ->setPaper('letter');
            return $pdf->stream('cuadro-honor-colegio-'.$gestion.'.pdf');
        }

        // ── tipo = curso o nivel: ranking lineal ──
        $cursosFiltro = $todosLosCursos;
        if ($tipo === 'curso' && $cursoCod) {
            $cursosFiltro = $todosLosCursos->where('cur_codigo', $cursoCod);
        } elseif ($tipo === 'nivel' && $nivelIn) {
            $cursosFiltro = $todosLosCursos->where('cur_nivel', $nivelIn);
        }

        // Para el curso hace falta el ranking contra los demás, así que se
        // calcula el colegio entero una sola vez y se filtra de ahí.
        $rankingCursos = [];
        if ($tipo === 'curso' && $cursoCod) {
            $todas = $this->puntajesHonor($todosLosCursos, $periodos, $periodoNumero);
            $rankingCursos = $this->rankingCursosHonor($todas);
            $rows = array_values(array_filter($todas, fn($r) => $r->cur_codigo === $cursoCod));
        } else {
            $rows = $this->puntajesHonor($cursosFiltro, $periodos, $periodoNumero);
        }

        $titulo = match($tipo) {
            'nivel'   => 'CUADRO DE HONOR — NIVEL ' . ($nivelIn ?? '') . $trimestreLabel,
            default   => 'CUADRO DE HONOR — ' . (Curso::where('cur_codigo', $cursoCod)->value('cur_nombre') ?? '') . $trimestreLabel,
        };

        $cursoActual = $cursoCod ? Curso::where('cur_codigo', $cursoCod)->first() : null;

        $pdf = Pdf::loadView('notas.cuadro-honor-pdf', compact(
            'rows','rankingCursos','cursoCod','cursoActual','titulo','config','gestion','tipo','trimestreNombre'
        ))->setPaper('letter');
        return $pdf->stream('cuadro-honor-'.$tipo.'-'.$gestion.'.pdf');
    }

    /** Agrupa puntajes por curso (en orden de curso) y deja los 3 primeros. */
    private function top3PorCursoHonor(array $rows, $cursos): array
    {
        $porCurso = [];
        foreach ($cursos as $c) {
            $top = array_slice(array_values(array_filter($rows, fn($r) => $r->cur_codigo === $c->cur_codigo)), 0, 3);
            if (!$top) continue;
            $porCurso[$c->cur_codigo] = [
                'cur_nombre' => $c->cur_nombre,
                'nombre'     => $c->cur_nombre,
                'cur_nivel'  => $c->cur_nivel,
                'cur_orden'  => $c->cur_orden,
                'rows'       => $top,
            ];
        }
        return $porCurso;
    }

    /** Top 3 por curso (anual) */
    public function top3PorCurso(Request $request)
    {
        $gestion  = (int) $request->input('gestion', date('Y'));
        $config   = DB::table('sistema_configuracion')->first();
        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();
        $cursos   = Curso::orderBy('cur_orden')->orderBy('cur_nombre')->get();

        // Antes sumaba las notas de TODAS las gestiones: el filtro de gestión
        // estaba sólo en la lista del curso, no en las notas.
        $porCurso = $this->top3PorCursoHonor($this->puntajesHonor($cursos, $periodos, null), $cursos);

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
            SELECT m.mat_codigo, m.mat_nombre, m.mat_orden,
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
