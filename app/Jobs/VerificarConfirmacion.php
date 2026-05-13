<?php

namespace App\Jobs;

use App\Models\Cita;
use App\Models\RecordatorioLog;
use App\Services\WhatsAppApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerificarConfirmacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private int $citaId) {}

    public function handle(WhatsAppApiService $api): void
    {
        $cita = Cita::with(['cliente', 'servicio', 'barberia'])->find($this->citaId);

        if (! $cita) return;

        // Si ya está confirmada o cancelada, no hacer nada
        if (in_array($cita->estado, ['confirmada', 'cancelada', 'completada'])) return;

        // La cita sigue en 'pendiente' → el cliente no confirmó → cancelar
        $fecha = Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $hora  = Carbon::parse($cita->hora_inicio)->format('g:i A');

        // Cancelar la cita
        $cita->cancelar('No confirmó asistencia');

        // Avisar al cliente
        $api->enviarTexto(
            $cita->cliente->telefono,
            $cita->barberia,
            "⚠️ Hola *{$cita->cliente->nombre}*, tu cita del *{$fecha}* a las *{$hora}* "
            . "fue cancelada porque no recibimos tu confirmación.\n\n"
            . "Si deseas agendar de nuevo escríbenos 😊"
        );

        // Registrar en el log
        RecordatorioLog::create([
            'cita_id'    => $this->citaId,
            'tipo'       => 'cancelacion',
            'status'     => RecordatorioLog::STATUS_ENVIADO,
            'enviado_at' => now(),
        ]);

        // Avisar al admin de la barbería
        if ($cita->barberia->whatsapp_admin_numero) {
            $api->enviarTexto(
                $cita->barberia->whatsapp_admin_numero,
                $cita->barberia,
                "🗑️ *Cita cancelada por no confirmación*\n\n"
                . "👤 {$cita->cliente->nombre} — {$cita->cliente->telefono}\n"
                . "✂️ {$cita->servicio->nombre}\n"
                . "📅 {$fecha} a las {$hora}"
            );
        }
    }
}