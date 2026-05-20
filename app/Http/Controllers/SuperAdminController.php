<?php

namespace App\Http\Controllers;

use App\Models\Barberia;
use App\Models\User;
use App\Models\Cita;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function index()
    {
        // Estadísticas globales
        $totalBarberias = Barberia::count();
        $barberiasActivas = Barberia::where('activo', true)->count();
        
        $totalUsuarios = User::where('is_superadmin', false)->count();
        
        // Volumen de citas hoy en toda la plataforma
        $citasHoy = Cita::whereDate('fecha', now()->toDateString())->count();
        
        // Ingresos globales (suma de precio_cobrado o precio de servicio)
        // Nota: esto puede ser pesado en grandes volúmenes, idealmente se usaría caché o una tabla resumen
        $ingresosGlobales = Cita::where('estado', 'completada')
            ->get()
            ->sum(function ($cita) {
                return $cita->precio_cobrado ?? optional($cita->servicio)->precio ?? 0;
            });

        // Lista de todas las barberías
        $barberias = Barberia::orderBy('created_at', 'desc')->get();

        return view('superadmin.dashboard', compact(
            'totalBarberias', 
            'barberiasActivas', 
            'totalUsuarios', 
            'citasHoy', 
            'ingresosGlobales',
            'barberias'
        ));
    }

    public function toggleStatus(Request $request, $id)
    {
        $barberia = Barberia::findOrFail($id);
        
        // Alternar el estado
        $barberia->activo = !$barberia->activo;
        $barberia->save();

        $estado = $barberia->activo ? 'activada' : 'suspendida';
        
        return redirect()->route('superadmin.dashboard')->with('success', "La barbería '{$barberia->nombre}' ha sido {$estado}.");
    }

    public function datos()
    {
        $barberias = Barberia::all();
        $labels = [];
        $ingresos = [];
        $totalCitas = [];
        $citasCompletadas = [];
        $citasCanceladas = [];

        foreach ($barberias as $barberia) {
            $labels[] = $barberia->nombre;

            // Ingresos de esta barbería
            $ingresosBarberia = Cita::where('barberia_id', $barberia->id)
                ->where('estado', 'completada')
                ->get()
                ->sum(function ($cita) {
                    return $cita->precio_cobrado ?? optional($cita->servicio)->precio ?? 0;
                });
            $ingresos[] = $ingresosBarberia;

            // Total de citas
            $totalCitas[] = Cita::where('barberia_id', $barberia->id)->count();

            // Citas por estado
            $citasCompletadas[] = Cita::where('barberia_id', $barberia->id)->where('estado', 'completada')->count();
            $citasCanceladas[] = Cita::where('barberia_id', $barberia->id)->where('estado', 'cancelada')->count();
        }

        return response()->json([
            'labels' => $labels,
            'ingresos' => $ingresos,
            'totalCitas' => $totalCitas,
            'estados' => [
                'completadas' => $citasCompletadas,
                'canceladas' => $citasCanceladas
            ]
        ]);
    }
}
