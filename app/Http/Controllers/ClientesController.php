<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function index()
    {
        // En una base de datos más grande, aquí filtraríamos por barberia_id
        // pero por ahora el modelo Cliente es global para el bot.
        // Podríamos filtrar solo clientes que han tenido citas con esta barberia.
        $barberiaId = auth()->user()->barberia->id;
        
        $clientes = Cliente::whereHas('citas', function($query) use ($barberiaId) {
                $query->where('barberia_id', $barberiaId);
            })
            ->with(['citas' => function($query) use ($barberiaId) {
                $query->where('barberia_id', $barberiaId)->latest('fecha');
            }])
            ->withCount(['citas as visitas_completadas' => function($query) use ($barberiaId) {
                $query->where('barberia_id', $barberiaId)->where('estado', 'completada');
            }])
            ->get();

        return view('admin.clientes', compact('clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20|unique:clientes,telefono',
            'notas' => 'nullable|string',
        ]);

        Cliente::create([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'notas' => $request->notas,
        ]);

        return back()->with('success', 'Cliente agregado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20|unique:clientes,telefono,' . $cliente->id,
            'notas' => 'nullable|string',
            'bloqueado' => 'boolean',
        ]);

        $cliente->update([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'notas' => $request->notas,
            'bloqueado' => $request->has('bloqueado'),
        ]);

        return back()->with('success', 'Cliente actualizado correctamente.');
    }
}
