<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionController extends Controller
{
    public function index()
    {
        $gestiones = Gestion::orderBy('ges_anio', 'desc')->get();
        return view('gestiones.index', compact('gestiones'));
    }

    public function create()
    {
        return view('gestiones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ges_anio'      => 'required|max:10|unique:colegio_gestiones,ges_anio',
            'ges_nombre'    => 'required|max:80',
            'ges_abreviado' => 'nullable|max:20',
            'ges_estado'    => 'nullable|in:0,1',
        ]);

        $data['ges_estado'] = $data['ges_estado'] ?? 0;

        if ($data['ges_estado'] == 1) {
            DB::table('colegio_gestiones')->update(['ges_estado' => 0]);
        }

        Gestion::create($data);
        return redirect()->route('gestiones.index')->with('success', 'Gestión creada');
    }

    public function edit($id)
    {
        $gestion = Gestion::findOrFail($id);
        return view('gestiones.edit', compact('gestion'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'ges_anio'      => 'required|max:10|unique:colegio_gestiones,ges_anio,'.$id.',ges_id',
            'ges_nombre'    => 'required|max:80',
            'ges_abreviado' => 'nullable|max:20',
            'ges_estado'    => 'nullable|in:0,1',
        ]);

        $data['ges_estado'] = $data['ges_estado'] ?? 0;

        if ($data['ges_estado'] == 1) {
            DB::table('colegio_gestiones')->where('ges_id', '!=', $id)->update(['ges_estado' => 0]);
        }

        $gestion = Gestion::findOrFail($id);
        $gestion->update($data);

        return redirect()->route('gestiones.index')->with('success', 'Gestión actualizada');
    }

    public function destroy($id)
    {
        $gestion = Gestion::findOrFail($id);
        $gestion->update(['ges_estado' => 0]);
        return redirect()->route('gestiones.index')->with('success', 'Gestión desactivada');
    }

    public function activar($id)
    {
        DB::table('colegio_gestiones')->update(['ges_estado' => 0]);
        Gestion::where('ges_id', $id)->update(['ges_estado' => 1]);
        return redirect()->route('gestiones.index')->with('success', 'Gestión activada');
    }
}
