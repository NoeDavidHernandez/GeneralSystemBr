<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensajeGracias;
use App\Models\Cita;
use App\Models\ConvEstado;
use Illuminate\Console\Command;

class ProcesarCitasCommand extends Command
{
    protected $signature   = 'citas:procesar';
    protected $description = 'Marca citas completadas y limpia sesiones expiradas';

    public function handle(): void
    {
        $this->marcarCitasCompletadas();
        $this->limpiarSesionesExpiradas();
    }

    // ─── Marcar como completadas las citas que ya pasaron ─────────────────
    // Corre cada 30 minutos via scheduler
    private function marcarCitasCompletadas(): void
    {
        // Citas confirmadas cuya hora_fin ya pasó
        $citas = Cita::where('estado', 'confirmada')
            ->whereRaw("CONCAT(fecha, ' ', hora_fin) < NOW()")
            ->get();

        foreach ($citas as $cita) {
            $cita->update(['estado' => 'completada']);
            $cita->cliente->increment('total_visitas');

            // Enviar mensaje de gracias 30 minutos después de terminar la cita
            EnviarMensajeGracias::dispatch($cita->id)->delay(now()->addMinutes(30));

            $this->info("Cita #{$cita->id} marcada como completada");
        }

        // Citas pendientes (no confirmadas) que ya pasaron → no_asistio
        Cita::where('estado', 'pendiente')
            ->whereRaw("CONCAT(fecha, ' ', hora_fin) < NOW()")
            ->update(['estado' => 'no_asistio']);
    }

    private function limpiarSesionesExpiradas(): void
    {
        $eliminadas = ConvEstado::limpiarExpiradas();
        if ($eliminadas > 0) {
            $this->info("Sesiones expiradas eliminadas: {$eliminadas}");
        }
    }
}