<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehiculo::query();
        
        if ($request->buscar) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('veh_numero_bus', 'like', "%{$buscar}%")
                  ->orWhere('veh_placa', 'like', "%{$buscar}%")
                  ->orWhere('veh_marca', 'like', "%{$buscar}%")
                  ->orWhere('veh_modelo', 'like', "%{$buscar}%");
            });
        }
        
        $vehiculos = $query->orderBy('veh_fecha_registro', 'desc')->get();
        return view('transporte.vehiculos.index', compact('vehiculos'));
    }

    public function create()
    {
        return view('transporte.vehiculos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'veh_numero_bus' => 'nullable|unique:transporte_vehiculos,veh_numero_bus',
            'veh_placa' => 'required|unique:transporte_vehiculos,veh_placa',
            'veh_marca' => 'required',
            'veh_capacidad' => 'required|integer|min:1'
        ]);

        Vehiculo::create([
            'veh_codigo' => 'VEH' . time(),
            'veh_numero_bus' => $request->veh_numero_bus,
            'veh_placa' => strtoupper($request->veh_placa),
            'veh_marca' => $request->veh_marca,
            'veh_modelo' => $request->veh_modelo,
            'veh_anio' => $request->veh_anio,
            'veh_capacidad' => $request->veh_capacidad,
            'veh_color' => $request->veh_color,
            'veh_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo registrado');
    }

    public function edit($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        return view('transporte.vehiculos.edit', compact('vehiculo'));
    }

    public function update(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        
        $request->validate([
            'veh_numero_bus' => 'nullable|unique:transporte_vehiculos,veh_numero_bus,' . $id . ',veh_id',
            'veh_placa' => 'required|unique:transporte_vehiculos,veh_placa,' . $id . ',veh_id',
            'veh_marca' => 'required',
            'veh_capacidad' => 'required|integer|min:1'
        ]);

        $vehiculo->update([
            'veh_numero_bus' => $request->veh_numero_bus,
            'veh_placa' => strtoupper($request->veh_placa),
            'veh_marca' => $request->veh_marca,
            'veh_modelo' => $request->veh_modelo,
            'veh_anio' => $request->veh_anio,
            'veh_capacidad' => $request->veh_capacidad,
            'veh_color' => $request->veh_color,
            'veh_estado' => $request->veh_estado
        ]);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado');
    }

    public function destroy($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->update(['veh_estado' => 0]);
        return redirect()->route('vehiculos.index')->with('success', 'Vehículo eliminado');
    }
}
