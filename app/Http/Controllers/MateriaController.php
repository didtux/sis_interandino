<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\MateriaGrupo;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index()
    {
        $materias = Materia::visible()->orderBy('mat_campo')->orderBy('mat_orden')->paginate(20);
        $grupos = MateriaGrupo::activo()->with('materias')->orderBy('grupo_nombre')->get();
        $todasMaterias = Materia::visible()->orderBy('mat_nombre')->get();
        return view('materias.index', compact('materias', 'grupos', 'todasMaterias'));
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

    // ── Grupos de Materias ──────────────────────────────────────────

    public function guardarGrupo(Request $request)
    {
        $request->validate([
            'grupo_nombre' => 'required|max:100',
            'materias'     => 'required|array|min:2',
        ], [
            'materias.min' => 'Un grupo debe tener al menos 2 materias.',
        ]);

        $grupo = MateriaGrupo::updateOrCreate(
            ['grupo_id' => $request->grupo_id],
            ['grupo_nombre' => $request->grupo_nombre]
        );

        // Sincronizar materias con orden
        $grupo->materias()->detach();
        foreach ($request->materias as $i => $matCodigo) {
            $grupo->materias()->attach($matCodigo, ['detalle_orden' => $i]);
        }

        return redirect()->route('materias.index')->with('success', 'Grupo "' . $grupo->grupo_nombre . '" guardado correctamente');
    }

    public function eliminarGrupo($id)
    {
        $grupo = MateriaGrupo::findOrFail($id);
        $nombre = $grupo->grupo_nombre;
        $grupo->delete();
        return redirect()->route('materias.index')->with('success', 'Grupo "' . $nombre . '" eliminado');
    }
}
