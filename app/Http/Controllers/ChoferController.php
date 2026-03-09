<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use Illuminate\Http\Request;

class ChoferController extends Controller
{
    public function index()
    {
        $choferes = Chofer::orderBy('chof_fecha_registro', 'desc')->get();
        return view('transporte.choferes.index', compact('choferes'));
    }

    public function create()
    {
        return view('transporte.choferes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'chof_nombres' => 'required',
            'chof_apellidos' => 'required',
            'chof_ci' => 'required|unique:transporte_choferes,chof_ci',
            'chof_licencia' => 'required',
            'chof_foto' => 'nullable|image|max:2048'
        ]);

        $data = [
            'chof_codigo' => 'CHOF' . time(),
            'chof_nombres' => $request->chof_nombres,
            'chof_apellidos' => $request->chof_apellidos,
            'chof_ci' => $request->chof_ci,
            'chof_licencia' => $request->chof_licencia,
            'chof_telefono' => $request->chof_telefono,
            'chof_direccion' => $request->chof_direccion,
            'chof_fecha_nacimiento' => $request->chof_fecha_nacimiento,
            'chof_usuario_registro' => auth()->user()->us_codigo
        ];

        if ($request->hasFile('chof_foto')) {
            $data['chof_foto'] = $request->file('chof_foto')->store('choferes', 'public');
        }

        Chofer::create($data);

        return redirect()->route('choferes.index')->with('success', 'Chofer registrado');
    }

    public function edit($id)
    {
        $chofer = Chofer::findOrFail($id);
        return view('transporte.choferes.edit', compact('chofer'));
    }

    public function update(Request $request, $id)
    {
        $chofer = Chofer::findOrFail($id);
        
        $request->validate([
            'chof_nombres' => 'required',
            'chof_apellidos' => 'required',
            'chof_ci' => 'required|unique:transporte_choferes,chof_ci,' . $id . ',chof_id',
            'chof_licencia' => 'required',
            'chof_foto' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('chof_foto');
        
        if ($request->hasFile('chof_foto')) {
            $data['chof_foto'] = $request->file('chof_foto')->store('choferes', 'public');
        }

        $chofer->update($data);
        return redirect()->route('choferes.index')->with('success', 'Chofer actualizado');
    }

    public function destroy($id)
    {
        $chofer = Chofer::findOrFail($id);
        $chofer->update(['chof_estado' => 0]);
        return redirect()->route('choferes.index')->with('success', 'Chofer eliminado');
    }
}
