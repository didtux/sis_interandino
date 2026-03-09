<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::visible()->withCount('productos')->paginate(50);
        return view('categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate(['categ_nombre' => 'required']);

        Categoria::create([
            'categ_codigo' => 'CAT' . time(),
            'categ_nombre' => $request->categ_nombre
        ]);

        return redirect()->back()->with('success', 'Categoría creada');
    }

    public function update(Request $request, $id)
    {
        Categoria::findOrFail($id)->update($request->all());
        return redirect()->back()->with('success', 'Categoría actualizada');
    }

    public function destroy($id)
    {
        Categoria::findOrFail($id)->update(['categ_visible' => 0]);
        return redirect()->back()->with('success', 'Categoría eliminada');
    }
}
