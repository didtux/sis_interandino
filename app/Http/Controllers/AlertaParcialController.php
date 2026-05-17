<?php

namespace App\Http\Controllers;

use App\Models\AlertaParcial;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\NotaPeriodo;
use App\Models\CursoMateriaDocente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AlertaParcialController extends Controller
{
    /** Toggle marca (docente o director) para un estudiante+materia+periodo. */
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
        $esAdminODirector = in_array($user->rol_id, [1, 4]);
        if ($request->rol === 'director' && !$esAdminODirector) {
            abort(403, 'Solo dirección puede marcar advertencias en rosa.');
        }

        $alerta = AlertaParcial::firstOrNew([
            'est_codigo' => $request->est_codigo,
            'mat_codigo' => $request->mat_codigo,
            'periodo_id' => $request->periodo_id,
        ]);
        $alerta->cur_codigo     = $request->cur_codigo;
        $alerta->alerta_gestion = (int) ($request->gestion ?? date('Y'));

        $nombre = trim(($user->us_nombres ?? '').' '.($user->us_apellidos ?? ''));

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
            'ok' => true,
            'docente'  => (bool) $alerta->marcado_docente,
            'director' => (bool) $alerta->marcado_director,
        ]);
    }

    /** Reporte por curso: lista con materias observadas por estudiante (color naranja / rosa). */
    public function reporteCurso(Request $request)
    {
        $request->validate(['cur_codigo' => 'required']);
        $gestion = (int) $request->input('gestion', date('Y'));
        $curso = Curso::where('cur_codigo', $request->cur_codigo)->firstOrFail();

        $periodo = $request->filled('periodo_id')
            ? NotaPeriodo::findOrFail($request->periodo_id)
            : NotaPeriodo::activo()->gestion($gestion)->orderBy('periodo_numero')->first();

        if (!$periodo) return back()->with('error', 'No hay periodo activo.');

        $estudiantes = Estudiante::where('cur_codigo', $curso->cur_codigo)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get();

        $alertas = AlertaParcial::where('cur_codigo', $curso->cur_codigo)
            ->where('periodo_id', $periodo->periodo_id)
            ->where(function($q){ $q->where('marcado_docente', 1)->orWhere('marcado_director', 1); })
            ->get();

        $matCodigos = $alertas->pluck('mat_codigo')->unique();
        $materias   = Materia::whereIn('mat_codigo', $matCodigos)->get()->keyBy('mat_codigo');

        // Agrupar por estudiante: mat_codigo => ['docente'=>bool, 'director'=>bool]
        $porEst = [];
        foreach ($alertas as $a) {
            $porEst[$a->est_codigo][$a->mat_codigo] = [
                'docente'  => (bool) $a->marcado_docente,
                'director' => (bool) $a->marcado_director,
                'nombre'   => optional($materias[$a->mat_codigo] ?? null)->mat_nombre,
            ];
        }

        $pdf = Pdf::loadView('alertas.reporte-curso-pdf', compact('curso','periodo','estudiantes','porEst','gestion'))
            ->setPaper('letter');
        return $pdf->stream('advertencia-'.$curso->cur_codigo.'.pdf');
    }

    /** Reporte individual por estudiante (la hoja que firma el padre). */
    public function reporteEstudiante(Request $request)
    {
        $request->validate(['est_codigo' => 'required', 'periodo_id' => 'required|integer']);
        $estudiante = Estudiante::with('curso')->where('est_codigo', $request->est_codigo)->firstOrFail();
        $periodo    = NotaPeriodo::findOrFail($request->periodo_id);

        // Solo las marcadas en ROSA (oficial al padre)
        $alertas = AlertaParcial::with('materia')
            ->where('est_codigo', $estudiante->est_codigo)
            ->where('periodo_id', $periodo->periodo_id)
            ->where('marcado_director', 1)
            ->get();

        $pdf = Pdf::loadView('alertas.reporte-estudiante-pdf', compact('estudiante','periodo','alertas'))
            ->setPaper('letter');
        return $pdf->stream('advertencia-'.$estudiante->est_codigo.'.pdf');
    }
}
