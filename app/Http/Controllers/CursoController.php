<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        $cursos = Curso::visible()->withCount('estudiantes')->paginate(15);
        return view('cursos.index', compact('cursos'));
    }

    public function create()
    {
        return view('cursos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cur_codigo' => 'required|unique:colegio_cursos,cur_codigo',
            'cur_nombre' => 'required|max:20'
        ]);

        Curso::create($request->all());
        return redirect()->route('cursos.index')->with('success', 'Curso creado exitosamente');
    }

    public function show($id)
    {
        $curso = Curso::with('estudiantes')->findOrFail($id);
        return view('cursos.show', compact('curso'));
    }

    public function edit($id)
    {
        $curso = Curso::findOrFail($id);
        return view('cursos.edit', compact('curso'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cur_nombre' => 'required|max:20'
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update($request->all());
        return redirect()->route('cursos.index')->with('success', 'Curso actualizado exitosamente');
    }

    public function destroy($id)
    {
        $curso = Curso::findOrFail($id);
        $curso->update(['cur_visible' => 0]);
        return redirect()->route('cursos.index')->with('success', 'Curso eliminado exitosamente');
    }
}
