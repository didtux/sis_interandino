<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * destroy() da de baja lógica (prov_estado = 0), pero acá no se filtraba por
     * estado: el proveedor volvía a aparecer igual y parecía que "no elimina".
     */
    public function index()
    {
        $proveedores = Proveedor::activo()->orderBy('prov_nombre')->paginate(50);
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'prov_nombre' => 'required|max:100',
            'prov_telefono' => 'nullable|max:20',
            'prov_email' => 'nullable|email|max:100'
        ]);

        Proveedor::create([
            'prov_codigo' => 'PROV' . time(),
            'prov_nombre' => $request->prov_nombre,
            'prov_razon_social' => $request->prov_razon_social,
            'prov_nit' => $request->prov_nit,
            'prov_telefono' => $request->prov_telefono,
            'prov_email' => $request->prov_email,
            'prov_direccion' => $request->prov_direccion,
            'prov_contacto' => $request->prov_contacto,
            'prov_usuario_registro' => auth()->user()->us_codigo
        ]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado');
    }

    public function edit($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($request->all());
        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado');
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        // La baja es lógica, así que la base nunca se queja. Se avisa acá para
        // que no desaparezca un proveedor que todavía respalda stock o compras.
        $productos    = $proveedor->productos()->count();
        $movimientos  = $proveedor->movimientos()->count();
        if ($productos > 0 || $movimientos > 0) {
            return redirect()->route('proveedores.index')->with(
                'error',
                "No se dio de baja «{$proveedor->prov_nombre}»: tiene {$productos} producto(s) y {$movimientos} movimiento(s) asociados."
            );
        }

        $proveedor->update(['prov_estado' => 0]);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado');
    }
}
