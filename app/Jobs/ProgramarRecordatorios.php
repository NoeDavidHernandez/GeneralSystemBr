<?php

namespace App\Jobs;

use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProgramarRecordatorios implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Cita $cita) {}

    public function handle(): void
    {
        $inicioCita = Carbon::parse("{$this->cita->fecha} {$this->cita->hora_inicio}")
            ->setTimezone('America/Mexico_City');

        // ── Recordatorio 24 horas antes ───────────────────────────────────
        $momento24h = $inicioCita->copy()->subHours(24);
        if ($momento24h->isFuture()) {
            EnviarRecordatorio::dispatch($this->cita->id, '24h_antes')
                ->delay($momento24h);
        }

        // ── Recordatorio 30 minutos antes ─────────────────────────────────────
        $momento30m = $inicioCita->copy()->subMinutes(30);
        if ($momento30m->isFuture()) {
            EnviarRecordatorio::dispatch($this->cita->id, '30m_antes')
                ->delay($momento30m);
        }

        // ── Verificar confirmación 2 horas antes ──────────────────────────
        // Si el cliente no confirmó, cancela la cita automáticamente
        $momentoVerificar = $inicioCita->copy()->subHours(2);
        if ($momentoVerificar->isFuture()) {
            VerificarConfirmacion::dispatch($this->cita->id)
                ->delay($momentoVerificar);
        }
    }
}