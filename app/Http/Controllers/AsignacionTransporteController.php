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

    /**
     * Alta de la asignación. El chofer y el vehículo se pueden crear acá mismo
     * (nuevo_chofer / nuevo_vehiculo): antes había que pasar por tres módulos
     * distintos — Choferes, Vehículos y recién Asignaciones — para poder dejar
     * un bus andando.
     */
    public function store(Request $request)
    {
        // Se validan los dos altas ANTES de crear nada: si el vehículo no pasa la
        // validación, no puede quedar un chofer huérfano ya guardado.
        if ($request->boolean('nuevo_chofer')) {
            $request->validate([
                'chof_nombres'   => 'required',
                'chof_apellidos' => 'required',
                'chof_ci'        => 'required|unique:transporte_choferes,chof_ci',
                'chof_licencia'  => 'required',
            ]);
        }
        if ($request->boolean('nuevo_vehiculo')) {
            $request->validate([
                'veh_placa'      => 'required|unique:transporte_vehiculos,veh_placa',
                'veh_marca'      => 'required',
                'veh_capacidad'  => 'required|integer|min:1',
                'veh_numero_bus' => 'nullable|unique:transporte_vehiculos,veh_numero_bus',
            ]);
        }

        $chofCodigo = $request->chof_codigo;
        $vehCodigo  = $request->veh_codigo;

        if ($request->boolean('nuevo_chofer')) {
            $chofCodigo = Chofer::create([
                'chof_codigo'            => 'CHOF' . time(),
                'chof_nombres'           => $request->chof_nombres,
                'chof_apellidos'         => $request->chof_apellidos,
                'chof_ci'                => $request->chof_ci,
                'chof_licencia'          => $request->chof_licencia,
                'chof_telefono'          => $request->chof_telefono,
                'chof_usuario_registro'  => auth()->user()->us_codigo,
            ])->chof_codigo;
        }

        if ($request->boolean('nuevo_vehiculo')) {
            $vehCodigo = Vehiculo::create([
                'veh_codigo'            => 'VEH' . time(),
                'veh_numero_bus'        => $request->veh_numero_bus,
                'veh_placa'             => strtoupper($request->veh_placa),
                'veh_marca'             => $request->veh_marca,
                'veh_modelo'            => $request->veh_modelo,
                'veh_capacidad'         => $request->veh_capacidad,
                'veh_color'             => $request->veh_color,
                'veh_usuario_registro'  => auth()->user()->us_codigo,
            ])->veh_codigo;
        }

        $request->merge(['chof_codigo' => $chofCodigo, 'veh_codigo' => $vehCodigo]);
        $request->validate([
            'chof_codigo' => 'required',
            'veh_codigo' => 'required',
            'ruta_codigo' => 'required',
            'asig_fecha_inicio' => 'required|date'
        ]);

        AsignacionTransporte::create([
            'asig_codigo' => 'ASIG' . time(),
            'chof_codigo' => $chofCodigo,
            'veh_codigo' => $vehCodigo,
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
