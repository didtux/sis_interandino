<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index()
    {
        $materias = Materia::visible()->paginate(20);
        return view('materias.index', compact('materias'));
    }

    public function create()
    {
        return view('materias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'mat_codigo' => 'required|unique:colegio_materias,mat_codigo',
            'mat_nombre' => 'required|max:50'
        ]);

        Materia::create($request->all());
        return redirect()->route('materias.index')->with('success', 'Materia creada exitosamente');
    }

    public function edit($id)
    {
        $materia = Materia::findOrFail($id);
        return view('materias.edit', compact('materia'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['mat_nombre' => 'required|max:50']);

        Materia::findOrFail($id)->update($request->all());
        return redirect()->route('materias.index')->with('success', 'Materia actualizada exitosamente');
    }

    public function destroy($id)
    {
        Materia::findOrFail($id)->update(['mat_visible' => 0]);
        return redirect()->route('materias.index')->with('success', 'Materia eliminada exitosamente');
    }
}
