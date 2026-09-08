<?php

namespace App\Http\Controllers;

use App\Models\AlertaParcial;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\NotaPeriodo;
use App\Models\CursoMateriaDocente;
use App\Models\ListaCurso;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertaParcialController extends Controller
{
    private const UMBRAL_REPROBACION = 51;

    private function esDireccion(): bool
    {
        return in_array(auth()->user()->rol_id, [1, 4, 9, 10]);
    }

    private function esDocente(): bool
    {
        return auth()->user()->us_entidad_tipo === 'docente';
    }

    // ──────────────────────────────────────────────────────────────────
    // Pantalla matriz (alumnos × materias) para marcar advertencias
    // ──────────────────────────────────────────────────────────────────
    public function matriz(Request $request)
    {
        $user    = auth()->user();
        $gestion = (int) $request->input('gestion', date('Y'));

        // Cursos disponibles: docente solo los suyos; dirección/admin todos.
        if ($this->esDocente() && !$this->esDireccion()) {
            $curCodigos = CursoMateriaDocente::where('doc_codigo', $user->us_entidad_id)
                ->where('curmatdoc_estado', 1)->pluck('cur_codigo')->unique();
            $cursos = Curso::whereIn('cur_codigo', $curCodigos)->orderBy('cur_nombre')->get();
        } else {
            $cursos = Curso::visible()->orderBy('cur_nombre')->get();
        }

        $periodos = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->get();

        $curCodigo = $request->input('cur_codigo', optional($cursos->first())->cur_codigo);
        $periodoId = $request->input('periodo_id', optional($periodos->first())->periodo_id);

        $datos = null;
        if ($curCodigo && $periodoId) {
            $datos = $this->construirMatriz($curCodigo, (int) $periodoId, $gestion, $user);
        }

        return view('alertas.matriz', compact('cursos', 'periodos', 'curCodigo', 'periodoId', 'gestion', 'datos'));
    }

    /** Arma estudiantes × materias con notas, reprobación sugerida y marcas guardadas. */
    private function construirMatriz(string $curCodigo, int $periodoId, int $gestion, $user): array
    {
        $curso = Curso::where('cur_codigo', $curCodigo)->firstOrFail();

        // Materias del curso (únicas, ordenadas)
        $materias = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $curCodigo)->where('curmatdoc_estado', 1)->get()
            ->pluck('materia')->filter()->unique('mat_codigo')
            ->sortBy(fn($m) => $m->mat_orden ?? 999)->values();

        // Estudiantes + número de lista
        $lista = ListaCurso::where('cur_codigo', $curCodigo)->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');
        $estudiantes = Estudiante::where('cur_codigo', $curCodigo)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get()
            ->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();

        // Notas globales por est+materia (para sugerir reprobación)
        $notasRows = DB::table('colegio_notas as n')
            ->join('colegio_curso_materia_docente as cmd', 'cmd.curmatdoc_id', '=', 'n.curmatdoc_id')
            ->where('cmd.cur_codigo', $curCodigo)
            ->where('n.periodo_id', $periodoId)
            ->select('n.est_codigo', 'cmd.mat_codigo', 'n.nota_promedio_trimestral')
            ->get();
        $notas = []; // [est][mat] => nota
        foreach ($notasRows as $r) {
            $notas[$r->est_codigo][$r->mat_codigo] = round((float) $r->nota_promedio_trimestral);
        }

        // Marcas guardadas
        $alertasRows = AlertaParcial::where('cur_codigo', $curCodigo)
            ->where('periodo_id', $periodoId)->get();
        $marcas = []; // [est][mat] => ['docente'=>,'director'=>]
        foreach ($alertasRows as $a) {
            $marcas[$a->est_codigo][$a->mat_codigo] = [
                'docente'  => (bool) $a->marcado_docente,
                'director' => (bool) $a->marcado_director,
            ];
        }

        // Materias que puede marcar el usuario (docente: solo las suyas; dirección: todas)
        $misMaterias = [];
        if (!$this->esDireccion()) {
            $misMaterias = CursoMateriaDocente::where('cur_codigo', $curCodigo)
                ->where('doc_codigo', $user->us_entidad_id)->where('curmatdoc_estado', 1)
                ->pluck('mat_codigo')->unique()->all();
        }

        return [
            'curso'       => $curso,
            'materias'    => $materias,
            'estudiantes' => $estudiantes,
            'lista'       => $lista,
            'notas'       => $notas,
            'marcas'      => $marcas,
            'misMaterias' => $misMaterias,
            'esDireccion' => $this->esDireccion(),
            'umbral'      => self::UMBRAL_REPROBACION,
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Marcado individual (docente naranja / director rosa)
    // ──────────────────────────────────────────────────────────────────
    public function toggle(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required|exists:colegio_estudiantes,est_codigo',
            'mat_codigo' => 'required',
            'cur_codigo' => 'required',
            'periodo_id' => 'required|integer',
            'rol'        => 'required|in:docente,director',
            'estado'     => 'required|in:0,1',
        ]);

        $user = auth()->user();
        if ($request->rol === 'director' && !$this->esDireccion()) {
            abort(403, 'Solo dirección puede marcar advertencias en rosa.');
        }

        $alerta = AlertaParcial::firstOrNew([
            'est_codigo' => $request->est_codigo,
            'mat_codigo' => $request->mat_codigo,
            'periodo_id' => $request->periodo_id,
        ]);
        $alerta->cur_codigo     = $request->cur_codigo;
        $alerta->alerta_gestion = (int) ($request->gestion ?? date('Y'));

        $nombre = trim(($user->us_nombres ?? '') . ' ' . ($user->us_apellidos ?? ''));

        if ($request->rol === 'docente') {
            $alerta->marcado_docente        = (int) $request->estado;
            $alerta->marcado_docente_por    = (int) $request->estado === 1 ? $user->us_id : null;
            $alerta->marcado_docente_nombre = (int) $request->estado === 1 ? $nombre : null;
            $alerta->marcado_docente_fecha  = (int) $request->estado === 1 ? now() : null;
        } else {
            $alerta->marcado_director        = (int) $request->estado;
            $alerta->marcado_director_por    = (int) $request->estado === 1 ? $user->us_id : null;
            $alerta->marcado_director_nombre = (int) $request->estado === 1 ? $nombre : null;
            $alerta->marcado_director_fecha  = (int) $request->estado === 1 ? now() : null;
        }
        $alerta->save();

        return response()->json([
            'ok'       => true,
            'docente'  => (bool) $alerta->marcado_docente,
            'director' => (bool) $alerta->marcado_director,
        ]);
    }

    /**
     * Pre-sugerencia: marca (rol según usuario) todas las materias reprobadas (nota < 51)
     * del curso+periodo que el usuario tiene permitido marcar.
     */
    public function aplicarSugerencia(Request $request)
    {
        $request->validate(['cur_codigo' => 'required', 'periodo_id' => 'required|integer']);
        $user      = auth()->user();
        $gestion   = (int) $request->input('gestion', date('Y'));
        $curCodigo = $request->cur_codigo;
        $periodoId = (int) $request->periodo_id;

        $esDir = $this->esDireccion();
        $rol   = $esDir ? 'director' : 'docente';

        // Materias que el usuario puede marcar
        $matPermitidas = $esDir ? null : CursoMateriaDocente::where('cur_codigo', $curCodigo)
            ->where('doc_codigo', $user->us_entidad_id)->where('curmatdoc_estado', 1)
            ->pluck('mat_codigo')->unique()->all();

        $notasRows = DB::table('colegio_notas as n')
            ->join('colegio_curso_materia_docente as cmd', 'cmd.curmatdoc_id', '=', 'n.curmatdoc_id')
            ->where('cmd.cur_codigo', $curCodigo)
            ->where('n.periodo_id', $periodoId)
            ->whereRaw('ROUND(n.nota_promedio_trimestral) > 0')
            ->whereRaw('ROUND(n.nota_promedio_trimestral) < ?', [self::UMBRAL_REPROBACION])
            ->select('n.est_codigo', 'cmd.mat_codigo')
            ->get();

        $nombre = trim(($user->us_nombres ?? '') . ' ' . ($user->us_apellidos ?? ''));
        $n = 0;
        foreach ($notasRows as $r) {
            if (!$esDir && !in_array($r->mat_codigo, $matPermitidas ?? [])) continue;

            $alerta = AlertaParcial::firstOrNew([
                'est_codigo' => $r->est_codigo,
                'mat_codigo' => $r->mat_codigo,
                'periodo_id' => $periodoId,
            ]);
            $alerta->cur_codigo     = $curCodigo;
            $alerta->alerta_gestion = $gestion;
            if ($rol === 'docente') {
                $alerta->marcado_docente = 1;
                $alerta->marcado_docente_por = $user->us_id;
                $alerta->marcado_docente_nombre = $nombre;
                $alerta->marcado_docente_fecha = now();
            } else {
                $alerta->marcado_director = 1;
                $alerta->marcado_director_por = $user->us_id;
                $alerta->marcado_director_nombre = $nombre;
                $alerta->marcado_director_fecha = now();
            }
            $alerta->save();
            $n++;
        }

        return back()->with('success', "Sugerencia aplicada: $n materias reprobadas marcadas en " . ($esDir ? 'ROSA (dirección)' : 'NARANJA (docente)') . '.');
    }

    // ──────────────────────────────────────────────────────────────────
    // DOC 1 — Advertencia en lote (varias por hoja, formato oficial)
    // ──────────────────────────────────────────────────────────────────
    public function advertenciaLote(Request $request)
    {
        $request->validate(['cur_codigo' => 'required', 'periodo_id' => 'required|integer']);
        $gestion = (int) $request->input('gestion', date('Y'));
        $curso   = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();
        $periodo = NotaPeriodo::findOrFail($request->periodo_id);

        // Materias del curso (para mostrarlas TODAS y resaltar las reprobadas)
        $materias = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $curso->cur_codigo)->where('curmatdoc_estado', 1)->get()
            ->pluck('materia')->filter()->unique('mat_codigo')
            ->sortBy(fn($m) => $m->mat_orden ?? 999)->values();

        $lista = ListaCurso::where('cur_codigo', $curso->cur_codigo)->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        // Marcas por estudiante: mat_codigo => 'director'|'docente'
        $alertas = AlertaParcial::where('cur_codigo', $curso->cur_codigo)
            ->where('periodo_id', $periodo->periodo_id)
            ->where(function ($q) { $q->where('marcado_docente', 1)->orWhere('marcado_director', 1); })
            ->get();

        $marcasPorEst = [];
        foreach ($alertas as $a) {
            $marcasPorEst[$a->est_codigo][$a->mat_codigo] = $a->marcado_director ? 'director' : 'docente';
        }

        // Solo estudiantes con al menos una marca
        $estudiantes = Estudiante::where('cur_codigo', $curso->cur_codigo)
            ->whereIn('est_codigo', array_keys($marcasPorEst))
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get()
            ->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();

        if ($estudiantes->isEmpty()) {
            return back()->with('error', 'No hay estudiantes con materias marcadas en este curso/periodo.');
        }

        $pdf = Pdf::loadView('alertas.advertencia-lote-pdf',
            compact('curso', 'periodo', 'materias', 'estudiantes', 'marcasPorEst', 'lista', 'gestion'))
            ->setPaper('letter');
        return $pdf->stream('advertencias-' . $curso->cur_codigo . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────────
    // DOC 2 — Nómina por curso (materias observadas por color / FELICITACIONES)
    // ──────────────────────────────────────────────────────────────────
    public function reporteCurso(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);
        $gestion = (int) $request->input('gestion', date('Y'));
        $curso = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();

        $periodo = $request->filled('periodo_id')
            ? NotaPeriodo::findOrFail($request->periodo_id)
            : NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->first();

        if (!$periodo) return back()->with('error', 'No hay periodo activo.');

        $lista = ListaCurso::where('cur_codigo', $curso->cur_codigo)->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        $estudiantes = Estudiante::where('cur_codigo', $curso->cur_codigo)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get()
            ->sortBy(fn($e) => $lista[$e->est_codigo] ?? 9999)->values();

        $alertas = AlertaParcial::where('cur_codigo', $curso->cur_codigo)
            ->where('periodo_id', $periodo->periodo_id)
            ->where(function ($q) { $q->where('marcado_docente', 1)->orWhere('marcado_director', 1); })
            ->get();

        $matCodigos = $alertas->pluck('mat_codigo')->unique();
        $materias   = Materia::whereIn('mat_codigo', $matCodigos)->get()->keyBy('mat_codigo');

        $porEst = [];
        foreach ($alertas as $a) {
            $porEst[$a->est_codigo][$a->mat_codigo] = [
                'docente'  => (bool) $a->marcado_docente,
                'director' => (bool) $a->marcado_director,
                'nombre'   => optional($materias[$a->mat_codigo] ?? null)->mat_nombre,
            ];
        }

        $pdf = Pdf::loadView('alertas.reporte-curso-pdf', compact('curso', 'periodo', 'estudiantes', 'porEst', 'gestion', 'lista'))
            ->setPaper('letter');
        return $pdf->stream('nomina-advertencia-' . $curso->cur_codigo . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────────
    // Advertencia individual (1 por hoja) — se conserva
    // ──────────────────────────────────────────────────────────────────
    public function reporteEstudiante(Request $request)
    {
        $request->validate(['est_codigo' => 'required', 'periodo_id' => 'required|integer']);
        $gestion    = (int) $request->input('gestion', date('Y'));
        $estudiante = Estudiante::with('curso')->where('est_codigo', $request->est_codigo)->firstOrFail();
        $periodo    = NotaPeriodo::findOrFail($request->periodo_id);

        $curCodigo = $estudiante->cur_codigo;
        $materias = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $curCodigo)->where('curmatdoc_estado', 1)->get()
            ->pluck('materia')->filter()->unique('mat_codigo')
            ->sortBy(fn($m) => $m->mat_orden ?? 999)->values();

        $alertasRows = AlertaParcial::where('est_codigo', $estudiante->est_codigo)
            ->where('periodo_id', $periodo->periodo_id)
            ->where(function ($q) { $q->where('marcado_docente', 1)->orWhere('marcado_director', 1); })
            ->get();
        $marcas = [];
        foreach ($alertasRows as $a) {
            $marcas[$a->mat_codigo] = $a->marcado_director ? 'director' : 'docente';
        }

        $lista = ListaCurso::where('cur_codigo', $curCodigo)->where('lista_gestion', $gestion)
            ->pluck('lista_numero', 'est_codigo');

        // Reutiliza la plantilla del lote con un solo estudiante
        $curso        = $estudiante->curso;
        $estudiantes  = collect([$estudiante]);
        $marcasPorEst = [$estudiante->est_codigo => $marcas];

        $pdf = Pdf::loadView('alertas.advertencia-lote-pdf',
            compact('curso', 'periodo', 'materias', 'estudiantes', 'marcasPorEst', 'lista', 'gestion'))
            ->setPaper('letter');
        return $pdf->stream('advertencia-' . $estudiante->est_codigo . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────────
    // DOC 3 — Reporte para docente (hoja con casilleros numerados por curso)
    // ──────────────────────────────────────────────────────────────────
    public function hojaDocente(Request $request)
    {
        $gestion = (int) $request->input('gestion', date('Y'));
        $user    = auth()->user();

        // Docente objetivo: el propio docente, o uno elegido por dirección
        $docCodigo = $request->input('doc_codigo');
        if (!$docCodigo && $this->esDocente()) {
            $docCodigo = $user->us_entidad_id;
        }

        // Dirección sin docente elegido → pantalla de selección
        if (!$docCodigo) {
            $docCodigos = CursoMateriaDocente::where('curmatdoc_estado', 1)
                ->pluck('doc_codigo')->unique()->filter();
            $docentes = \App\Models\Docente::whereIn('doc_codigo', $docCodigos)
                ->orderBy('doc_apellidos')->orderBy('doc_nombres')->get();
            return view('alertas.hoja-docente-select', compact('docentes', 'gestion'));
        }

        $docente = \App\Models\Docente::where('doc_codigo', $docCodigo)->first();

        $asignaciones = CursoMateriaDocente::with(['curso', 'materia'])
            ->where('doc_codigo', $docCodigo)->where('curmatdoc_estado', 1)->get();

        // Materias (áreas) que dicta
        $areas = $asignaciones->pluck('materia')->filter()->unique('mat_codigo')
            ->pluck('mat_nombre')->implode(' / ');

        // Filas: por curso → cantidad de estudiantes (para los casilleros 1..N)
        $filas = [];
        foreach ($asignaciones->pluck('curso')->filter()->unique('cur_codigo') as $curso) {
            $cant = Estudiante::where('cur_codigo', $curso->cur_codigo)->count();
            $filas[] = ['curso' => $curso->cur_nombre, 'cantidad' => $cant];
        }

        $periodo = NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->first();

        $pdf = Pdf::loadView('alertas.hoja-docente-pdf', compact('docente', 'areas', 'filas', 'periodo', 'gestion'))
            ->setPaper('letter');
        return $pdf->stream('reporte-docente-' . $docCodigo . '.pdf');
    }
}
