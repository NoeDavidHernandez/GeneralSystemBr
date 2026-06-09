<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiciosController extends Controller
{
    public function index()
    {
        // Solo administradores deberian acceder, pero igual lo filtramos por barberia
        $barberiaId = Auth::user()->barberia_id;
        $servicios = Servicio::where('barberia_id', $barberiaId)->orderBy('categoria')->orderBy('nombre')->get();
        
        return view('admin.servicios', compact('servicios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'precio' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:1',
            'precio_variable' => 'nullable|boolean',
        ]);

        $barberiaId = Auth::user()->barberia_id;

        Servicio::create([
            'barberia_id' => $barberiaId,
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'precio' => $request->precio,
            'duracion_min' => $request->duracion_min,
            'precio_variable' => $request->has('precio_variable'),
            'activo' => true,
        ]);

        return back()->with('success', 'Servicio creado correctamente.');
    }

    public function update(Request $request, Servicio $servicio)
    {
        if ($servicio->barberia_id !== Auth::user()->barberia_id) {
            abort(403);
        }

        $request->validate([
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'precio' => 'required|numeric|min:0',
            'duracion_min' => 'required|integer|min:1',
            'precio_variable' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
        ]);

        $servicio->update([
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'precio' => $request->precio,
            'duracion_min' => $request->duracion_min,
            'precio_variable' => $request->has('precio_variable'),
            'activo' => $request->has('activo'),
        ]);

        return back()->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Servicio $servicio)
    {
        if ($servicio->barberia_id !== Auth::user()->barberia_id) {
            abort(403);
        }

        // En lugar de borrar, desactivamos por si hay citas históricas con este servicio
        $servicio->update(['activo' => false]);

        return back()->with('success', 'Servicio desactivado correctamente.');
    }
}
