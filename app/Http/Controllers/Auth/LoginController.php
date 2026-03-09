<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'us_user' => 'required',
            'password' => 'required'
        ]);

        // Buscar por us_user o us_codigo
        $user = User::where(function($query) use ($request) {
                    $query->where('us_user', $request->us_user)
                          ->orWhere('us_codigo', $request->us_user);
                })
                ->where('us_visible', 1)
                ->first();

        if ($user) {
            // Verificar si la contraseña está hasheada o es texto plano
            $passwordMatch = Hash::check($request->password, $user->us_pass) || 
                           $user->us_pass === $request->password;
            
            if ($passwordMatch) {
                Auth::login($user, $request->filled('remember'));
                return redirect()->intended('/home');
            }
        }

        return back()->withErrors(['us_user' => 'Credenciales incorrectas'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}
