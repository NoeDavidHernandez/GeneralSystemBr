<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConvEstado;
use App\Models\Barberia;
use App\Models\Cliente;
use App\Services\WhatsAppApiService;

class ReiniciarAsesoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:reiniciar-asesores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia las sesiones en modo asesor que han excedido su tiempo limite de inactividad (30 min)';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppApiService $whatsappApi)
    {
        // Buscar estados que esten en modo asesor y hayan expirado
        $estadosExpirados = ConvEstado::where('modo_asesor', true)
            ->where('expires_at', '<', now())
            ->get();

        if ($estadosExpirados->isEmpty()) {
            return;
        }

        foreach ($estadosExpirados as $estado) {
            // Obtener el cliente para saber a que barberia pertenece
            $cliente = Cliente::where('telefono', $estado->telefono)->first();

            if ($cliente && $cliente->barberia) {
                // Notificar al cliente que su sesion finalizo por inactividad
                $mensaje = "Tu sesión con el asesor ha finalizado por inactividad. ¿En qué más te puedo ayudar?\n\nSi necesitas algo más, solo escríbeme.";
                $whatsappApi->enviarTexto($estado->telefono, $cliente->barberia, $mensaje);
            }

            // Desactivar el modo asesor y reiniciar la sesion
            $estado->desactivarModoAsesor();
        }

        $this->info("Se han reiniciado {$estadosExpirados->count()} sesiones de asesores inactivos.");
    }
}
