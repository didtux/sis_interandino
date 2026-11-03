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
        $this->guardarCapacidades($rol, $request->input('capacidades', []));

        return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente');
    }

    public function edit($id)
    {
        $rol = Rol::with('permisos')->findOrFail($id);
        $modulos = Modulo::principales()->with('hijos')->get();

        $permisosMap = [];
        foreach ($rol->permisos as $p) {
            foreach (\App\Support\Permisos::ACCIONES as $a) {
                $permisosMap[$p->mod_id][$a] = (int) ($p->{'perm_' . $a} ?? 0);
            }
        }
        $capacidadesRol = \Illuminate\Support\Facades\DB::table('rol_capacidades')
            ->where('rol_id', $rol->rol_id)->pluck('cap_slug')->all();

        return view('roles.form', compact('rol', 'modulos', 'permisosMap', 'capacidadesRol'));
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($rol, $request) {
            RolPermiso::where('rol_id', $rol->rol_id)->delete();
            $this->guardarPermisos($rol, $request->input('permisos', []));
            $this->guardarCapacidades($rol, $request->input('capacidades', []));
        });

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
                $fila = ['rol_id' => $rol->rol_id, 'mod_id' => $modId];
                foreach (\App\Support\Permisos::ACCIONES as $a) {
                    $fila['perm_' . $a] = isset($acciones[$a]) ? 1 : 0;
                }
                RolPermiso::create($fila);
            }
        }
        \App\Support\Permisos::olvidar();
    }

    /** Capacidades especiales (config/permisos.php) marcadas para el rol. */
    private function guardarCapacidades(Rol $rol, array $capacidades)
    {
        $validas = array_intersect($capacidades, array_keys(config('permisos.capacidades', [])));
        \Illuminate\Support\Facades\DB::table('rol_capacidades')->where('rol_id', $rol->rol_id)->delete();
        foreach (array_unique($validas) as $slug) {
            \Illuminate\Support\Facades\DB::table('rol_capacidades')->insert(['rol_id' => $rol->rol_id, 'cap_slug' => $slug]);
        }
        \App\Support\Permisos::olvidar();
    }
}
