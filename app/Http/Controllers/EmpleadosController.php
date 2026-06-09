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

        // Crear usuario para el empleado
        $emailBase = ($request->telefono ? preg_replace('/[^0-9]/', '', $request->telefono) : strtolower(str_replace(' ', '', $request->nombre))) . '@empleado.com';
        
        \App\Models\User::create([
            'name' => $request->nombre,
            'email' => $emailBase,
            'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
            'barberia_id' => $barberia->id,
            'rol' => 'empleado',
            'barbero_id' => $barbero->id,
        ]);

        return back()->with('success', 'Especialista agregado y cuenta creada con contraseña: 12345678');
    }

    public function update(Request $request, Barbero $empleado)
    {
        if ($empleado->barberia_id !== Auth::user()->barberia->id) {
            abort(403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'color_calendario' => 'required|string|max:7',
            'activo' => 'boolean',
        ]);

        $empleado->update([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'color_calendario' => $request->color_calendario,
            'activo' => $request->has('activo'),
        ]);

        // Actualizar el nombre del usuario vinculado si existe
        $user = \App\Models\User::where('barbero_id', $empleado->id)->first();
        if ($user) {
            $user->update([
                'name' => $request->nombre,
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
