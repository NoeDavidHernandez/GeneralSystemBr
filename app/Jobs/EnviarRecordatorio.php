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
use Illuminate\Support\Facades\Log;

class EnviarRecordatorio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Si falla, reintentar hasta 3 veces con 5 min entre intentos
    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(
        private int    $citaId,
        private string $tipo   // '24h_antes' | '1h_antes'
    ) {}

    public function handle(WhatsAppApiService $api): void
    {
        $cita = Cita::with(['cliente', 'servicio', 'barberia'])->find($this->citaId);

        // Validaciones antes de enviar
        if (! $cita) return;
        if ($cita->estado === 'cancelada') return;
        if (RecordatorioLog::yaEnviado($this->citaId, $this->tipo)) return;

        // Crear el log antes de enviar (evita duplicados en reintentos)
        $log = RecordatorioLog::create([
            'cita_id' => $this->citaId,
            'tipo'    => $this->tipo,
            'status'  => RecordatorioLog::STATUS_PENDIENTE,
        ]);

        try {
            $mensaje  = $this->construirMensaje($cita);
            $enviado  = $api->enviarTexto(
                $cita->cliente->telefono,
                $cita->barberia,
                $mensaje
            );

            if ($enviado) {
                $log->marcarEnviado();
            } else {
                $log->marcarFallido('API retornó false');
                $this->fail('WhatsApp API error');
            }

        } catch (\Throwable $e) {
            $log->marcarFallido($e->getMessage());
            Log::error("EnviarRecordatorio falló", [
                'cita_id' => $this->citaId,
                'tipo'    => $this->tipo,
                'error'   => $e->getMessage(),
            ]);
            throw $e; // que Laravel reintente el job
        }
    }

    private function construirMensaje(Cita $cita): string
    {
        $fecha  = Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $hora   = Carbon::parse($cita->hora_inicio)->format('g:i A');
        $nombre = $cita->cliente->nombre;

        return match ($this->tipo) {

            '24h_antes' =>
                "💈 *Recordatorio de cita — {$cita->barberia->nombre}*\n\n"
                . "Hola *{$nombre}* 👋\n\n"
                . "Te recordamos que mañana tienes cita:\n\n"
                . "✂️ *{$cita->servicio->nombre}*\n"
                . "📅 {$fecha}\n"
                . "🕐 {$hora}\n"
                . "📍 {$cita->barberia->direccion}\n\n"
                . "Por favor confirma tu asistencia respondiendo *SÍ* ✅\n"
                . "Si no puedes asistir responde *NO* ❌\n\n"
                . "_Recuerda que puedes cancelar con mínimo 2 horas de anticipación._",

            '1h_antes' =>
                "⏰ *¡Tu cita es en 1 hora!*\n\n"
                . "Hola *{$nombre}*, te esperamos a las *{$hora}* en {$cita->barberia->nombre} 💈\n\n"
                . "📍 {$cita->barberia->direccion}\n\n"
                . "Si no puedes venir avísanos respondiendo *NO PUEDO*.\n\n"
                . "_Recuerda que tenemos 15 minutos de tolerancia._",

            default => '',
        };
    }
}