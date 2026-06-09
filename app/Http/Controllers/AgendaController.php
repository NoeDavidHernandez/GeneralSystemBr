<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Barbero;
use App\Models\Servicio;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendaController extends Controller
{
    public function index()
    {
        $barberia = Auth::user()->barberia;
        // Resources for FullCalendar (Especialistas)
        $barberos = $barberia->barberos()->where('activo', true)->get(['id', 'nombre', 'color_calendario']);
        
        // Servicios for the modal
        $servicios = $barberia->servicios()->get();
        
        return view('admin.agenda', compact('barberos', 'servicios'));
    }

    public function eventos(Request $request): JsonResponse
    {
        $barberia = Auth::user()->barberia;
        
        $start = $request->query('start');
        $end = $request->query('end');

        $query = Cita::with(['cliente', 'servicios', 'barbero'])
            ->where('barberia_id', $barberia->id)
            ->whereNotIn('estado', ['cancelada']); // Exclude cancelled

        if ($start) {
            $query->whereDate('fecha', '>=', Carbon::parse($start));
        }
        if ($end) {
            $query->whereDate('fecha', '<=', Carbon::parse($end));
        }

        $citas = $query->get();

        $eventos = $citas->map(function ($cita) {
            $color = $cita->barbero ? ($cita->barbero->color_calendario ?? '#3b82f6') : '#3b82f6';
            
            // Adjust colors based on status
            if ($cita->estado === 'completada') $color = '#10b981'; // green
            if ($cita->estado === 'no_asistio') $color = '#ef4444'; // red

            return [
                'id' => $cita->id,
                'resourceId' => $cita->barbero_id,
                'title' => ($cita->cliente->nombre ?? 'Cliente') . ' - ' . $cita->nombresServicios(),
                'start' => $cita->fecha->format('Y-m-d') . 'T' . $cita->hora_inicio,
                'end' => $cita->fecha->format('Y-m-d') . 'T' . ($cita->hora_fin ?? Carbon::parse($cita->hora_inicio)->addMinutes($cita->duracionTotal())->format('H:i:s')),
                'color' => $color,
                'extendedProps' => [
                    'cliente' => $cita->cliente->nombre ?? 'Cliente',
                    'telefono' => $cita->cliente->telefono ?? '',
                    'servicios' => $cita->nombresServicios(),
                    'estado' => $cita->estado,
                    'precio' => $cita->precioTotalTexto(),
                    'notas' => $cita->notas
                ]
            ];
        });

        return response()->json($eventos);
    }

    public function guardarCita(Request $request)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'cliente_telefono' => 'nullable|string|max:20',
            'barbero_id' => 'required|exists:barberos,id',
            'servicios' => 'required|array',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
        ]);

        $barberia = Auth::user()->barberia;

        // Crear o buscar cliente local
        $cliente = Cliente::firstOrCreate(
            ['telefono' => $request->cliente_telefono ?? '0000000000'],
            [
                'barberia_id' => $barberia->id,
                'nombre' => $request->cliente_nombre,
                'puntos' => 0
            ]
        );

        // Calcular duracion y total
        $servicios = Servicio::whereIn('id', $request->servicios)->get();
        $duracion = $servicios->sum('duracion_min');
        
        $hora_fin = Carbon::parse($request->hora_inicio)->addMinutes($duracion)->format('H:i:s');

        $cita = Cita::create([
            'barberia_id' => $barberia->id,
            'cliente_id' => $cliente->id,
            'barbero_id' => $request->barbero_id,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $hora_fin,
            'estado' => 'confirmada', // Como es creada por el admin, asume confirmada
        ]);

        $cita->servicios()->sync($request->servicios);

        return response()->json(['success' => true, 'cita' => $cita]);
    }
    
    public function actualizarCita(Request $request, Cita $cita)
    {
        // Al arrastrar el evento en FullCalendar
        if ($cita->barberia_id !== Auth::user()->barberia->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s',
            'barbero_id' => 'required|exists:barberos,id'
        ]);

        $cita->update([
            'fecha' => Carbon::parse($request->fecha)->format('Y-m-d'),
            'hora_inicio' => Carbon::parse($request->hora_inicio)->format('H:i:s'),
            'hora_fin' => Carbon::parse($request->hora_fin)->format('H:i:s'),
            'barbero_id' => $request->barbero_id
        ]);

        return response()->json(['success' => true]);
    }
}
