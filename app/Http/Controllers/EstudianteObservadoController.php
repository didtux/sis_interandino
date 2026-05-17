<?php

namespace App\Http\Controllers;

use App\Models\EstudianteObservado;
use App\Models\Estudiante;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EstudianteObservadoController extends Controller
{
    private function soloDirector()
    {
        $user = auth()->user();
        // Roles permitidos: 1 = admin, 4 = director (ajustar según tu sistema)
        if (!in_array($user->rol_id, [1, 4])) {
            abort(403, 'Solo dirección puede gestionar la lista de observados.');
        }
    }

    public function index(Request $request)
    {
        $this->soloDirector();

        $gestion = (int) $request->input('gestion', date('Y'));
        $estado  = $request->input('estado', 'activos'); // activos | liberados | todos

        $q = EstudianteObservado::with(['estudiante.curso', 'estudiante.padres'])
            ->where('obs_gestion', $gestion);

        if ($estado === 'activos')   $q->where('obs_activo', 1);
        elseif ($estado === 'liberados') $q->where('obs_activo', 0);

        $observados = $q->orderByDesc('obs_fecha_registro')->paginate(50);

        $estudiantes = Estudiante::visible()
            ->select('est_codigo', 'est_nombres', 'est_apellidos', 'cur_codigo')
            ->orderBy('est_apellidos')->get();

        return view('observados.index', compact('observados', 'gestion', 'estado', 'estudiantes'));
    }

    public function store(Request $request)
    {
        $this->soloDirector();

        $request->validate([
            'est_codigo'      => 'required|exists:colegio_estudiantes,est_codigo',
            'obs_gestion'     => 'required|integer',
            'obs_motivo_tipo' => 'required|in:PENSIONES,FALTAS,DISCIPLINARIO,OTRO',
            'obs_motivo'      => 'required|max:255',
        ]);

        // Verificar que no exista una observación activa ya
        if (EstudianteObservado::estaBloqueado($request->est_codigo, (int)$request->obs_gestion)) {
            return back()->with('error', 'Este estudiante ya está en la lista de observados para esta gestión.');
        }

        $user = auth()->user();
        EstudianteObservado::create([
            'est_codigo'                => $request->est_codigo,
            'obs_gestion'               => (int) $request->obs_gestion,
            'obs_motivo_tipo'           => $request->obs_motivo_tipo,
            'obs_motivo'                => $request->obs_motivo,
            'obs_registrado_por'        => $user->us_id,
            'obs_registrado_por_nombre' => trim(($user->us_nombres ?? '').' '.($user->us_apellidos ?? '')),
            'obs_fecha_registro'        => now(),
            'obs_activo'                => 1,
        ]);

        return redirect()->route('observados.index', ['gestion' => $request->obs_gestion])
            ->with('success', 'Estudiante agregado a la lista de observados.');
    }

    public function liberar(Request $request, $id)
    {
        $this->soloDirector();

        $request->validate(['obs_motivo_liberacion' => 'required|max:255']);

        $obs = EstudianteObservado::findOrFail($id);
        $user = auth()->user();

        $obs->update([
            'obs_activo'              => 0,
            'obs_liberado_por'        => $user->us_id,
            'obs_liberado_por_nombre' => trim(($user->us_nombres ?? '').' '.($user->us_apellidos ?? '')),
            'obs_fecha_liberacion'    => now(),
            'obs_motivo_liberacion'   => $request->obs_motivo_liberacion,
        ]);

        return back()->with('success', 'Estudiante liberado de la lista.');
    }

    public function reportePdf(Request $request)
    {
        $this->soloDirector();
        $gestion = (int) $request->input('gestion', date('Y'));
        $observados = EstudianteObservado::with(['estudiante.curso'])
            ->where('obs_gestion', $gestion)
            ->where('obs_activo', 1)
            ->orderByDesc('obs_fecha_registro')->get();

        $pdf = Pdf::loadView('observados.reporte-pdf', compact('observados', 'gestion'))
            ->setPaper('letter');
        return $pdf->stream('estudiantes-observados-'.$gestion.'.pdf');
    }
}
