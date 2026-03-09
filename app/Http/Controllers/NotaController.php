<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Curso;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    public function index()
    {
        $notas = Nota::with('estudiante', 'docente', 'curso')->paginate(20);
        return view('notas.index', compact('notas'));
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->get();
        $docentes = Docente::visible()->get();
        $cursos = Curso::visible()->get();
        return view('notas.create', compact('estudiantes', 'docentes', 'cursos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notas_codigo' => 'required',
            'est_codigo' => 'required',
            'doc_codigo' => 'required',
            'cur_codigo' => 'required'
        ]);

        Nota::create($request->all());
        return redirect()->route('notas.index')->with('success', 'Nota registrada exitosamente');
    }

    public function edit($id)
    {
        $nota = Nota::findOrFail($id);
        $estudiantes = Estudiante::visible()->get();
        $docentes = Docente::visible()->get();
        $cursos = Curso::visible()->get();
        return view('notas.edit', compact('nota', 'estudiantes', 'docentes', 'cursos'));
    }

    public function update(Request $request, $id)
    {
        Nota::findOrFail($id)->update($request->all());
        return redirect()->route('notas.index')->with('success', 'Nota actualizada exitosamente');
    }
}
