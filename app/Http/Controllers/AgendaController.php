<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index()
    {
        $agendas = Agenda::activo()->with('estudiante')->orderBy('age_fechahora', 'desc')->paginate(20);
        return view('agenda.index', compact('agendas'));
    }

    public function create()
    {
        $estudiantes = Estudiante::visible()->get();
        return view('agenda.create', compact('estudiantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'age_codigo' => 'required',
            'age_tipo' => 'required',
            'age_titulo' => 'required|max:50',
            'age_detalles' => 'required'
        ]);

        Agenda::create($request->all());
        return redirect()->route('agenda.index')->with('success', 'Registro creado exitosamente');
    }

    public function destroy($id)
    {
        Agenda::findOrFail($id)->update(['age_estado' => 0]);
        return redirect()->route('agenda.index')->with('success', 'Registro eliminado exitosamente');
    }
}
