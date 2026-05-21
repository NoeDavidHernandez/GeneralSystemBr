<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cita;
use App\Models\RecordatorioLog;
use App\Services\WhatsAppApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EnviarRecordatorios extends Command
{
    protected $signature = 'citas:enviar-recordatorios';
    protected $description = 'Envía recordatorios de citas 30 minutos antes';

    public function handle(WhatsAppApiService $api)
    {
        $ahora = Carbon::now();
        // Buscar citas que ocurran entre 30 y 45 minutos en el futuro
        $limiteInferior = $ahora->copy()->addMinutes(30);
        $limiteSuperior = $ahora->copy()->addMinutes(45);

        $citas = Cita::with(['cliente', 'barberia', 'servicio'])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha', $ahora->toDateString())
            ->get()
            ->filter(function ($cita) use ($limiteInferior, $limiteSuperior) {
                // Parsear hora_inicio asumiendo que es H:i:s
                $horaCita = Carbon::createFromFormat('H:i:s', $cita->hora_inicio);
                $limInfStr = $limiteInferior->format('H:i:s');
                $limSupStr = $limiteSuperior->format('H:i:s');
                return $horaCita->between($limInfStr, $limSupStr);
            });

        $this->info("Citas encontradas para recordatorio 30m: " . $citas->count());

        foreach ($citas as $cita) {
            $yaEnviado = RecordatorioLog::where('cita_id', $cita->id)
                ->where('tipo', '30m_antes')
                ->whereIn('status', ['enviado', 'pendiente'])
                ->exists();

            if ($yaEnviado) {
                continue;
            }

            try {
                $hora = Carbon::createFromFormat('H:i:s', $cita->hora_inicio)->format('g:i A');
                
                $mensaje = "⏰ *¡Recordatorio de Cita!*\n\n"
                         . "Hola {$cita->cliente->nombre},\n"
                         . "Te recordamos que tu cita para *{$cita->servicio->nombre}* es en aproximadamente 30 minutos (a las {$hora}).\n\n"
                         . "📍 {$cita->barberia->direccion}\n\n"
                         . "¡Te esperamos! 💈";

                $api->enviarTexto($cita->cliente->telefono, $cita->barberia, $mensaje);

                RecordatorioLog::create([
                    'cita_id' => $cita->id,
                    'tipo' => '30m_antes',
                    'status' => 'enviado',
                    'enviado_at' => now()
                ]);
                
                $this->info("Recordatorio enviado a {$cita->cliente->telefono}");

            } catch (\Exception $e) {
                Log::error("Error enviando recordatorio 30m para cita {$cita->id}: " . $e->getMessage());
                
                RecordatorioLog::create([
                    'cita_id' => $cita->id,
                    'tipo' => '30m_antes',
                    'status' => 'fallido',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
