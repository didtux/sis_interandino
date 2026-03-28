<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with('rol')->orderBy('us_id', 'desc')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Rol::activo()->get();
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'us_codigo' => 'required|unique:rol_usuarios,us_codigo',
            'us_ci' => 'required|unique:rol_usuarios,us_ci',
            'us_nombres' => 'required',
            'us_apellidos' => 'required',
            'us_user' => 'required|unique:rol_usuarios,us_user',
            'us_pass' => 'required|min:6',
            'rol_id' => 'required|integer'
        ]);

        User::create([
            'us_codigo' => $request->us_codigo,
            'rol_id' => $request->rol_id,
            'us_ci' => $request->us_ci,
            'us_nombres' => $request->us_nombres,
            'us_apellidos' => $request->us_apellidos,
            'us_user' => $request->us_user,
            'us_pass' => Hash::make($request->us_pass),
            'us_visible' => 1
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente');
    }

    public function show(User $usuario)
    {
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $roles = Rol::activo()->get();
        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'us_codigo' => 'required|unique:rol_usuarios,us_codigo,' . $usuario->us_id . ',us_id',
            'us_ci' => 'required|unique:rol_usuarios,us_ci,' . $usuario->us_id . ',us_id',
            'us_nombres' => 'required',
            'us_apellidos' => 'required',
            'us_user' => 'required|unique:rol_usuarios,us_user,' . $usuario->us_id . ',us_id',
            'rol_id' => 'required|integer'
        ]);

        $data = [
            'us_codigo' => $request->us_codigo,
            'rol_id' => $request->rol_id,
            'us_ci' => $request->us_ci,
            'us_nombres' => $request->us_nombres,
            'us_apellidos' => $request->us_apellidos,
            'us_user' => $request->us_user,
            'us_visible' => $request->has('us_visible') ? 1 : 0
        ];

        if ($request->filled('us_pass')) {
            $data['us_pass'] = Hash::make($request->us_pass);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        $usuario->update(['us_visible' => 0]);
        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente');
    }
}
