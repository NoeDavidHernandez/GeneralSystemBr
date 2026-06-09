<?php

namespace App\Http\Controllers;

use App\Models\Barberia;
use Illuminate\Http\Request;

class SuperAdminNegociosController extends Controller
{
    public function show(Barberia $barberia)
    {
        $barberiasActivas = Barberia::where('id', '!=', $barberia->id)->orderBy('nombre')->get();
        return view('superadmin.negocios.show', compact('barberia', 'barberiasActivas'));
    }

    public function update(Request $request, Barberia $barberia)
    {
        $request->validate([
            'referido_por' => 'nullable|exists:barberias,id',
            'fecha_proximo_pago' => 'nullable|date',
        ]);

        $barberia->update([
            'referido_por' => $request->referido_por,
            'fecha_proximo_pago' => $request->fecha_proximo_pago,
        ]);

        return back()->with('success', 'Datos del negocio actualizados correctamente.');
    }
}
