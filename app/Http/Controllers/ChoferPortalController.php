<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use App\Models\AsignacionTransporte;
use App\Models\EstudianteRuta;
use App\Models\AsistenciaTransporte;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class ChoferPortalController extends Controller
{
    private function getChofer()
    {
        $user = auth()->user();
        return Chofer::where('chof_codigo', $user->us_entidad_id)->firstOrFail();
    }

    private function getAsignaciones()
    {
        return AsignacionTransporte::with('ruta', 'vehiculo')
            ->where('chof_codigo', $this->getChofer()->chof_codigo)
            ->activo()->get();
    }

    private function getRutaCodigos()
    {
        return $this->getAsignaciones()->pluck('ruta_codigo');
    }

    // ── Dashboard ──────────────────────────────────────────────
    public function dashboard()
    {
        $chofer = $this->getChofer();
        $asignaciones = $this->getAsignaciones();
        $rutaCodigos = $asignaciones->pluck('ruta_codigo');
        $hoy = date('Y-m-d');

        $totalEstudiantes = EstudianteRuta::whereIn('ruta_codigo', $rutaCodigos)->activo()->count();
        $asistHoy = AsistenciaTransporte::whereIn('ruta_codigo', $rutaCodigos)->where('tasis_fecha', $hoy)->count();
        $idaHoy = AsistenciaTransporte::whereIn('ruta_codigo', $rutaCodigos)->where('tasis_fecha', $hoy)->where('tasis_tipo', 'IDA')->count();
        $vueltaHoy = AsistenciaTransporte::whereIn('ruta_codigo', $rutaCodigos)->where('tasis_fecha', $hoy)->where('tasis_tipo', 'VUELTA')->count();

        return view('chofer-portal.dashboard', compact('chofer', 'asignaciones', 'totalEstudiantes', 'idaHoy', 'vueltaHoy'));
    }

    // ── Mis Estudiantes ────────────────────────────────────────
    public function estudiantes(Request $request)
    {
        $asignaciones = $this->getAsignaciones();
        $rutaCodigos = $asignaciones->pluck('ruta_codigo');
        $rutaSeleccionada = $request->ruta_codigo && $rutaCodigos->contains($request->ruta_codigo)
            ? $request->ruta_codigo : $rutaCodigos->first();

        $estudiantesRuta = EstudianteRuta::with('estudiante.curso', 'ruta')
            ->where('ruta_codigo', $rutaSeleccionada)->activo()
            ->get();

        $rutas = $asignaciones->map(fn($a) => $a->ruta)->filter()->unique('ruta_codigo');

        return view('chofer-portal.estudiantes', compact('rutas', 'rutaSeleccionada', 'estudiantesRuta'));
    }

    // ── Registrar Asistencia ───────────────────────────────────
    public function asistencia(Request $request)
    {
        $asignaciones = $this->getAsignaciones();
        $rutaCodigos = $asignaciones->pluck('ruta_codigo');
        $rutaSeleccionada = $request->ruta_codigo && $rutaCodigos->contains($request->ruta_codigo)
            ? $request->ruta_codigo : $rutaCodigos->first();

        $tipo = $request->tipo ?? 'IDA';
        $fecha = $request->fecha ?? date('Y-m-d');

        $estudiantesRuta = EstudianteRuta::with('estudiante.curso')
            ->where('ruta_codigo', $rutaSeleccionada)->activo()->get();

        $registros = AsistenciaTransporte::with('estudiante.curso')
            ->where('ruta_codigo', $rutaSeleccionada)
            ->where('tasis_fecha', $fecha)
            ->where('tasis_tipo', $tipo)
            ->orderBy('tasis_hora', 'desc')->get();

        $rutas = $asignaciones->map(fn($a) => $a->ruta)->filter()->unique('ruta_codigo');

        return view('chofer-portal.asistencia', compact('rutas', 'rutaSeleccionada', 'tipo', 'fecha', 'estudiantesRuta', 'registros'));
    }

    // ── Guardar registro (AJAX) ────────────────────────────────
    public function guardarAsistencia(Request $request)
    {
        $request->validate([
            'ruta_codigo' => 'required',
            'est_codigo' => 'required',
            'tipo' => 'required|in:IDA,VUELTA',
            'fecha' => 'required|date'
        ]);

        // Verificar que el chofer tiene esta ruta asignada
        $rutaCodigos = $this->getRutaCodigos();
        if (!$rutaCodigos->contains($request->ruta_codigo)) {
            return response()->json(['success' => false, 'message' => 'Ruta no asignada']);
        }

        // Verificar que el estudiante pertenece a la ruta
        $enRuta = EstudianteRuta::where('ruta_codigo', $request->ruta_codigo)
            ->where('est_codigo', $request->est_codigo)->activo()->exists();
        if (!$enRuta) {
            return response()->json(['success' => false, 'message' => 'Estudiante no pertenece a esta ruta']);
        }

        // Verificar duplicado
        $existe = AsistenciaTransporte::where('ruta_codigo', $request->ruta_codigo)
            ->where('est_codigo', $request->est_codigo)
            ->where('tasis_fecha', $request->fecha)
            ->where('tasis_tipo', $request->tipo)->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya se registró la asistencia de este estudiante']);
        }

        $est = Estudiante::where('est_codigo', $request->est_codigo)->with('curso')->first();
        if (!$est) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado']);
        }

        AsistenciaTransporte::create([
            'tasis_codigo' => 'TASIS' . time() . rand(10, 99),
            'ruta_codigo' => $request->ruta_codigo,
            'est_codigo' => $request->est_codigo,
            'tasis_fecha' => $request->fecha,
            'tasis_tipo' => $request->tipo,
            'tasis_hora' => now()->format('H:i:s'),
            'tasis_observacion' => $request->observacion,
            'tasis_registrado_por' => auth()->user()->us_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada',
            'estudiante' => [
                'codigo' => $est->est_codigo,
                'nombre' => $est->est_apellidos . ' ' . $est->est_nombres,
                'curso' => $est->curso->cur_nombre ?? '',
                'hora' => now()->format('H:i:s'),
            ]
        ]);
    }

    // ── Eliminar registro ──────────────────────────────────────
    public function eliminarAsistencia($id)
    {
        $reg = AsistenciaTransporte::findOrFail($id);
        $rutaCodigos = $this->getRutaCodigos();
        if (!$rutaCodigos->contains($reg->ruta_codigo)) {
            return back()->with('error', 'No autorizado');
        }
        $reg->delete();
        return back()->with('success', 'Registro eliminado');
    }

    // ── Historial ──────────────────────────────────────────────
    public function historial(Request $request)
    {
        $asignaciones = $this->getAsignaciones();
        $rutaCodigos = $asignaciones->pluck('ruta_codigo');
        $rutaSeleccionada = $request->ruta_codigo && $rutaCodigos->contains($request->ruta_codigo)
            ? $request->ruta_codigo : $rutaCodigos->first();

        $fechaDesde = $request->fecha_desde ?? now()->startOfMonth()->format('Y-m-d');
        $fechaHasta = $request->fecha_hasta ?? date('Y-m-d');

        $registros = AsistenciaTransporte::with('estudiante.curso')
            ->where('ruta_codigo', $rutaSeleccionada)
            ->whereBetween('tasis_fecha', [$fechaDesde, $fechaHasta])
            ->orderBy('tasis_fecha', 'desc')->orderBy('tasis_tipo')->orderBy('tasis_hora', 'desc')
            ->get();

        $rutas = $asignaciones->map(fn($a) => $a->ruta)->filter()->unique('ruta_codigo');

        return view('chofer-portal.historial', compact('rutas', 'rutaSeleccionada', 'fechaDesde', 'fechaHasta', 'registros'));
    }
}
