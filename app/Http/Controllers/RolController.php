<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Modulo;
use App\Models\RolPermiso;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::activo()->withCount('usuarios')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $modulos = Modulo::principales()->with('hijos')->get();
        $permisosMap = [];
        return view('roles.form', compact('modulos', 'permisosMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rol_nombre' => 'required|max:50|unique:rol_roles,rol_nombre',
        ]);

        $rol = Rol::create([
            'rol_nombre' => $request->rol_nombre,
            'rol_descripcion' => $request->rol_descripcion,
        ]);

        $this->guardarPermisos($rol, $request->input('permisos', []));

        return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente');
    }

    public function edit($id)
    {
        $rol = Rol::with('permisos')->findOrFail($id);
        $modulos = Modulo::principales()->with('hijos')->get();

        $permisosMap = [];
        foreach ($rol->permisos as $p) {
            $permisosMap[$p->mod_id] = [
                'ver' => $p->perm_ver,
                'crear' => $p->perm_crear,
                'editar' => $p->perm_editar,
                'eliminar' => $p->perm_eliminar,
            ];
        }

        return view('roles.form', compact('rol', 'modulos', 'permisosMap'));
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        $request->validate([
            'rol_nombre' => 'required|max:50|unique:rol_roles,rol_nombre,' . $id . ',rol_id',
        ]);

        $rol->update([
            'rol_nombre' => $request->rol_nombre,
            'rol_descripcion' => $request->rol_descripcion,
        ]);

        RolPermiso::where('rol_id', $rol->rol_id)->delete();
        $this->guardarPermisos($rol, $request->input('permisos', []));

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente');
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);
        if ($rol->rol_id == 1) {
            return back()->with('error', 'No se puede eliminar el rol Administrador');
        }
        $rol->update(['rol_visible' => 0]);
        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente');
    }

    private function guardarPermisos(Rol $rol, array $permisos)
    {
        foreach ($permisos as $modId => $acciones) {
            if (!empty($acciones)) {
                RolPermiso::create([
                    'rol_id' => $rol->rol_id,
                    'mod_id' => $modId,
                    'perm_ver' => isset($acciones['ver']) ? 1 : 0,
                    'perm_crear' => isset($acciones['crear']) ? 1 : 0,
                    'perm_editar' => isset($acciones['editar']) ? 1 : 0,
                    'perm_eliminar' => isset($acciones['eliminar']) ? 1 : 0,
                ]);
            }
        }
    }
}
