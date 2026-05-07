<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelController extends Controller
{
    public function index()
    {
        $niveles = Nivel::ordenado()->get();
        return view('niveles.index', compact('niveles'));
    }

    public function create()
    {
        return view('niveles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'niv_nombre'    => 'required|max:50|unique:colegio_niveles,niv_nombre',
            'niv_abreviado' => 'nullable|max:20',
            'niv_orden'     => 'nullable|integer|min:0',
            'niv_estado'    => 'nullable|in:0,1',
        ]);

        $data['niv_estado'] = $data['niv_estado'] ?? 1;
        $data['niv_orden']  = $data['niv_orden']  ?? 0;

        Nivel::create($data);
        return redirect()->route('niveles.index')->with('success', 'Nivel creado exitosamente');
    }

    public function edit($id)
    {
        $nivel = Nivel::findOrFail($id);
        return view('niveles.edit', compact('nivel'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'niv_nombre'    => 'required|max:50|unique:colegio_niveles,niv_nombre,'.$id.',niv_id',
            'niv_abreviado' => 'nullable|max:20',
            'niv_orden'     => 'nullable|integer|min:0',
            'niv_estado'    => 'nullable|in:0,1',
        ]);

        $nivel = Nivel::findOrFail($id);
        $nivel->update($data);

        return redirect()->route('niveles.index')->with('success', 'Nivel actualizado');
    }

    public function destroy($id)
    {
        $nivel = Nivel::findOrFail($id);
        $nivel->update(['niv_estado' => 0]);
        return redirect()->route('niveles.index')->with('success', 'Nivel desactivado');
    }
}
