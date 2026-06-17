<?php

namespace App\Http\Controllers;

use App\Models\Barberia;
use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function __construct(private BotService $bot) {}

    // ─── Verificación del webhook (Meta lo llama una vez al configurar) ───
    // Meta manda GET con hub.challenge y espera que lo devuelvas tal cual
    public function verificar(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // ─── Webhook principal: recibe mensajes entrantes ─────────────────────
    public function webhook(Request $request): Response
    {
        // 1. Validar firma HMAC que Meta manda en el header
        if (! $this->firmaValida($request)) {
            Log::warning('WhatsApp webhook: firma inválida', [
                'ip' => $request->ip(),
            ]);
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        // Meta siempre espera 200 rápido, aunque fallemos internamente
        // Por eso procesamos en segundo plano con dispatch
        try {
            $this->procesarPayload($payload);
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        return response('OK', 200);
    }

    // ─── Privados ─────────────────────────────────────────────────────────

    private function firmaValida(Request $request): bool
    {
        // En local/dev, omitimos la validación de firma para facilitar pruebas.
        // En producción, cambiar a false para activar seguridad HMAC.
        if (config('app.env') !== 'production') {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $secret    = config('services.whatsapp.app_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function procesarPayload(array $payload): void
    {
        $entry = $payload['entry'][0] ?? null;
        if (! $entry) return;

        $change = $entry['changes'][0] ?? null;
        if (! $change) return;

        $value         = $change['value'] ?? [];
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
        $messages      = $value['messages'] ?? [];

        // DEBUG TEMPORAL
        Log::info('WhatsApp DEBUG', [
            'phone_number_id' => $phoneNumberId,
            'has_messages'    => ! empty($messages),
            'has_statuses'    => isset($value['statuses']),
        ]);

        if (empty($messages)) return; // status update, ignorar

        // Identificar a qué barbería pertenece este número de WhatsApp
        $barberia = \App\Models\Barberia::where('whatsapp_phone_id', $phoneNumberId)->first();

        if (! $barberia) {
            Log::warning('WhatsApp: phone_number_id sin barbería asociada', [
                'phone_number_id' => $phoneNumberId,
            ]);
            return;
        }

        if (! $barberia->activo) {
            Log::info("WhatsApp: Mensaje ignorado. La barbería {$barberia->nombre} está inactiva.");
            return;
        }

        foreach ($messages as $message) {
            $this->procesarMensaje($message, $barberia);
        }
    }

    private function procesarMensaje(array $message, Barberia $barberia): void
    {
        $tipo = $message['type'] ?? 'unknown';


        $texto = match ($tipo) {
            'text'        => trim($message['text']['body'] ?? ''),
            'interactive' => $this->extraerInteractive($message),
            default       => null,
        };

        if ($texto === null) return;

        $from = $message['from']; 
        $name = $message['contacts'][0]['profile']['name'] ?? null;

        $this->bot->manejar(
            barberia: $barberia,
            telefono: $from,
            texto: $texto,
            nombrePerfil: $name,
        );
    }

    // Extrae la selección de un mensaje interactivo (list reply o button reply)
    private function extraerInteractive(array $message): ?string
    {
        $interactive = $message['interactive'] ?? [];

        return match ($interactive['type'] ?? '') {
            'list_reply'   => $interactive['list_reply']['id']    ?? null,
            'button_reply' => $interactive['button_reply']['id']  ?? null,
            default        => null,
        };
    }
}