<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    public function index()
    {
        $materias = Materia::visible()->orderBy('mat_campo')->orderBy('mat_orden')->paginate(20);

        // Grupos derivados de mat_campo: cada campo único es un "grupo".
        // Se construye una colección de objetos sintéticos para no tocar los blades demasiado.
        $todasMaterias = Materia::visible()->orderBy('mat_campo')->orderBy('mat_orden')->orderBy('mat_nombre')->get();
        $grupos = $todasMaterias
            ->filter(fn($m) => !empty(trim((string) $m->mat_campo)))
            ->groupBy(fn($m) => trim((string) $m->mat_campo))
            ->map(function ($materias, $campo) {
                $cntProm = $materias->where('mat_promediable', 1)->count();
                return (object) [
                    'campo'        => $campo,
                    'materias'     => $materias->values(),
                    'total'        => $materias->count(),
                    'promediables' => $cntProm,
                ];
            })
            ->sortKeys()
            ->values();

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

        $data = $request->all();
        $data['mat_promediable'] = $request->has('mat_promediable') ? 1 : ($request->filled('mat_campo') ? 1 : 1);
        Materia::create($data);
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

        $materia = Materia::findOrFail($id);
        $data = $request->all();
        $data['mat_promediable'] = $request->has('mat_promediable') ? 1 : 0;
        $materia->update($data);
        return redirect()->route('materias.index')->with('success', 'Materia actualizada exitosamente');
    }

    public function destroy($id)
    {
        Materia::findOrFail($id)->update(['mat_visible' => 0]);
        return redirect()->route('materias.index')->with('success', 'Materia eliminada exitosamente');
    }

    // ── Asignar campo a varias materias a la vez ────────────────────
    public function asignarCampo(Request $request)
    {
        $request->validate([
            'mat_campo' => 'required|max:60',
            'materias'  => 'required|array|min:1',
        ]);
        $campo = trim($request->mat_campo);
        Materia::whereIn('mat_codigo', $request->materias)->update(['mat_campo' => $campo]);
        return redirect()->route('materias.index', ['tab' => 'asociar'])
            ->with('success', count($request->materias) . ' materias asignadas al campo "' . $campo . '"');
    }

    // ── Marcar qué materias de un campo suman al promedio ───────────
    public function guardarPromediables(Request $request)
    {
        $request->validate(['mat_campo' => 'required']);
        $campo = trim($request->mat_campo);
        $promediables = $request->input('promediables', []);

        // Materias del campo: las marcadas → mat_promediable=1; las no marcadas → 0
        $materiasCampo = Materia::where('mat_campo', $campo)->pluck('mat_codigo');
        foreach ($materiasCampo as $cod) {
            Materia::where('mat_codigo', $cod)
                ->update(['mat_promediable' => in_array($cod, $promediables) ? 1 : 0]);
        }

        return redirect()
            ->to(route('materias.index', ['tab' => 'grupos']) . '#tabGrupos')
            ->with('success', 'Promediables actualizados para "' . $campo . '"');
    }
}
