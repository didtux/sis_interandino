<?php

namespace App\Http\Controllers;

use App\Models\EstudianteRuta;
use App\Models\Estudiante;
use App\Models\Ruta;
use App\Models\PagoTransporte;
use Illuminate\Http\Request;

class EstudianteRutaController extends Controller
{
    public function index(Request $request)
    {
        $query = EstudianteRuta::with(['estudiante.curso', 'ruta.asignaciones.vehiculo', 'pago']);
        
        if ($request->buscar) {
            $buscar = $request->buscar;
            $query->whereHas('estudiante', function($q) use ($buscar) {
                $q->where('est_nombres', 'like', "%{$buscar}%")
                  ->orWhere('est_apellidos', 'like', "%{$buscar}%")
                  ->orWhere('est_ci', 'like', "%{$buscar}%");
            });
        }
        
        if ($request->ruta_codigo) {
            $query->where('ruta_codigo', $request->ruta_codigo);
        }

        if ($request->cur_codigo) {
            $query->whereHas('estudiante', fn($q) => $q->where('cur_codigo', $request->cur_codigo));
        }

        if ($request->estado === 'suspendido') {
            $query->where('ter_suspendido', 1);
        } elseif ($request->estado === 'activo') {
            $query->where('ter_estado', 1)->where('ter_suspendido', 0);
        } elseif ($request->estado === 'inactivo') {
            $query->where('ter_estado', 0);
        }
        
        $asignaciones = $query->where('ter_estado', '>=', 0)->orderBy('ter_fecha_registro', 'desc')->get();
        $rutas = Ruta::activo()->with('asignaciones.vehiculo')->get();
        $cursos = \App\Models\Curso::visible()->orderBy('cur_nombre')->get();
        return view('transporte.estudiantes-rutas.index', compact('asignaciones', 'rutas', 'cursos'));
    }

    public function create()
    {
        $rutas = Ruta::activo()->get();
        $pagos = PagoTransporte::vigente()->with('estudiante')->get();
        
        // Obtener solo estudiantes que tienen pagos vigentes
        $estudiantesConPago = $pagos->pluck('est_codigo')->unique();
        $estudiantes = Estudiante::visible()
            ->whereIn('est_codigo', $estudiantesConPago)
            ->get();
        
        return view('transporte.estudiantes-rutas.create', compact('estudiantes', 'rutas', 'pagos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'est_codigo' => 'required',
            'ruta_codigo' => 'required',
            'tpago_codigo' => 'required'
        ]);

        EstudianteRuta::create([
            'ter_codigo' => 'TER' . time(),
            'est_codigo' => $request->est_codigo,
            'ruta_codigo' => $request->ruta_codigo,
            'tpago_codigo' => $request->tpago_codigo,
            'ter_direccion_recogida' => $request->ter_direccion_recogida,
            'ter_coordenadas' => $request->ter_coordenadas,
            'ter_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('estudiantes-rutas.index')->with('success', 'Estudiante asignado a ruta');
    }

    public function edit($id)
    {
        $asignacion = EstudianteRuta::findOrFail($id);
        $rutas = Ruta::activo()->get();
        $pagos = PagoTransporte::vigente()->with('estudiante')->get();
        
        // Obtener solo estudiantes que tienen pagos vigentes
        $estudiantesConPago = $pagos->pluck('est_codigo')->unique();
        $estudiantes = Estudiante::visible()
            ->whereIn('est_codigo', $estudiantesConPago)
            ->get();
        
        return view('transporte.estudiantes-rutas.edit', compact('asignacion', 'estudiantes', 'rutas', 'pagos'));
    }

    public function update(Request $request, $id)
    {
        $asignacion = EstudianteRuta::findOrFail($id);
        
        $request->validate([
            'ruta_codigo' => 'required',
            'tpago_codigo' => 'required'
        ]);

        $asignacion->update($request->all());
        return redirect()->route('estudiantes-rutas.index')->with('success', 'Asignación actualizada');
    }

    public function destroy($id)
    {
        $asignacion = EstudianteRuta::findOrFail($id);
        $asignacion->update(['ter_estado' => 0]);
        return redirect()->route('estudiantes-rutas.index')->with('success', 'Asignación eliminada');
    }

    public function suspender(Request $request, $id)
    {
        $asignacion = EstudianteRuta::findOrFail($id);
        $mesSuspension = $request->input('mes', intval(date('n')));
        $asignacion->update([
            'ter_suspendido' => 1,
            'ter_suspendido_desde' => $mesSuspension,
        ]);
        return back()->with('success', 'Servicio suspendido. No generará mora desde este mes.');
    }

    public function reactivar($id)
    {
        $asignacion = EstudianteRuta::findOrFail($id);
        $asignacion->update([
            'ter_suspendido' => 0,
            'ter_suspendido_desde' => null,
        ]);
        return back()->with('success', 'Servicio reactivado.');
    }
}
