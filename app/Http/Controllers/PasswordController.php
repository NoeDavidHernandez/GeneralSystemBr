<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function showSetPasswordForm()
    {
        // Si el usuario no tiene que cambiar la contraseña, redirigirlo a su dashboard
        if (!auth()->user()->must_change_password) {
            return redirect('/');
        }
        
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        if (!auth()->user()->must_change_password) {
            return redirect('/');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect('/')->with('success', 'Contraseña actualizada con éxito. ¡Bienvenido!');
    }
}
