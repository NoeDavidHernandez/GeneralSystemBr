<?php

namespace App\Services;

use App\Models\Barberia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    private const BASE_URL = 'https://graph.facebook.com/v25.0';

    // WhatsApp internamente agrega un '1' a los números de México móvil (521XXXXXXXXXX)
    // pero la API de Meta espera el formato E.164 estándar (52XXXXXXXXXX).
    // Este método normaliza el número antes de enviarlo.
    private function normalizarTelefono(string $telefono): string
    {
        // Si es número mexicano con prefijo 521 + 10 dígitos (13 dígitos total) → quitar el 1
        if (preg_match('/^521(\d{10})$/', $telefono, $m)) {
            return '52' . $m[1];
        }
        return $telefono;
    }

    // ─── Mensajes de texto simples ────────────────────────────────────────

    public function enviarTexto(string $telefono, Barberia $barberia, string $mensaje): bool
    {
        return $this->enviar($barberia, [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $telefono,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $mensaje,
            ],
        ]);
    }

    // ─── Mensajes con botones (máx 3 botones) ────────────────────────────
    // Útil para preguntas de sí/no o confirmaciones

    public function enviarBotones(
        string   $telefono,
        Barberia $barberia,
        string   $cuerpo,
        array    $botones  // [['id' => 'si', 'title' => 'Sí, confirmar'], ...]
    ): bool {
        $botonesFormato = array_map(fn($b) => [
            'type'  => 'reply',
            'reply' => [
                'id'    => $b['id'],
                'title' => substr($b['title'], 0, 20), // Meta limita a 20 chars
            ],
        ], array_slice($botones, 0, 3)); // Meta permite máx 3 botones

        return $this->enviar($barberia, [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $telefono,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => $cuerpo],
                'action' => ['buttons' => $botonesFormato],
            ],
        ]);
    }

    // ─── Mensajes con lista (máx 10 opciones) ────────────────────────────
    // Perfecto para el menú de servicios y selección de horarios

    public function enviarLista(
        string   $telefono,
        Barberia $barberia,
        string   $cuerpo,
        string   $botonTexto,  // texto del botón que abre la lista ej: "Ver servicios"
        array    $secciones    // [['title' => 'Cortes', 'rows' => [['id'=>'1','title'=>'Corte caballero','description'=>'$150']]]]
    ): bool {
        return $this->enviar($barberia, [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $telefono,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'list',
                'body' => ['text' => $cuerpo],
                'action' => [
                    'button'   => substr($botonTexto, 0, 20),
                    'sections' => $secciones,
                ],
            ],
        ]);
    }

    // ─── Marcar mensaje como leído (buena práctica con Meta) ─────────────

    public function marcarLeido(string $messageId, Barberia $barberia): bool
    {
        return $this->enviar($barberia, [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ]);
    }

    // ─── Método base que hace el HTTP request a Meta ──────────────────────

    private function enviar(Barberia $barberia, array $payload): bool
    {
        // Normalizar número de destino (México: 521XXXXXXXXXX → 52XXXXXXXXXX)
        if (isset($payload['to'])) {
            $payload['to'] = $this->normalizarTelefono($payload['to']);
        }

        try {
            $response = Http::withToken($barberia->whatsapp_token)
                ->timeout(10)
                ->post(
                    self::BASE_URL . "/{$barberia->whatsapp_phone_id}/messages",
                    $payload
                );

            if ($response->failed()) {
                Log::error('WhatsApp API error', [
                    'barberia_id' => $barberia->id,
                    'status'      => $response->status(),
                    'body'        => $response->json(),
                    'payload'     => $payload,
                ]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('WhatsApp API excepción', [
                'barberia_id' => $barberia->id,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }
}