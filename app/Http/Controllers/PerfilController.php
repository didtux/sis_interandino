<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    public function cambiarPassword(Request $request)
    {
        $user = Auth::user();

        // Verificar contraseña actual (hash o texto plano)
        $passwordMatch = Hash::check($request->password_current, $user->us_pass)
            || $user->us_pass === $request->password_current;

        if (!$passwordMatch) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta.'
            ]);
        }

        if (strlen($request->password) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 6 caracteres.'
            ]);
        }

        if ($request->password !== $request->password_confirmation) {
            return response()->json([
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ]);
        }

        $user->us_pass = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }
}
