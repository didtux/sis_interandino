<?php

namespace App\Http\Controllers;

use App\Models\BloqueoNotas;
use App\Models\Estudiante;
use App\Services\MoraService;
use Illuminate\Http\Request;

/**
 * Bloqueo de notas en el portal de padres: bloqueos y exoneraciones manuales,
 * más una vista previa de a quiénes alcanza el bloqueo automático con los
 * parámetros actuales (Parametrización → Parámetros del colegio).
 */
class BloqueoNotasController extends Controller
{
    public function index()
    {
        $gestion = gestion_actual();
        $manuales = BloqueoNotas::with('estudiante.curso')->where('ges_anio', $gestion)->where('bloq_activo', 1)
            ->orderByDesc('bloq_id')->get();

        $minimo = (int) parametro('bloqueo_meses_minimo', 2);
        $alcanzados = (new MoraService())->enMora(mes_de_gestion(), $gestion)
            ->filter(fn($e) => count($e->mora_meses_pendientes) >= $minimo)->values();
        $estudiantes = Estudiante::visible()->orderBy('est_apellidos')->get(['est_codigo', 'est_apellidos', 'est_nombres']);

        $activo = (string) parametro('bloqueo_mora_activo', '0') === '1';
        $modo = parametro('bloqueo_mora_modo', 'AMBOS');
        $desdeMes = (int) parametro('bloqueo_desde_mes', 3);
        return view('bloqueos.index', compact('manuales', 'alcanzados', 'estudiantes', 'activo', 'modo', 'minimo', 'desdeMes', 'gestion'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo'    => 'required|exists:colegio_estudiantes,est_codigo',
            'bloq_tipo'     => 'required|in:BLOQUEAR,EXONERAR',
            'bloq_motivo'   => 'required|max:255',
            'vigente_hasta' => 'nullable|date|after_or_equal:today',
        ]);
        // Un estudiante tiene una sola marca manual vigente: la nueva reemplaza a la anterior.
        BloqueoNotas::where('est_codigo', $request->est_codigo)->where('ges_anio', gestion_actual())->update(['bloq_activo' => 0]);
        BloqueoNotas::create($request->only('est_codigo', 'bloq_tipo', 'bloq_motivo', 'vigente_hasta') + [
            'ges_anio' => gestion_actual(), 'bloq_activo' => 1, 'bloq_usuario' => auth()->user()->us_id, 'bloq_fecha' => now(),
        ]);
        return back()->with('success', $request->bloq_tipo === 'BLOQUEAR' ? 'Notas bloqueadas para ese estudiante.' : 'Estudiante exonerado del bloqueo.');
    }

    public function destroy($id)
    {
        BloqueoNotas::findOrFail($id)->update(['bloq_activo' => 0]);
        return back()->with('success', 'Marca manual quitada.');
    }
}
