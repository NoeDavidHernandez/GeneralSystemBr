<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NlogicTeamController extends Controller
{
    public function index()
    {
        $team = User::where('is_superadmin', true)->get();
        return view('superadmin.team.index', compact('team'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_superadmin' => true,
            'rol' => 'admin', // Puede ser irrelevante ya que is_superadmin toma precedencia
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Socio agregado al equipo NLogic correctamente.');
    }

    public function destroy(User $user)
    {
        // Evitar que el usuario se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();
        return back()->with('success', 'Socio eliminado del equipo.');
    }
}
