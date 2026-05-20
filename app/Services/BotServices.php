<?php

namespace App\Services;

use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ConvEstado;
use App\Models\Servicio;
use App\Jobs\ProgramarRecordatorios;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BotService
{
    public function __construct(
        private WhatsAppApiService    $api,
        private DisponibilidadService $disponibilidad,
    ) {}

    // ─── Entrada principal ────────────────────────────────────────────────

    public function manejar(
        Barberia $barberia,
        string   $telefono,
        string   $texto,
        ?string  $nombrePerfil = null,
    ): void {
        $estado  = ConvEstado::obtenerOCrear($telefono);
        $cliente = Cliente::firstOrCreateByTelefono($telefono, $nombrePerfil ?? '');

        // ── Guardias ──────────────────────────────────────────────────────

        if ($cliente->bloqueado) return;

        if ($estado->modo_asesor) return;

        // El cliente quiere hablar con un asesor
        if ($this->quiereAsesor($texto)) {
            $estado->activarModoAsesor();
            $this->api->enviarTexto($telefono, $barberia,
                "Entendido {$cliente->nombre} 👋 En un momento un asesor te atenderá.\n\n"
                . "Si quieres volver al bot automático escribe *bot*."
            );
            $this->notificarAdmin($barberia, $cliente, $texto);
            return;
        }

        // Asesor devuelve el control al bot
        if (strtolower(trim($texto)) === 'bot') {
            $estado->desactivarModoAsesor();
            $this->api->enviarTexto($telefono, $barberia,
                "¡De vuelta con el asistente! 🤖 ¿En qué te puedo ayudar?"
            );
            return;
        }

        // Fuera de horario
        if (! $barberia->estaAbierta()) {
            $this->responderFueraDeHorario($telefono, $barberia, $estado);
            return;
        }

        // ── Detección de respuesta a recordatorio ─────────────────────────
        // Va ANTES del router de pasos para que funcione aunque no haya sesión activa.
        // CORRECCIÓN: ya no existe pasoRespuestaRecordatorio() que causaba loop infinito.
        $respuestaLower = strtolower(trim($texto));
        $esConfirmacion = in_array($respuestaLower, ['sí', 'si', 's', '1', 'confirmo', 'confirmar']);
        $esCancelacion  = in_array($respuestaLower, ['no', 'no puedo', 'cancelar', '0']);

        if ($esConfirmacion || $esCancelacion) {
            $citaPendiente = Cita::where('estado', 'pendiente')
                ->whereHas('cliente', fn($q) => $q->where('telefono', $telefono))
                ->where('barberia_id', $barberia->id)
                ->whereDate('fecha', '>=', today())
                ->orderBy('fecha')
                ->orderBy('hora_inicio')
                ->first();

            if ($citaPendiente) {
                $this->procesarRespuestaRecordatorio(
                    $telefono, $barberia, $citaPendiente, $esConfirmacion
                );
                return;
            }
        }

        // Sesión expirada: reiniciar suavemente
        if ($estado->estaExpirada()) {
            $estado->reiniciar();
        }

        // ── Router de pasos ───────────────────────────────────────────────
        match ($estado->paso) {
            ConvEstado::PASO_INICIO             => $this->pasoInicio($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_NOMBRE   => $this->pasoNombre($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_SERVICIO => $this->pasoServicio($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_FECHA    => $this->pasoFecha($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_HORA     => $this->pasoHora($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_CONFIRMANDO_CITA   => $this->pasoConfirmar($telefono, $barberia, $cliente, $estado, $texto),
            default                             => $this->pasoInicio($telefono, $barberia, $cliente, $estado, $texto),
        };
    }

    // ─── Pasos del flujo ──────────────────────────────────────────────────

    private function pasoInicio(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $saludo = $this->saludo();
        $nombre = $cliente->nombre !== 'Cliente' ? $cliente->nombre : '';

        $msg = "{$saludo} {$nombre}! 💈 Bienvenido a *{$barberia->nombre}*.\n\n"
             . "Para agendar tu cita necesito algunos datos.\n\n"
             . "¿Cuál es tu nombre?";

        if ($nombre) {
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_SERVICIO, ['nombre' => $nombre]);
            $this->enviarMenuServicios($telefono, $barberia, $cliente);
            return;
        }

        $estado->avanzar(ConvEstado::PASO_ESPERANDO_NOMBRE);
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function pasoNombre(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        if (strlen($texto) < 2 || strlen($texto) > 50) {
            $this->api->enviarTexto($telefono, $barberia,
                "Por favor escribe tu nombre completo 😊"
            );
            return;
        }

        $cliente->update(['nombre' => ucwords(strtolower($texto))]);
        $estado->avanzar(ConvEstado::PASO_ESPERANDO_SERVICIO, ['nombre' => $cliente->nombre]);
        $this->enviarMenuServicios($telefono, $barberia, $cliente);
    }

    private function pasoServicio(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $servicio = $this->buscarServicio($texto, $barberia);

        if (! $servicio) {
            $this->api->enviarTexto($telefono, $barberia,
                "No encontré ese servicio 🤔 Por favor elige un número del menú o escribe el nombre del servicio."
            );
            $this->enviarMenuServicios($telefono, $barberia, $cliente);
            return;
        }

        if ($servicio->precio_variable) {
            $this->api->enviarTexto($telefono, $barberia,
                "Perfecto, elegiste *{$servicio->nombre}* 💇\n\n"
                . "ℹ️ El precio depende del largo de tu cabello, te lo confirmamos cuando llegues.\n\n"
                . "¿Continuamos con la fecha? Escribe *sí*."
            );
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA, ['servicio_id' => $servicio->id]);
            return;
        }

        if ($servicio->precio_consultar) {
            $this->api->enviarTexto($telefono, $barberia,
                "Para *{$servicio->nombre}* el precio varía según tu caso 💬\n\n"
                . "Te recomendamos consultar directamente. Si quieres hablar con nosotros escribe *asesor*.\n\n"
                . "¿O prefieres elegir otro servicio? Escribe *menú*."
            );
            return;
        }

        $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA, ['servicio_id' => $servicio->id]);
        $this->api->enviarTexto($telefono, $barberia,
            "Genial, *{$servicio->nombre}* — {$servicio->precioTexto()} 💰\n\n"
            . "¿Para qué fecha te gustaría? Aquí los días disponibles:"
        );
        $this->enviarDiasDisponibles($telefono, $barberia);
    }

    private function pasoFecha(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $fecha = $this->parsearFecha($texto, $barberia);

        if (! $fecha) {
            $this->api->enviarTexto($telefono, $barberia,
                "No reconocí esa fecha 📅 Por favor elige un número de la lista o escribe la fecha (ej: *lunes 9 de junio*)."
            );
            $this->enviarDiasDisponibles($telefono, $barberia);
            return;
        }

        $estado->avanzar(ConvEstado::PASO_ESPERANDO_HORA, ['fecha' => $fecha]);

        $servicio = Servicio::find($estado->datos_temp['servicio_id']);
        $slots    = $this->disponibilidad->slotsDia($barberia, $fecha, $servicio->duracion_min);

        if ($slots->isEmpty()) {
            $this->api->enviarTexto($telefono, $barberia,
                "Lo sentimos, ese día ya no hay horarios disponibles 😔\n\n"
                . "Elige otra fecha:"
            );
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA);
            $this->enviarDiasDisponibles($telefono, $barberia);
            return;
        }

        $fechaFormato = Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $this->api->enviarTexto($telefono, $barberia,
            "📅 *{$fechaFormato}* — Horarios disponibles:"
        );
        $this->enviarSlotsHorarios($telefono, $barberia, $slots);
    }

    private function pasoHora(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $datos    = $estado->datos_temp;
        $fecha    = $datos['fecha'];
        $servicio = Servicio::find($datos['servicio_id']);

        $slots = $this->disponibilidad->slotsDia($barberia, $fecha, $servicio->duracion_min);
        $slot  = $this->buscarSlot($texto, $slots);

        if (! $slot) {
            $this->api->enviarTexto($telefono, $barberia,
                "No encontré ese horario ⏰ Elige un número de la lista."
            );
            $this->enviarSlotsHorarios($telefono, $barberia, $slots);
            return;
        }

        if (! $this->disponibilidad->slotSigueDisponible($barberia, $fecha, $slot['hora'], $servicio->duracion_min)) {
            $this->api->enviarTexto($telefono, $barberia,
                "Ese horario acaba de ocuparse 😅 Elige otro:"
            );
            $slots = $this->disponibilidad->slotsDia($barberia, $fecha, $servicio->duracion_min);
            $this->enviarSlotsHorarios($telefono, $barberia, $slots);
            return;
        }

        $estado->avanzar(ConvEstado::PASO_CONFIRMANDO_CITA, [
            'hora'       => $slot['hora'],
            'barbero_id' => $slot['barbero_id'],
        ]);

        $fechaFormato = Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $barberos     = Barbero::where('barberia_id', $barberia->id)->count();

        $resumen = "✅ *Resumen de tu cita*\n\n"
                 . "👤 *Cliente:* {$cliente->nombre}\n"
                 . "✂️ *Servicio:* {$servicio->nombre}\n"
                 . "📅 *Fecha:* {$fechaFormato}\n"
                 . "🕐 *Hora:* {$slot['hora_formato']}\n"
                 . "💰 *Precio:* {$servicio->precioTexto()}\n";

        if ($barberos > 1 && $slot['barbero_nombre']) {
            $resumen .= "💈 *Barbero:* {$slot['barbero_nombre']}\n";
        }

        $resumen .= "\n¿Confirmas tu cita? Escribe *sí* para agendar o *no* para cancelar.";
        $this->api->enviarTexto($telefono, $barberia, $resumen);
    }

    private function pasoConfirmar(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $respuesta = strtolower(trim($texto));

        if (! in_array($respuesta, ['sí', 'si', 's', 'yes', '1', 'confirmar', 'confirmo'])) {
            if (in_array($respuesta, ['no', 'n', 'cancelar', '0'])) {
                $estado->reiniciar();
                $this->api->enviarTexto($telefono, $barberia,
                    "Cita cancelada 👍 Si quieres agendar de nuevo escribe *hola*."
                );
                return;
            }

            $this->api->enviarTexto($telefono, $barberia,
                "Responde *sí* para confirmar o *no* para cancelar."
            );
            return;
        }

        $datos    = $estado->datos_temp;
        $servicio = Servicio::find($datos['servicio_id']);
        $horaFin  = Carbon::createFromFormat('H:i', $datos['hora'])
            ->addMinutes($servicio->duracion_min)
            ->format('H:i');

        $cita = Cita::create([
            'barberia_id' => $barberia->id,
            'cliente_id'  => $cliente->id,
            'servicio_id' => $servicio->id,
            'barbero_id'  => $datos['barbero_id'] ?? null,
            'fecha'       => $datos['fecha'],
            'hora_inicio' => $datos['hora'],
            'hora_fin'    => $horaFin,
            'estado'      => 'pendiente',
        ]);

        $cliente->increment('total_visitas');
        $estado->reiniciar();

        $fechaFormato = Carbon::parse($datos['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
        $this->api->enviarTexto($telefono, $barberia,
            "🎉 ¡Tu cita está agendada!\n\n"
            . "📅 *{$fechaFormato}* a las *{$this->formatoAmPm($datos['hora'])}*\n"
            . "✂️ {$servicio->nombre}\n\n"
            . "Te enviaremos un recordatorio 24 horas antes y 1 hora antes.\n\n"
            . "📍 {$barberia->direccion}\n\n"
            . "¡Te esperamos! 💈"
        );

        ProgramarRecordatorios::dispatch($cita);
    }

    // ─── Recordatorios ────────────────────────────────────────────────────

    private function procesarRespuestaRecordatorio(
        string   $telefono,
        Barberia $barberia,
        Cita     $cita,
        bool     $confirma
    ): void {
        $fecha = Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $hora  = Carbon::parse($cita->hora_inicio)->format('g:i A');

        if ($confirma) {
            $cita->confirmar();

            $this->api->enviarTexto($telefono, $barberia,
                "✅ ¡Cita confirmada!\n\n"
                . "Te esperamos el *{$fecha}* a las *{$hora}* 💈\n\n"
                . "📍 {$barberia->direccion}\n\n"
                . "_Recuerda llegar puntual. Tenemos 15 min de tolerancia._"
            );
        } else {
            // CORRECCIÓN: eliminado 'cancelado_por' — columna que no existe en la migración
            $cita->cancelar('Cliente canceló al responder recordatorio');

            $this->api->enviarTexto($telefono, $barberia,
                "Entendido, tu cita del *{$fecha}* a las *{$hora}* fue cancelada ✔️\n\n"
                . "Cuando quieras agendar de nuevo escríbenos 😊"
            );

            $this->notificarAdmin($barberia, $cita->cliente,
                "Canceló su cita del {$fecha} a las {$hora} tras el recordatorio."
            );
        }

        // Limpiar sesión para que no quede trabada
        ConvEstado::obtenerOCrear($telefono)->reiniciar();
    }

    // ─── Helpers de envío ─────────────────────────────────────────────────

    private function enviarMenuServicios(string $telefono, Barberia $barberia, Cliente $cliente): void
    {
        $servicios = Servicio::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->orderBy('categoria')
            ->orderBy('id')
            ->get();

        $msg             = "Hola *{$cliente->nombre}* 😊 Estos son nuestros servicios:\n\n";
        $categoriaActual = '';
        $contador        = 1;

        foreach ($servicios as $servicio) {
            if ($servicio->categoria !== $categoriaActual) {
                $msg .= "\n*{$servicio->categoria}*\n";
                $categoriaActual = $servicio->categoria;
            }
            $msg .= $servicio->lineaMenu($contador) . "\n";
            $contador++;
        }

        $msg .= "\nEscribe el *número* del servicio que deseas.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function enviarDiasDisponibles(string $telefono, Barberia $barberia): void
    {
        $dias = $this->disponibilidad->diasDisponibles($barberia, 7);

        if ($dias->isEmpty()) {
            $this->api->enviarTexto($telefono, $barberia,
                "No hay días disponibles en los próximos días 😔 Por favor contáctanos directamente."
            );
            return;
        }

        $msg = "📅 *Días disponibles:*\n\n";
        $dias->each(function ($fecha, $i) use (&$msg) {
            $carbon = Carbon::parse($fecha)->locale('es');
            $label  = match (true) {
                $carbon->isToday()    => 'Hoy',
                $carbon->isTomorrow() => 'Mañana',
                default               => ucfirst($carbon->isoFormat('dddd D [de] MMMM')),
            };
            $msg .= ($i + 1) . ". {$label}\n";
        });

        $msg .= "\nEscribe el *número* del día que prefieres.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function enviarSlotsHorarios(string $telefono, Barberia $barberia, $slots): void
    {
        $msg = "⏰ *Horarios disponibles:*\n\n";
        $slots->values()->each(function ($slot, $i) use (&$msg) {
            $msg .= ($i + 1) . ". {$slot['hora_formato']}\n";
        });
        $msg .= "\nEscribe el *número* del horario que prefieres.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function responderFueraDeHorario(
        string $telefono, Barberia $barberia, ConvEstado $estado
    ): void {
        if ($estado->paso === ConvEstado::PASO_FUERA_DE_HORARIO) return;

        $estado->avanzar(ConvEstado::PASO_FUERA_DE_HORARIO);

        $horario = $barberia->horarioParseado();
        $this->api->enviarTexto($telefono, $barberia,
            "¡Hola! 👋 Gracias por contactarnos.\n\n"
            . "En este momento estamos cerrados 🕐\n\n"
            . "Nuestro horario es:\n"
            . "Lunes a domingo de {$horario['apertura']} a {$horario['cierre']}\n"
            . "_(excepto martes y hora de comida 4pm-5pm)_\n\n"
            . "Escríbenos en horario de atención y con gusto te agendamos 😊"
        );
    }

    private function notificarAdmin(Barberia $barberia, Cliente $cliente, string $ultimoMensaje): void
    {
        if (! $barberia->whatsapp_admin_numero) return;

        $this->api->enviarTexto(
            $barberia->whatsapp_admin_numero,
            $barberia,
            "🔔 *Cliente solicitó asesor*\n\n"
            . "👤 {$cliente->nombre}\n"
            . "📱 {$cliente->telefono}\n"
            . "💬 Último mensaje: {$ultimoMensaje}"
        );
    }

    // ─── Parsers y buscadores ─────────────────────────────────────────────

    private function buscarServicio(string $texto, Barberia $barberia): ?Servicio
    {
        $texto     = trim($texto);
        $servicios = Servicio::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        if (is_numeric($texto)) {
            return $servicios->values()->get((int) $texto - 1);
        }

        return $servicios->first(fn($s) => str_contains(strtolower($s->nombre), strtolower($texto)));
    }

    private function parsearFecha(string $texto, Barberia $barberia): ?string
    {
        $texto           = strtolower(trim($texto));
        $diasDisponibles = $this->disponibilidad->diasDisponibles($barberia, 7);

        if (is_numeric($texto)) {
            return $diasDisponibles->values()->get((int) $texto - 1);
        }

        if (in_array($texto, ['hoy', 'today'])) {
            $hoy = now()->toDateString();
            return $diasDisponibles->contains($hoy) ? $hoy : null;
        }

        if (in_array($texto, ['mañana', 'manana', 'tomorrow'])) {
            $manana = now()->addDay()->toDateString();
            return $diasDisponibles->contains($manana) ? $manana : null;
        }

        try {
            Carbon::setLocale('es');
            $fecha = Carbon::parse($texto)->toDateString();
            return $diasDisponibles->contains($fecha) ? $fecha : null;
        } catch (\Exception) {
            return null;
        }
    }

    private function buscarSlot(string $texto, $slots): ?array
    {
        $texto = trim($texto);

        if (is_numeric($texto)) {
            return $slots->values()->get((int) $texto - 1);
        }

        return $slots->first(fn($slot) =>
            str_contains(strtolower($slot['hora_formato']), strtolower($texto))
            || str_contains($slot['hora'], $texto)
        );
    }

    private function quiereAsesor(string $texto): bool
    {
        $palabras = ['asesor', 'humano', 'persona', 'ayuda', 'agente', 'hablar con alguien'];
        $texto    = strtolower(trim($texto));
        foreach ($palabras as $p) {
            if (str_contains($texto, $p)) return true;
        }
        return false;
    }

    private function saludo(): string
    {
        $hora = now()->setTimezone('America/Mexico_City')->hour;
        return match (true) {
            $hora < 12  => '¡Buenos días',
            $hora < 19  => '¡Buenas tardes',
            default     => '¡Buenas noches',
        };
    }

    private function formatoAmPm(string $hora): string
    {
        return Carbon::createFromFormat('H:i', $hora)->format('g:i A');
    }
}