<?php

namespace App\Jobs;

use App\Models\Cita;
use App\Models\RecordatorioLog;
use App\Services\WhatsAppApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarMensajeGracias implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private int $citaId) {}

    public function handle(WhatsAppApiService $api): void
    {
        $cita = Cita::with(['cliente', 'servicio', 'barberia'])->find($this->citaId);

        if (! $cita) return;
        if (RecordatorioLog::yaEnviado($this->citaId, RecordatorioLog::TIPO_GRACIAS)) return;

        $log = RecordatorioLog::create([
            'cita_id' => $this->citaId,
            'tipo'    => RecordatorioLog::TIPO_GRACIAS,
            'status'  => RecordatorioLog::STATUS_PENDIENTE,
        ]);

        $esFrecuente = $cita->cliente->esFrecuente();

        $mensaje = "✨ *¡Gracias por tu preferencia, {$cita->cliente->nombre}!*\n\n"
                 . "Esperamos que hayas disfrutado tu servicio de *{$cita->servicio->nombre}* 💈\n\n"
                 . ($esFrecuente
                     ? "🌟 ¡Eres uno de nuestros clientes frecuentes! Te esperamos pronto.\n\n"
                     : "")
                 . "Te esperamos de nuevo en *{$cita->barberia->nombre}* 🙌\n\n"
                 . "_Para agendar tu próxima cita escríbenos cuando quieras._";

        $enviado = $api->enviarTexto($cita->cliente->telefono, $cita->barberia, $mensaje);

        $enviado ? $log->marcarEnviado() : $log->marcarFallido('API retornó false');
    }
}