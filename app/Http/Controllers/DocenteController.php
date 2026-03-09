<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;

class DocenteController extends Controller
{
    public function index()
    {
        $docentes = Docente::visible()->paginate(20);
        return view('docentes.index', compact('docentes'));
    }

    public function create()
    {
        $ultimoDocente = Docente::orderBy('doc_id', 'desc')->first();
        $siguienteNumero = $ultimoDocente ? intval(substr($ultimoDocente->doc_codigo, 3)) + 1 : 1;
        $codigoGenerado = 'DOC' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);
        
        return view('docentes.create', compact('codigoGenerado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'doc_nombres' => 'required|max:30',
            'doc_apellidos' => 'nullable|max:30',
            'doc_ci' => 'nullable|max:20'
        ]);

        $ultimoDocente = Docente::orderBy('doc_id', 'desc')->first();
        $siguienteNumero = $ultimoDocente ? intval(substr($ultimoDocente->doc_codigo, 3)) + 1 : 1;
        $codigoGenerado = 'DOC' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);

        Docente::create(array_merge($request->all(), ['doc_codigo' => $codigoGenerado]));
        return redirect()->route('docentes.index')->with('success', 'Docente creado exitosamente');
    }

    public function show($id)
    {
        $docente = Docente::with('cursos')->findOrFail($id);
        return view('docentes.show', compact('docente'));
    }

    public function edit($id)
    {
        $docente = Docente::findOrFail($id);
        return view('docentes.edit', compact('docente'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'doc_nombres' => 'required|max:30',
            'doc_apellidos' => 'nullable|max:30',
            'doc_ci' => 'nullable|max:20'
        ]);

        $docente = Docente::findOrFail($id);
        $docente->update($request->all());
        return redirect()->route('docentes.index')->with('success', 'Docente actualizado exitosamente');
    }

    public function destroy($id)
    {
        $docente = Docente::findOrFail($id);
        $docente->update(['doc_visible' => 0]);
        return redirect()->route('docentes.index')->with('success', 'Docente eliminado exitosamente');
    }
}
