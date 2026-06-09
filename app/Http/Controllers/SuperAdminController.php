<?php

namespace App\Http\Controllers;

use App\Models\Barberia;
use App\Models\User;
use App\Models\Cita;
use App\Models\PagoSaaS;
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
            ->with('servicios')
            ->get()
            ->sum(function ($cita) {
                return $cita->precio_cobrado ?? $cita->servicios->sum('precio');
            });

        // Lista de todas las barberías
        $barberias = Barberia::with('referenciador')->orderBy('created_at', 'desc')->get();

        // Próximos Pagos (SaaS)
        $proximosPagos = Barberia::whereNotNull('fecha_proximo_pago')
            ->orderBy('fecha_proximo_pago', 'asc')
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalBarberias', 
            'barberiasActivas', 
            'totalUsuarios', 
            'citasHoy', 
            'ingresosGlobales',
            'barberias',
            'proximosPagos'
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

    public function datos(Request $request)
    {
        $rango = $request->input('rango', 6); // por defecto 6 meses
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
                ->with('servicios')
                ->get()
                ->sum(function ($cita) {
                    return $cita->precio_cobrado ?? $cita->servicios->sum('precio');
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
            ],
            'crecimiento' => $this->getCrecimientoSaaS($rango),
            'ingresosNLogic' => $this->getIngresosNLogic($rango)
        ]);
    }

    private function getCrecimientoSaaS($rango)
    {
        $meses = [];
        $altas = [];

        // Aseguramos que empiece en 0 (mes actual) y retroceda $rango - 1
        for ($i = $rango - 1; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $meses[] = $mes->format('M Y');
            
            $count = Barberia::whereYear('created_at', $mes->year)
                ->whereMonth('created_at', $mes->month)
                ->count();
                
            $altas[] = $count;
        }

        return [
            'labels' => $meses,
            'data' => $altas
        ];
    }

    private function getIngresosNLogic($rango)
    {
        $meses = [];
        $ingresos = [];

        for ($i = $rango - 1; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $meses[] = $mes->format('M Y');
            
            $suma = PagoSaaS::whereYear('fecha_pago', $mes->year)
                ->whereMonth('fecha_pago', $mes->month)
                ->sum('monto');
                
            $ingresos[] = $suma;
        }

        return [
            'labels' => $meses,
            'data' => $ingresos
        ];
    }
}
