<?php

namespace App\Http\Controllers;

use App\Models\MovimientoFinanciero;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanzasController extends Controller
{
    public function index()
    {
        $barberia = auth()->user()->barberia;
        $mesActual = Carbon::now()->startOfMonth();

        // Obtener resumen de ingresos por método de pago del mes actual
        $ingresosPorMetodo = MovimientoFinanciero::where('barberia_id', $barberia->id)
            ->where('tipo', 'ingreso')
            ->where('fecha', '>=', $mesActual)
            ->select('metodo_pago', DB::raw('SUM(monto) as total'), DB::raw('COUNT(*) as tx_count'))
            ->groupBy('metodo_pago')
            ->get();

        $totalIngresos = $ingresosPorMetodo->sum('total');
        $totalEgresos = MovimientoFinanciero::where('barberia_id', $barberia->id)
            ->where('tipo', 'egreso')
            ->where('fecha', '>=', $mesActual)
            ->sum('monto');

        $movimientos = MovimientoFinanciero::where('barberia_id', $barberia->id)
            ->orderBy('fecha', 'desc')
            ->limit(20)
            ->get();

        return view('admin.finanzas', compact('ingresosPorMetodo', 'totalIngresos', 'totalEgresos', 'movimientos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string|max:50',
            'persona' => 'nullable|string|max:255',
        ]);

        MovimientoFinanciero::create([
            'barberia_id' => auth()->user()->barberia->id,
            'tipo' => $request->tipo,
            'concepto' => $request->concepto,
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'persona' => $request->persona,
            'fecha' => now(),
        ]);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }
}
