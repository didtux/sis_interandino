<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\EstudianteGestion;
use App\Services\PromocionService;
use Illuminate\Http\Request;

/**
 * Resultado de fin de año por curso (PROMOVIDO / REPROBADO / EGRESADO).
 * Se calcula con la fórmula del boletín; dirección puede corregirlo a mano
 * (capacidad "promocion.decidir") y el resultado pasa a las pre-inscripciones.
 */
class PromocionController extends Controller
{
    public function index(Request $request)
    {
        $gestion = (int) $request->input('gestion', gestion_actual());
        $cursos = Curso::visible()->ordenado()->get();
        $curso = $request->input('cur_codigo');

        $resumen = EstudianteGestion::where('ges_anio', $gestion)
            ->selectRaw('cur_codigo, eg_resultado, COUNT(*) n')->groupBy('cur_codigo', 'eg_resultado')->get()
            ->groupBy('cur_codigo')->map(fn($g) => $g->pluck('n', 'eg_resultado'));

        $filas = collect();
        if ($curso) {
            $filas = EstudianteGestion::with('estudiante')->where('ges_anio', $gestion)->where('cur_codigo', $curso)->get()
                ->sortBy(fn($e) => optional($e->estudiante)->est_apellidos . ' ' . optional($e->estudiante)->est_nombres)->values();
        }
        return view('promocion.index', compact('gestion', 'cursos', 'curso', 'resumen', 'filas'));
    }

    public function calcular(Request $request)
    {
        $gestion = (int) $request->input('gestion', gestion_actual());
        [$n, $incompletos, $periodos] = (new PromocionService())->calcular($gestion, $request->input('cur_codigo') ?: null);
        $cambios = (new PromocionService())->aplicarAPreinscripciones($gestion);
        $msg = "Resultados calculados para {$n} estudiantes.";
        if ($incompletos) $msg .= " {$incompletos} quedan PENDIENTES porque les faltan notas en alguno de los {$periodos} trimestres.";
        if ($cambios['CONFIRMADA'] || $cambios['AJUSTAR']) {
            $msg .= " Pre-inscripciones " . ($gestion + 1) . ": {$cambios['CONFIRMADA']} confirmadas, {$cambios['AJUSTAR']} a ajustar (reprobaron).";
        }
        return back()->with('success', $msg);
    }

    /** Corrección manual del resultado (queda quién, cuándo y por qué). */
    public function decidir(Request $request, $id)
    {
        if (!auth()->user()->tieneCapacidad('promocion.decidir')) {
            abort(403, 'Su rol no tiene la capacidad «Fijar a mano el resultado de fin de año».');
        }
        $request->validate([
            'eg_resultado' => 'required|in:' . implode(',', EstudianteGestion::RESULTADOS),
            'motivo'       => 'required_unless:automatico,1|nullable|max:255',
            'automatico'   => 'nullable|boolean',
        ]);
        $eg = EstudianteGestion::findOrFail($id);
        if ($request->boolean('automatico')) {
            $eg->update(['eg_resultado_manual' => 0, 'eg_resultado_motivo' => null, 'eg_resultado_por' => auth()->user()->us_id]);
            (new PromocionService())->calcular((int) $eg->ges_anio, $eg->cur_codigo);
        } else {
            $eg->update([
                'eg_resultado'        => $request->eg_resultado,
                'eg_resultado_manual' => 1,
                'eg_resultado_motivo' => $request->motivo,
                'eg_resultado_por'    => auth()->user()->us_id,
                'eg_resultado_fecha'  => now(),
            ]);
        }
        (new PromocionService())->aplicarAPreinscripciones((int) $eg->ges_anio);
        return back()->with('success', 'Resultado actualizado.');
    }
}
