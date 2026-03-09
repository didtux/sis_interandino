<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;

class DescuentoController extends Controller
{
    public function index()
    {
        $descuentos = Descuento::orderBy('desc_fecha_registro', 'desc')->get();
        return view('descuentos.index', compact('descuentos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'desc_nombre' => 'required|string|max:100',
            'desc_porcentaje' => 'required|numeric|min:0|max:100'
        ]);

        Descuento::create([
            'desc_codigo' => 'DESC' . time(),
            'desc_nombre' => $request->desc_nombre,
            'desc_porcentaje' => $request->desc_porcentaje,
            'desc_estado' => 1
        ]);

        return redirect()->route('descuentos.index')->with('success', 'Descuento creado');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'desc_nombre' => 'required|string|max:100',
            'desc_porcentaje' => 'required|numeric|min:0|max:100'
        ]);

        $descuento = Descuento::findOrFail($id);
        $descuento->update([
            'desc_nombre' => $request->desc_nombre,
            'desc_porcentaje' => $request->desc_porcentaje
        ]);

        return redirect()->route('descuentos.index')->with('success', 'Descuento actualizado');
    }

    public function destroy($id)
    {
        $descuento = Descuento::findOrFail($id);
        $descuento->update(['desc_estado' => 0]);
        return redirect()->route('descuentos.index')->with('success', 'Descuento eliminado');
    }
}
