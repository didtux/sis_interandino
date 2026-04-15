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
        $asistenciaPorPeriodo = [];
        foreach ($periodos as $periodo) {
            $inicio = $periodo->periodo_fecha_inicio->format('Y-m-d');
            $fin    = $periodo->periodo_fecha_fin->format('Y-m-d');

            $diasTrabajados = Asistencia::whereBetween('asis_fecha', [$inicio, $fin])
                ->select('asis_fecha')->distinct()->count();

            $presencias = Asistencia::where('estud_codigo', $request->est_codigo)
                ->whereBetween('asis_fecha', [$inicio, $fin])->count();

            $atrasos = Atraso::where('estud_codigo', $request->est_codigo)
                ->whereBetween('atraso_fecha', [$inicio, $fin])->count();

            $licencias = Permiso::where('estud_codigo', $request->est_codigo)
                ->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fin)
                ->where('permiso_fecha_fin', '>=', $inicio)
                ->count();

            $faltas = max(0, $diasTrabajados - $presencias - $licencias);

            $asistenciaPorPeriodo[$periodo->periodo_numero] = compact(
                'atrasos', 'licencias', 'faltas', 'diasTrabajados'
            );
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
        $gruposActivos = MateriaGrupo::activo()->with('materias')->get();

        $pdf = Pdf::loadView('notas.reporte-personal-pdf', compact(
            'estudiante', 'periodos', 'notasPorMateria',
            'asistenciaPorPeriodo', 'enfermeriaPorPeriodo',
            'controlPorPeriodo', 'gestion', 'listaNumero',
            'gruposMap', 'gruposActivos', 'curmatdocs'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('reporte-personal-' . $request->est_codigo . '-' . $gestion . '.pdf');
    }

    /**
     * Construye mapa de grupos: [mat_codigo => grupo] para materias agrupadas.
     */
    private function buildGruposMap($matCodigos)
    {
        $grupos = MateriaGrupo::activo()->with('materias')->get();
        $map = []; // mat_codigo => MateriaGrupo
        foreach ($grupos as $grupo) {
            foreach ($grupo->materias as $mat) {
                if (in_array($mat->mat_codigo, $matCodigos->toArray())) {
                    $map[$mat->mat_codigo] = $grupo;
                }
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
        $matCodigosGrupo = $grupo->materias->pluck('mat_codigo');
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

        $estudiantes = Estudiante::visible()
            ->where('cur_codigo', $request->cur_codigo)
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
        $gruposActivos = MateriaGrupo::activo()->with('materias')->get();

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

        $estudiantes = Estudiante::visible()
            ->where('cur_codigo', $request->cur_codigo)
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
        $gruposActivos = MateriaGrupo::activo()->with('materias')->get();

        $pdf = Pdf::loadView('notas.reporte-general-pdf', compact(
            'curso', 'periodos', 'periodosActivos', 'curmatdocs',
            'estudiantes', 'dimensiones', 'notasMatrix',
            'lista', 'gestion', 'asistenciaMatrix',
            'gruposMap', 'gruposActivos'
        ))->setPaper('legal', 'landscape');

        return $pdf->stream('general-' . preg_replace('/\s+/', '_', $curso->cur_nombre) . '-' . $gestion . '.pdf');
    }
}
