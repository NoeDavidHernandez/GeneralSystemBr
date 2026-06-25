<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $barberia = $user->barberia;
        $barbero = $user->barbero;
        return view('admin.configuracion', compact('barberia', 'barbero'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        if ($user->rol === 'empleado') {
            $request->validate([
                'apertura' => 'nullable|string',
                'cierre' => 'nullable|string',
                'comida_inicio' => 'nullable|string',
                'comida_fin' => 'nullable|string',
                'dias_cerrado' => 'nullable|array',
            ]);

            $barbero = $user->barbero;
            $horarioActual = $barbero->horario_propio_json ?? [];
            $horarioNuevo = [
                'apertura' => $request->apertura ?? ($horarioActual['apertura'] ?? '09:00'),
                'cierre' => $request->cierre ?? ($horarioActual['cierre'] ?? '20:00'),
                'comida_inicio' => $request->comida_inicio ?? ($horarioActual['comida_inicio'] ?? '14:00'),
                'comida_fin' => $request->comida_fin ?? ($horarioActual['comida_fin'] ?? '15:00'),
                'dias_cerrado' => $request->dias_cerrado ? array_map('intval', $request->dias_cerrado) : [],
            ];

            $barbero->update(['horario_propio_json' => $horarioNuevo]);
            return back()->with('success', 'Tus horarios se han actualizado correctamente.');
        }

        $barberia = $user->barberia;

        $request->validate([
            'nombre' => 'required|string|max:100',
            'rif' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:200',
            'descripcion' => 'nullable|string',
            'whatsapp_admin_numero' => 'nullable|string|max:50',
            'whatsapp_phone_id' => 'nullable|string|max:100',
            'whatsapp_token' => 'nullable|string',
            'apertura' => 'nullable|string',
            'cierre' => 'nullable|string',
            'comida_inicio' => 'nullable|string',
            'comida_fin' => 'nullable|string',
            'dias_cerrado' => 'nullable|array',
            'password' => 'nullable|string|min:8|confirmed',
            'logo_base64' => 'nullable|string',
            'quitar_logo' => 'nullable|string|in:0,1',
        ]);

        $horarioActual = $barberia->horario_json ?? [];
        $horarioNuevo = [
            'apertura' => $request->apertura ?? ($horarioActual['apertura'] ?? '09:00'),
            'cierre' => $request->cierre ?? ($horarioActual['cierre'] ?? '20:00'),
            'comida_inicio' => $request->comida_inicio ?? ($horarioActual['comida_inicio'] ?? '14:00'),
            'comida_fin' => $request->comida_fin ?? ($horarioActual['comida_fin'] ?? '15:00'),
            'dias_cerrado' => $request->dias_cerrado ? array_map('intval', $request->dias_cerrado) : [],
        ];

        // Mantener el logo actual si existe
        if (isset($horarioActual['logo'])) {
            $horarioNuevo['logo'] = $horarioActual['logo'];
        }

        if ($request->quitar_logo == '1') {
            unset($horarioNuevo['logo']);
        } elseif ($request->filled('logo_base64')) {
            $horarioNuevo['logo'] = $request->logo_base64;
        }

        $barberia->update([
            'nombre' => $request->nombre,
            'rif' => $request->rif,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'descripcion' => $request->descripcion,
            'whatsapp_admin_numero' => $request->whatsapp_admin_numero ? trim($request->whatsapp_admin_numero) : null,
            'whatsapp_phone_id' => $request->whatsapp_phone_id ? trim($request->whatsapp_phone_id) : null,
            'whatsapp_token' => $request->whatsapp_token ? trim($request->whatsapp_token) : null,
            'horario_json' => $horarioNuevo,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => bcrypt($request->password)
            ]);
        }

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
