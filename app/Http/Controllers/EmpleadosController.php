<?php

namespace App\Http\Controllers;

use App\Models\Barbero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpleadosController extends Controller
{
    public function index()
    {
        $barberia = Auth::user()->barberia;
        
        $empleados = $barberia->barberos()
            ->with('user')
            ->withCount(['citas as citas_completadas' => function($query) {
                $query->where('estado', 'completada');
            }])
            ->withSum(['citas as ingresos_generados' => function($query) {
                $query->where('estado', 'completada');
            }], 'precio_cobrado')
            ->get();

        return view('admin.empleados', compact('empleados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'color_calendario' => 'required|string|max:7',
        ]);

        $barberia = Auth::user()->barberia;

        $barbero = Barbero::create([
            'barberia_id' => $barberia->id,
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'color_calendario' => $request->color_calendario,
            'activo' => true,
        ]);

        \App\Models\User::create([
            'name' => $request->nombre,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'barberia_id' => $barberia->id,
            'rol' => 'empleado',
            'barbero_id' => $barbero->id,
        ]);

        return back()->with('success', "Especialista agregado. Envíale estas credenciales para acceder: Correo: {$request->email} | Contraseña: {$request->password}");
    }

    public function update(Request $request, Barbero $empleado)
    {
        if ($empleado->barberia_id !== Auth::user()->barberia->id) {
            abort(403);
        }

        $user = \App\Models\User::where('barbero_id', $empleado->id)->first();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . ($user ? $user->id : 'NULL'),
            'password' => 'nullable|string|min:8',
            'color_calendario' => 'required|string|max:7',
            'activo' => 'boolean',
        ]);

        $empleado->update([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'color_calendario' => $request->color_calendario,
            'activo' => $request->has('activo'),
        ]);

        if ($user) {
            $updateData = [
                'name' => $request->nombre,
                'email' => $request->email,
            ];
            if ($request->filled('password')) {
                $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
            }
            $user->update($updateData);
        } else {
            \App\Models\User::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->filled('password') ? $request->password : '12345678'),
                'barberia_id' => $empleado->barberia_id,
                'rol' => 'empleado',
                'barbero_id' => $empleado->id,
            ]);
        }

        return back()->with('success', 'Especialista actualizado correctamente.');
    }

    public function destroy(Barbero $empleado)
    {
        if ($empleado->barberia_id !== Auth::user()->barberia->id) {
            abort(403);
        }

        // En lugar de borrar, mejor desactivar para no romper citas historicas
        $empleado->update(['activo' => false]);

        return back()->with('success', 'Especialista desactivado correctamente.');
    }
}
