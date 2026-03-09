<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTransporte;
use App\Models\Chofer;
use App\Models\Vehiculo;
use App\Models\Ruta;
use Illuminate\Http\Request;

class AsignacionTransporteController extends Controller
{
    public function index()
    {
        $asignaciones = AsignacionTransporte::with(['chofer', 'vehiculo', 'ruta'])
            ->orderBy('asig_fecha_registro', 'desc')->get();
        return view('transporte.asignaciones.index', compact('asignaciones'));
    }

    public function create()
    {
        $choferes = Chofer::activo()->get();
        $vehiculos = Vehiculo::activo()->get();
        $rutas = Ruta::activo()->get();
        return view('transporte.asignaciones.create', compact('choferes', 'vehiculos', 'rutas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'chof_codigo' => 'required',
            'veh_codigo' => 'required',
            'ruta_codigo' => 'required',
            'asig_fecha_inicio' => 'required|date'
        ]);

        AsignacionTransporte::create([
            'asig_codigo' => 'ASIG' . time(),
            'chof_codigo' => $request->chof_codigo,
            'veh_codigo' => $request->veh_codigo,
            'ruta_codigo' => $request->ruta_codigo,
            'asig_fecha_inicio' => $request->asig_fecha_inicio,
            'asig_fecha_fin' => $request->asig_fecha_fin,
            'asig_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('asignaciones-transporte.index')->with('success', 'Asignación registrada');
    }

    public function edit($id)
    {
        $asignacion = AsignacionTransporte::findOrFail($id);
        $choferes = Chofer::activo()->get();
        $vehiculos = Vehiculo::activo()->get();
        $rutas = Ruta::activo()->get();
        return view('transporte.asignaciones.edit', compact('asignacion', 'choferes', 'vehiculos', 'rutas'));
    }

    public function update(Request $request, $id)
    {
        $asignacion = AsignacionTransporte::findOrFail($id);
        
        $request->validate([
            'chof_codigo' => 'required',
            'veh_codigo' => 'required',
            'ruta_codigo' => 'required',
            'asig_fecha_inicio' => 'required|date'
        ]);

        $asignacion->update($request->all());
        return redirect()->route('asignaciones-transporte.index')->with('success', 'Asignación actualizada');
    }

    public function destroy($id)
    {
        $asignacion = AsignacionTransporte::findOrFail($id);
        $asignacion->update(['asig_estado' => 0]);
        return redirect()->route('asignaciones-transporte.index')->with('success', 'Asignación eliminada');
    }
}
