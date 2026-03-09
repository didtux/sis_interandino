<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index()
    {
        $servicios = Servicio::orderBy('serv_fecha_registro', 'desc')->paginate(20);
        return view('servicios.index', compact('servicios'));
    }

    public function create()
    {
        return view('servicios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'serv_nombre' => 'required|max:100',
            'serv_costo' => 'required|numeric|min:0'
        ]);

        Servicio::create([
            'serv_codigo' => 'SERV' . time(),
            'serv_nombre' => $request->serv_nombre,
            'serv_descripcion' => $request->serv_descripcion,
            'serv_costo' => $request->serv_costo,
            'serv_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('servicios.index')->with('success', 'Servicio creado exitosamente');
    }

    public function edit($id)
    {
        $servicio = Servicio::findOrFail($id);
        return view('servicios.edit', compact('servicio'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'serv_nombre' => 'required|max:100',
            'serv_costo' => 'required|numeric|min:0'
        ]);

        $servicio = Servicio::findOrFail($id);
        $servicio->update($request->all());

        return redirect()->route('servicios.index')->with('success', 'Servicio actualizado exitosamente');
    }

    public function destroy($id)
    {
        Servicio::findOrFail($id)->update(['serv_estado' => 0]);
        return redirect()->route('servicios.index')->with('success', 'Servicio eliminado exitosamente');
    }
}
