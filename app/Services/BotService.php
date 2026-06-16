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
        $cliente = Cliente::firstOrCreateByTelefono($telefono, $nombrePerfil ?? '', $barberia);

        // ── Guardias ──────────────────────────────────────────────────────

        if ($cliente->bloqueado) return;

        // Asesor devuelve el control al bot
        if ($estado->modo_asesor && strtolower(trim($texto)) === 'bot') {
            $estado->desactivarModoAsesor();
            $this->api->enviarTexto($telefono, $barberia,
                "¡De vuelta con el asistente! 🤖 ¿En qué te puedo ayudar?"
            );
            $this->enviarMenuPrincipal($telefono, $barberia, $estado);
            return;
        }

        if ($estado->modo_asesor) return;

        // El cliente quiere hablar con un asesor
        if ($this->quiereAsesor($texto)) {
            $estado->activarModoAsesor();
            $this->api->enviarTexto($telefono, $barberia,
                "Entendido {$cliente->nombre} 👋 En un momento un asesor te atenderá.\n\n"
                . "Si quieres volver al bot automático escribe *bot*."
            );
            $this->notificarAdmin($barberia, $cliente, "El cliente escribió: {$texto}", '🔔 *Cliente solicitó asesor*');
            return;
        }

        // ── Detección de intenciones (FAQ) ───────────────────────────────
        // Va ANTES del flujo de pasos para interceptar preguntas comunes
        if ($this->detectarIntencion($texto, $barberia, $cliente, $telefono, $estado)) return;

        // ── Detección de respuesta a recordatorio ─────────────────────────
        // Va ANTES del router de pasos, PERO solo si el usuario NO está a la mitad de un flujo activo.
        // Si está agendando y responde 'no' a '¿agregar otro servicio?', no debe cancelar su cita pendiente.
        if ($estado->paso === ConvEstado::PASO_INICIO || $estado->paso === ConvEstado::PASO_ESPERANDO_OPCION_MENU) {
            $respuestaLower = strtolower(trim($texto));
            $esConfirmacion = in_array($respuestaLower, ['sí', 'si', 's', 'confirmo', 'confirmar']);
            $esCancelacion  = in_array($respuestaLower, ['no', 'no puedo', 'cancelar']);

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
        }

        // Sesión expirada: reiniciar suavemente
        if ($estado->estaExpirada()) {
            $estado->reiniciar();
        }

        // ── Router de pasos ───────────────────────────────────────────────
        match ($estado->paso) {
            ConvEstado::PASO_INICIO             => $this->pasoInicio($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_OPCION_MENU => $this->pasoOpcionMenu($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_NOMBRE   => $this->pasoNombre($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_SERVICIO => $this->pasoServicio($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_AGREGAR_OTRO_SERVICIO => $this->pasoAgregarOtro($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_BARBERO  => $this->pasoBarbero($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_FECHA    => $this->pasoFecha($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_HORA     => $this->pasoHora($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_CONFIRMANDO_CITA   => $this->pasoConfirmar($telefono, $barberia, $cliente, $estado, $texto),
            ConvEstado::PASO_ESPERANDO_CALIFICACION => $this->pasoCalificacion($telefono, $barberia, $cliente, $estado, $texto),
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
        $saludoPersonalizado = $nombre ? " {$nombre}!" : "!";

        $msg = "{$saludo}{$saludoPersonalizado} 👋 Bienvenido a *{$barberia->nombre}*.\n\n"
             . "Soy tu asistente virtual 🤖. ¿En qué te puedo ayudar hoy?";

        $this->api->enviarTexto($telefono, $barberia, $msg);
        $this->enviarMenuPrincipal($telefono, $barberia, $estado);
    }

    private function enviarMenuPrincipal(string $telefono, Barberia $barberia, ConvEstado $estado): void
    {
        $msg = "Responde con el *número* de la opción deseada:\n\n"
             . "1️⃣ Agendar una cita\n"
             . "2️⃣ Ver precios y servicios\n"
             . "3️⃣ Conocer nuestro horario y ubicación\n"
             . "4️⃣ Ver mis citas pendientes\n"
             . "5️⃣ Hablar con un humano";

        $estado->avanzar(ConvEstado::PASO_ESPERANDO_OPCION_MENU);
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function pasoOpcionMenu(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $opcion = trim($texto);

        switch ($opcion) {
            case '1':
                $this->iniciarAgendamiento($telefono, $barberia, $cliente, $estado);
                break;
            case '2':
                // Reutilizamos el FAQ de precios pasando la palabra clave
                $this->detectarIntencion('precios', $barberia, $cliente, $telefono, $estado);
                break;
            case '3':
                // Mandamos primero el horario y luego la ubicación
                $this->detectarIntencion('horario', $barberia, $cliente, $telefono, $estado, false);
                $this->detectarIntencion('ubicación', $barberia, $cliente, $telefono, $estado);
                break;
            case '4':
                $this->detectarIntencion('mi cita', $barberia, $cliente, $telefono, $estado);
                break;
            case '5':
                if (! $barberia->estaAbierta()) {
                    $this->responderFueraDeHorario($telefono, $barberia, $estado);
                    return;
                }
                $estado->activarModoAsesor();
                $this->api->enviarTexto($telefono, $barberia,
                    "Entendido 👋 En un momento un asesor te atenderá.\n\n"
                    . "Si quieres volver al menú principal escribe *bot*."
                );
                $this->notificarAdmin($barberia, $cliente, "El cliente solicitó hablar con un humano desde el menú principal.", '👤 *Cliente solicitó asesor*');
                break;
            default:
                $this->api->enviarTexto($telefono, $barberia,
                    "Por favor, responde con un número del 1 al 5 para elegir una opción."
                );
                $this->enviarMenuPrincipal($telefono, $barberia, $estado);
                break;
        }
    }

    private function iniciarAgendamiento(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado
    ): void {
        $nombre = $cliente->nombre !== 'Cliente' ? $cliente->nombre : '';

        if ($nombre) {
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_SERVICIO, ['nombre' => $nombre]);
            $this->enviarMenuServicios($telefono, $barberia, $cliente);
            return;
        }

        $estado->avanzar(ConvEstado::PASO_ESPERANDO_NOMBRE);
        $this->api->enviarTexto($telefono, $barberia,
            "Para agendar tu cita necesito algunos datos.\n\n"
            . "¿Cuál es tu nombre?"
        );
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

        $datosTemp = $estado->datos_temp;
        $serviciosIds = $datosTemp['servicios_ids'] ?? [];
        $serviciosIds[] = $servicio->id;
        $datosTemp['servicios_ids'] = $serviciosIds;

        if ($servicio->precio_variable) {
            $this->api->enviarTexto($telefono, $barberia,
                "Perfecto, agregamos *{$servicio->nombre}* 💇\n\n"
                . "ℹ️ El precio depende del largo de tu cabello, te lo confirmamos cuando llegues.\n\n"
                . "¿Te gustaría agregar otro servicio? Responde *sí* o *no*."
            );
            $estado->avanzar(ConvEstado::PASO_AGREGAR_OTRO_SERVICIO, $datosTemp);
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

        $estado->avanzar(ConvEstado::PASO_AGREGAR_OTRO_SERVICIO, $datosTemp);
        $this->api->enviarTexto($telefono, $barberia,
            "Genial, agregamos *{$servicio->nombre}* — {$servicio->precioTexto()} 💰\n\n"
            . "¿Te gustaría agendar algún otro servicio para la misma cita? Responde *sí* o *no*."
        );
    }

    private function pasoAgregarOtro(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $respuesta = strtolower(trim($texto));
        $esSi = in_array($respuesta, ['sí', 'si', 's', 'yes', 'claro', 'por supuesto']);
        $esNo = in_array($respuesta, ['no', 'n', 'no gracias', 'solo eso', 'ninguno']);

        if ($esSi) {
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_SERVICIO, $estado->datos_temp);
            $this->enviarMenuServicios($telefono, $barberia, $cliente);
            return;
        }

        if ($esNo) {
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_BARBERO, $estado->datos_temp);
            $this->api->enviarTexto($telefono, $barberia,
                "Perfecto. ¿Con quién te gustaría atenderte? Aquí nuestros barberos:"
            );
            $this->enviarMenuBarberos($telefono, $barberia);
            return;
        }

        $this->api->enviarTexto($telefono, $barberia,
            "Por favor responde *sí* para agregar otro servicio o *no* para continuar con tu reservación."
        );
    }

    private function enviarMenuBarberos(string $telefono, Barberia $barberia): void
    {
        $barberos = Barbero::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->get();

        if ($barberos->isEmpty()) {
            // Si no hay barberos registrados, salta directo a elegir fecha
            $this->api->enviarTexto($telefono, $barberia, "¿Para qué fecha te gustaría? Aquí los días disponibles:");
            $this->enviarDiasDisponibles($telefono, $barberia);
            return;
        }

        $msg = "Elige un número:\n\n"
             . "1. Cualquiera (el primero disponible)\n";

        foreach ($barberos as $i => $barbero) {
            $n = $i + 2;
            $msg .= "{$n}. {$barbero->nombre}\n";
        }
        $msg .= "\n*(0. Volver al menú principal)*";

        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function pasoBarbero(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $barberos = Barbero::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->get();

        if ($barberos->isEmpty()) {
            // No debería llegar aquí, pero por si acaso
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA);
            $this->api->enviarTexto($telefono, $barberia, "¿Para qué fecha te gustaría?");
            $this->enviarDiasDisponibles($telefono, $barberia);
            return;
        }

        $opcion = (int) trim($texto);
        $barberoId = null;

        if ($opcion === 1) {
            $barberoId = null;
        } elseif ($opcion >= 2 && $opcion <= $barberos->count() + 1) {
            $barberoId = $barberos[$opcion - 2]->id;
        } else {
            $this->api->enviarTexto($telefono, $barberia,
                "Opción no válida. Elige un número de la lista."
            );
            $this->enviarMenuBarberos($telefono, $barberia);
            return;
        }

        $datos = $estado->datos_temp;
        $datos['barbero_id'] = $barberoId;
        $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA, $datos);
        $this->api->enviarTexto($telefono, $barberia,
            "¡Excelente! ¿Para qué fecha te gustaría? Aquí los días disponibles:"
        );
        $this->enviarDiasDisponibles($telefono, $barberia, $barberoId);
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

        $datos = $estado->datos_temp;
        $datos['fecha'] = $fecha;
        $estado->avanzar(ConvEstado::PASO_ESPERANDO_HORA, $datos);

        $servicios = Servicio::whereIn('id', $estado->datos_temp['servicios_ids'])->get();
        $duracionTotal = $servicios->sum('duracion_min');
        $barberoId = $estado->datos_temp['barbero_id'] ?? null;
        
        $slots = $this->disponibilidad->slotsDia($barberia, $fecha, $duracionTotal, $barberoId);

        if ($slots->isEmpty()) {
            $this->api->enviarTexto($telefono, $barberia,
                "Lo sentimos, ese día ya no hay horarios disponibles 😔\n\n"
                . "Elige otra fecha:"
            );
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_FECHA);
            $this->enviarDiasDisponibles($telefono, $barberia, $barberoId);
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
        $servicios = Servicio::whereIn('id', $datos['servicios_ids'])->get();
        $duracionTotal = $servicios->sum('duracion_min');
        $barberoId = $datos['barbero_id'] ?? null;

        $slots = $this->disponibilidad->slotsDia($barberia, $fecha, $duracionTotal, $barberoId);
        $slot  = $this->buscarSlot($texto, $slots);

        if (! $slot) {
            $this->api->enviarTexto($telefono, $barberia,
                "No encontré ese horario ⏰ Elige un número de la lista."
            );
            $this->enviarSlotsHorarios($telefono, $barberia, $slots);
            return;
        }

        if (! $this->disponibilidad->slotSigueDisponible($barberia, $fecha, $slot['hora'], $duracionTotal)) {
            $this->api->enviarTexto($telefono, $barberia,
                "Ese horario acaba de ocuparse 😅 Elige otro:"
            );
            $slots = $this->disponibilidad->slotsDia($barberia, $fecha, $duracionTotal, $barberoId);
            $this->enviarSlotsHorarios($telefono, $barberia, $slots);
            return;
        }

        $datos['hora'] = $slot['hora'];
        $datos['barbero_id'] = $slot['barbero_id'];
        $estado->avanzar(ConvEstado::PASO_CONFIRMANDO_CITA, $datos);

        $fechaFormato = Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $barberos     = Barbero::where('barberia_id', $barberia->id)->count();

        $nombresServicios = $servicios->pluck('nombre')->join(', ');
        
        $totalPrecio = $servicios->sum('precio');
        $precioTexto = ($totalPrecio == 0 && $servicios->where('precio_variable', true)->count() > 0) 
            ? 'Precio variable' 
            : '$' . number_format($totalPrecio, 2);

        $resumen = "✅ *Resumen de tu cita*\n\n"
                 . "👤 *Cliente:* {$cliente->nombre}\n"
                 . "✂️ *Servicios:* {$nombresServicios}\n"
                 . "📅 *Fecha:* {$fechaFormato}\n"
                 . "🕐 *Hora:* {$slot['hora_formato']}\n"
                 . "💰 *Total:* {$precioTexto}\n";

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
        $servicios = Servicio::whereIn('id', $datos['servicios_ids'])->get();
        $duracionTotal = $servicios->sum('duracion_min');
        $horaFin  = Carbon::createFromFormat('H:i', $datos['hora'])
            ->addMinutes($duracionTotal)
            ->format('H:i');

        // ─── EVITAR RESERVAS DOBLES (Verificación de último segundo) ───
        $slots = $this->disponibilidad->slotsDia(
            $barberia,
            $datos['fecha'],
            $duracionTotal,
            $datos['barbero_id'] ?? null
        );

        $slotSigueDisponible = $slots->contains(function ($s) use ($datos) {
            return $s['hora'] === $datos['hora'] 
                && ($datos['barbero_id'] ?? null) == $s['barbero_id'];
        });

        if (! $slotSigueDisponible) {
            $estado->avanzar(ConvEstado::PASO_ESPERANDO_HORA);
            $this->api->enviarTexto($telefono, $barberia,
                "¡Uy! 😅 Alguien más acaba de ganar y reservar ese mismo horario justo mientras confirmabas.\n\n"
            );
            $this->enviarSlotsHorarios($telefono, $barberia, $slots);
            return;
        }
        // ───────────────────────────────────────────────────────────────

        $cita = Cita::create([
            'barberia_id' => $barberia->id,
            'cliente_id'  => $cliente->id,
            'barbero_id'  => $datos['barbero_id'] ?? null,
            'fecha'       => $datos['fecha'],
            'hora_inicio' => $datos['hora'],
            'hora_fin'    => $horaFin,
            'estado'      => 'pendiente',
        ]);
        
        $cita->servicios()->attach($datos['servicios_ids']);

        $cliente->increment('total_visitas');
        $estado->reiniciar();

        $nombresServicios = $servicios->pluck('nombre')->join(', ');

        $fechaFormato = Carbon::parse($datos['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
        $this->api->enviarTexto($telefono, $barberia,
            "🎉 ¡Tu cita está agendada!\n\n"
            . "📅 *{$fechaFormato}* a las *{$this->formatoAmPm($datos['hora'])}*\n"
            . "✂️ {$nombresServicios}\n\n"
            . "Te enviaremos un recordatorio 24 horas antes y 1 hora antes.\n\n"
            . "📍 {$barberia->direccion}\n\n"
            . "¡Te esperamos! 💈\n\n"
            . "_Para volver a hablar con el bot escribe hola._"
        );

        ProgramarRecordatorios::dispatch($cita);

        // Notificar al barbero o administrador
        $barberoNumero = $barberia->whatsapp_admin_numero; // Por defecto avisa al administrador de la barbería
        if ($cita->barbero && $cita->barbero->telefono) {
            $barberoNumero = preg_replace('/[^0-9]/', '', $cita->barbero->telefono); // Avisar directo al especialista
        }

        $barberoAsignado = $cita->barbero ? $cita->barbero->nombre : 'Cualquier barbero disponible';
        $mensajeAdmin = "🔔 *Nueva Cita Agendada*\n\n"
                      . "👤 *Cliente:* {$cliente->nombre} ({$cliente->telefono})\n"
                      . "✂️ *Servicios:* {$nombresServicios}\n"
                      . "📅 *Fecha:* {$fechaFormato}\n"
                      . "🕐 *Hora:* {$this->formatoAmPm($datos['hora'])}\n"
                      . "💈 *Barbero asignado:* {$barberoAsignado}\n";

        try {
            $this->api->enviarTexto($barberoNumero, $barberia, $mensajeAdmin);
        } catch (\Exception $e) {
            // Ignorar errores si el número del admin no es válido
            \Log::error("Error al notificar al admin/barbero: " . $e->getMessage());
        }
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

    // ─── Calificación del Servicio ────────────────────────────────────────

    public function pedirCalificacion(Cita $cita): void
    {
        $barbero = $cita->barbero ? $cita->barbero->nombre : 'nuestro equipo';
        $mensaje = "¡Hola {$cita->cliente->nombre}! Gracias por visitarnos hoy.\n\n"
                 . "¿Qué te pareció el servicio con *{$barbero}*?\n\n"
                 . "Por favor, califícalo respondiendo con un *número del 1 al 5*\n\n"
                 . "1 = Malo\n"
                 . "5 = Excelente";

        $this->api->enviarTexto($cita->cliente->telefono, $cita->barberia, $mensaje);

        $estado = ConvEstado::obtenerOCrear($cita->cliente->telefono);
        $estado->avanzar(ConvEstado::PASO_ESPERANDO_CALIFICACION, ['cita_id' => $cita->id]);
    }

    private function pasoCalificacion(
        string $telefono, Barberia $barberia,
        Cliente $cliente, ConvEstado $estado, string $texto
    ): void {
        $t = strtolower(trim($texto));
        if (in_array($t, ['menu', 'menú', 'omitir', 'cancelar', 'salir'])) {
            $estado->reiniciar();
            $this->pasoInicio($telefono, $barberia, $cliente, $estado, $texto);
            return;
        }

        $calificacion = (int) $t;

        if ($calificacion < 1 || $calificacion > 5) {
            $this->api->enviarTexto($telefono, $barberia,
                "Por favor responde solo con un número del 1 al 5 ⭐\n\n(o escribe *omitir* si no deseas calificar)."
            );
            return;
        }

        $citaId = $estado->datos_temp['cita_id'] ?? null;
        if ($citaId) {
            $cita = Cita::find($citaId);
            if ($cita) {
                $cita->update(['calificacion' => $calificacion]);
            }
        }

        $this->api->enviarTexto($telefono, $barberia,
            "¡Muchas gracias por tu calificación! Nos ayuda a mejorar 😊💈\n\n"
            . "Para volver a usar el bot en el futuro, solo escríbenos."
        );
        
        $estado->reiniciar();
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

        $msg .= "\nEscribe el *número* del servicio que deseas, o *0* para volver al menú.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function enviarDiasDisponibles(string $telefono, Barberia $barberia, ?int $barberoId = null): void
    {
        $dias = $this->disponibilidad->diasDisponibles($barberia, 7, $barberoId);

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

        $msg .= "\nEscribe el *número* del día que prefieres, o *0* para volver al menú.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function enviarSlotsHorarios(string $telefono, Barberia $barberia, $slots): void
    {
        $msg = "⏰ *Horarios disponibles:*\n\n";
        $slots->values()->each(function ($slot, $i) use (&$msg) {
            $msg .= ($i + 1) . ". {$slot['hora_formato']}\n";
        });
        $msg .= "\nEscribe el *número* del horario que prefieres, o *0* para volver al menú.";
        $this->api->enviarTexto($telefono, $barberia, $msg);
    }

    private function responderFueraDeHorario(
        string $telefono, Barberia $barberia, ConvEstado $estado
    ): void {
        if ($estado->paso === ConvEstado::PASO_FUERA_DE_HORARIO) return;

        $estado->avanzar(ConvEstado::PASO_FUERA_DE_HORARIO);

        $h = $barberia->horarioParseado();
        $nombres = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $diasCerrados = collect($h['dias_cerrado'])->map(fn($d) => $nombres[$d])->join(' y ');

        // Días en que SÍ trabaja
        $diasTrabaja = collect(range(0,6))
            ->filter(fn($d) => !in_array($d, $h['dias_cerrado']))
            ->map(fn($d) => $nombres[$d]);

        $primerDia  = $diasTrabaja->first();
        $ultimoDia  = $diasTrabaja->last();

        $apertura = $h['apertura'];
        $cierre   = $h['cierre'];

        // Convertir a formato 12h para el mensaje
        $aperturaFmt = Carbon::createFromFormat('H:i', $apertura)->format('g:i A');
        $cierre12h   = Carbon::createFromFormat('H:i', $cierre)->format('g:i A');
        $comIni12h   = Carbon::createFromFormat('H:i', $h['comida_inicio'])->format('g:i A');
        $comFin12h   = Carbon::createFromFormat('H:i', $h['comida_fin'])->format('g:i A');

        $this->api->enviarTexto($telefono, $barberia,
            "¡Hola! 👋 Gracias por contactarnos.\n\n"
            . "En este momento estamos cerrados 🕐\n\n"
            . "Nuestro horario es:\n"
            . "📅 {$primerDia} a {$ultimoDia}: {$aperturaFmt} – {$cierre12h}\n"
            . ($diasCerrados ? "🚫 Descansamos: {$diasCerrados}\n" : "")
            . "🍽️ Comida: {$comIni12h} – {$comFin12h}\n\n"
            . "Escríbenos dentro de nuestro horario y con gusto te agendamos 😊"
        );
    }

    private function notificarAdmin(Barberia $barberia, Cliente $cliente, string $ultimoMensaje, string $titulo = '🔔 *Aviso del Bot*'): void
    {
        if (! $barberia->whatsapp_admin_numero) return;

        $this->api->enviarTexto(
            $barberia->whatsapp_admin_numero,
            $barberia,
            "{$titulo}\n\n"
            . "👤 {$cliente->nombre}\n"
            . "📱 {$cliente->telefono}\n"
            . "💬 {$ultimoMensaje}"
        );
    }

    // ─── Detección de intenciones (FAQ) ──────────────────────────────────
    // Devuelve true si se manejó la intención (para cortocircuitar el flujo)

    private function detectarIntencion(
        string $texto, Barberia $barberia,
        Cliente $cliente, string $telefono, ConvEstado $estado,
        bool $reenviarMenu = true
    ): bool {
        $t = strtolower(trim($texto));

        // ── Comandos globales ────────────────────────────────────────────
        // Reiniciar / volver al menú principal
        if ($t === '0' || $this->contiene($t, ['menu', 'menú', 'inicio', 'reiniciar', 'empezar', 'volver', 'hola', 'buenas', 'hi', 'hello', 'buenos días', 'buenas tardes', 'buenas noches'])) {
            $estado->reiniciar();
            $this->pasoInicio($telefono, $barberia, $cliente, $estado, $texto);
            return true;
        }

        // Cancelar mi cita
        if ($this->contiene($t, ['cancelar mi cita', 'quiero cancelar', 'cancela mi cita', 'no voy a ir', 'no puedo ir', 'cancel'])) {
            $cita = Cita::where('barberia_id', $barberia->id)
                ->whereHas('cliente', fn($q) => $q->where('telefono', $telefono))
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->whereDate('fecha', '>=', today())
                ->orderBy('fecha')->orderBy('hora_inicio')
                ->first();

            if ($cita) {
                $fecha = Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
                $hora  = $this->formatoAmPm($cita->hora_inicio);
                $cita->cancelar('Cliente canceló por WhatsApp');
                $estado->reiniciar();
                $this->api->enviarTexto($telefono, $barberia,
                    "✅ Tu cita del *{$fecha}* a las *{$hora}* ha sido cancelada.\n\n"
                    . "Cuando quieras agendar de nuevo escríbenos 😊"
                );
                $this->notificarAdmin($barberia, $cliente, "Canceló su cita del {$fecha} a las {$hora} por WhatsApp.", '❌ *Cita Cancelada*');
            } else {
                $this->api->enviarTexto($telefono, $barberia,
                    "No encontré citas pendientes para cancelar 🤔\n\n"
                    . "Si crees que hay un error escribe *asesor*."
                );
            }
            if ($reenviarMenu) {
                $this->enviarMenuPrincipal($telefono, $barberia, $estado);
            }
            return true;
        }

        // Ver mi cita / mis citas
        if ($this->contiene($t, ['mi cita', 'mis citas', 'tengo cita', 'ver cita', 'mis reservas', 'mi reserva', 'cuando es mi cita', 'a qué hora'])) {
            $cita = Cita::where('barberia_id', $barberia->id)
                ->whereHas('cliente', fn($q) => $q->where('telefono', $telefono))
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->whereDate('fecha', '>=', today())
                ->orderBy('fecha')->orderBy('hora_inicio')
                ->first();

            if ($cita) {
                $fecha = Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
                $hora  = $this->formatoAmPm($cita->hora_inicio);
                $this->api->enviarTexto($telefono, $barberia,
                    "📋 *Tu próxima cita:*\n\n"
                    . "📅 *Fecha:* {$fecha}\n"
                    . "🕐 *Hora:* {$hora}\n"
                    . "✂️ *Servicios:* {$cita->nombresServicios()}\n"
                    . "📍 *Lugar:* {$barberia->direccion}\n\n"
                    . "Para cancelarla escribe *cancelar mi cita*.\n"
                    . "Para volver al menú escribe *0* o *menú*."
                );
            } else {
                $this->api->enviarTexto($telefono, $barberia,
                    "No tienes citas próximas agendadas 📅\n\n"
                    . "¿Quieres agendar una? Escribe *hola* para comenzar."
                );
            }
            return true;
        }

        // ── Preguntas de horario ─────────────────────────────────────────
        if ($this->contiene($t, ['horario', 'hora', 'abierto', 'abre', 'cierra', 'a qué hora abren', 'a que hora', 'cuando abren', 'cuando cierran', 'están abiertos', 'estan abiertos', 'qué días', 'que dias', 'días de atención', 'dias de atencion'])) {
            $h = $barberia->horarioParseado();
            $nombres = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            $diasCerrados = collect($h['dias_cerrado'])->map(fn($d) => $nombres[$d])->join(' y ');

            $diasTrabaja = collect(range(0,6))
                ->filter(fn($d) => !in_array($d, $h['dias_cerrado']))
                ->map(fn($d) => $nombres[$d]);
            $primerDia = $diasTrabaja->first();
            $ultimoDia = $diasTrabaja->last();

            $aperturaFmt = Carbon::createFromFormat('H:i', $h['apertura'])->format('g:i A');
            $cierre12h   = Carbon::createFromFormat('H:i', $h['cierre'])->format('g:i A');
            $comIni12h   = Carbon::createFromFormat('H:i', $h['comida_inicio'])->format('g:i A');
            $comFin12h   = Carbon::createFromFormat('H:i', $h['comida_fin'])->format('g:i A');

            $abiertaAhora = $barberia->estaAbierta() ? '🟢 *Ahora estamos abiertos*' : '🔴 *Ahora estamos cerrados*';

            $this->api->enviarTexto($telefono, $barberia,
                "🕐 *Nuestro horario:*\n\n"
                . "{$abiertaAhora}\n\n"
                . "📅 {$primerDia} a {$ultimoDia}: {$aperturaFmt} – {$cierre12h}\n"
                . "🍽️ Comida: {$comIni12h} – {$comFin12h}\n"
                . ($diasCerrados ? "🚫 Día de descanso: {$diasCerrados}\n" : "")
            );
            if ($reenviarMenu) {
                $this->enviarMenuPrincipal($telefono, $barberia, $estado);
            }
            return true;
        }

        // ── Preguntas de precios y servicios ─────────────────────────────
        if ($this->contiene($t, ['precio', 'precios', 'cuánto cuesta', 'cuanto cuesta', 'cuánto cobran', 'cuanto cobran', 'servicio', 'servicios', 'qué hacen', 'que hacen', 'qué ofrecen', 'que ofrecen', 'catalogo', 'catálogo', 'lista de servicios'])) {
            $servicios = Servicio::where('barberia_id', $barberia->id)
                ->where('activo', true)
                ->orderBy('categoria')->orderBy('id')
                ->get();

            $msg             = "💈 *Nuestros servicios y precios:*\n";
            $categoriaActual = '';

            foreach ($servicios as $s) {
                if ($s->categoria !== $categoriaActual) {
                    $msg .= "\n*{$s->categoria}*\n";
                    $categoriaActual = $s->categoria;
                }
                $msg .= "• {$s->nombre} — {$s->precioTexto()}\n";
            }

            $this->api->enviarTexto($telefono, $barberia, $msg);
            if ($reenviarMenu) {
                $this->enviarMenuPrincipal($telefono, $barberia, $estado);
            }
            return true;
        }

        // ── Ubicación / Dirección ────────────────────────────────────────
        if ($this->contiene($t, ['donde', 'dónde', 'dirección', 'direccion', 'ubicación', 'ubicacion', 'como llego', 'cómo llego', 'mapa', 'están', 'estan', 'localización', 'localizacion'])) {
            $this->api->enviarTexto($telefono, $barberia,
                "📍 *¿Cómo llegar?*\n\n"
                . "*{$barberia->nombre}*\n"
                . "{$barberia->direccion}\n"
            );
            if ($reenviarMenu) {
                $this->enviarMenuPrincipal($telefono, $barberia, $estado);
            }
            return true;
        }

        // ── Gracias / Despedidas ─────────────────────────────────────────
        if ($this->contiene($t, ['gracias', 'thank', 'thanks', 'muchas gracias', 'ok gracias', 'perfecto gracias', 'listo', 'hasta luego', 'adios', 'adiós', 'bye', 'chao'])) {
            $this->api->enviarTexto($telefono, $barberia,
                "¡De nada, {$cliente->nombre}! 😊 Fue un placer atenderte.\n\n"
                . "Cuando necesites algo más, aquí estaremos. ¡Hasta pronto! 💈"
            );
            $estado->reiniciar();
            return true;
        }

        return false; // No se detectó ninguna intención, continúa el flujo normal
    }

    // Helper: verifica si el texto contiene alguna de las palabras clave
    private function contiene(string $texto, array $palabras): bool
    {
        foreach ($palabras as $p) {
            if (str_contains($texto, $p)) return true;
        }
        return false;
    }

    // ─── Parsers y buscadores ─────────────────────────────────────────────


    private function buscarServicio(string $texto, Barberia $barberia): ?Servicio
    {
        $texto     = trim($texto);
        $servicios = Servicio::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->orderBy('categoria')
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
        return Carbon::parse($hora)->format('g:i A');
    }
}