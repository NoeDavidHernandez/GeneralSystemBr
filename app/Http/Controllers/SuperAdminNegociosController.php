<?php

namespace App\Http\Controllers;

use App\Models\Barberia;
use App\Models\PagoSaaS;
use Illuminate\Http\Request;

class SuperAdminNegociosController extends Controller
{
    public function show(Barberia $barberia)
    {
        $barberiasActivas = Barberia::where('id', '!=', $barberia->id)->orderBy('nombre')->get();
        $pagos = PagoSaaS::where('barberia_id', $barberia->id)->orderBy('fecha_pago', 'desc')->get();
        return view('superadmin.negocios.show', compact('barberia', 'barberiasActivas', 'pagos'));
    }

    public function update(Request $request, Barberia $barberia)
    {
        $request->validate([
            'referido_por' => 'nullable|exists:barberias,id',
            'fecha_proximo_pago' => 'nullable|date',
            'dias_recompensa' => 'nullable|integer|min:0|max:30',
        ]);

        $oldReferidoPor = $barberia->referido_por;
        $mensaje = 'Datos del negocio actualizados correctamente.';

        $barberia->update([
            'referido_por' => $request->referido_por,
            'fecha_proximo_pago' => $request->fecha_proximo_pago,
        ]);

        // Si se acaba de asignar un nuevo referenciador y se especificaron días de recompensa
        if ($request->referido_por && $oldReferidoPor != $request->referido_por && $request->dias_recompensa > 0) {
            $referenciador = Barberia::find($request->referido_por);
            if ($referenciador && $referenciador->fecha_proximo_pago) {
                $referenciador->fecha_proximo_pago = $referenciador->fecha_proximo_pago->addDays($request->dias_recompensa);
                $referenciador->recompensas_acumuladas += $request->dias_recompensa;
                $referenciador->save();
                
                $mensaje .= " Además, se han otorgado {$request->dias_recompensa} días gratis al negocio referenciador ({$referenciador->nombre}).";
            }
        }

        return back()->with('success', $mensaje);
    }

    public function storePago(Request $request, Barberia $barberia)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date',
            'metodo' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
        ]);

        PagoSaaS::create([
            'barberia_id' => $barberia->id,
            'monto' => $request->monto,
            'fecha_pago' => $request->fecha_pago,
            'metodo' => $request->metodo,
            'notas' => $request->notas,
        ]);

        if ($barberia->fecha_proximo_pago) {
            $barberia->fecha_proximo_pago = $barberia->fecha_proximo_pago->addMonth();
        } else {
            $barberia->fecha_proximo_pago = now()->addMonth();
        }
        $barberia->save();

        return back()->with('success', 'Pago registrado correctamente. Se adelantó la fecha de próximo pago 1 mes.');
    }
}
