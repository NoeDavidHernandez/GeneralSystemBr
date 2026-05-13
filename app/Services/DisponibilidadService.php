<?php

namespace App\Services;

use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\BloqueoHorario;
use App\Models\Cita;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class DisponibilidadService
{
    // Días de la semana en que la barbería NO trabaja (0=domingo, 2=martes)
    // Esto viene de barberia.horario_json, aquí son valores de ejemplo para Malva Barber
    private const DIAS_CERRADO = [2]; // martes

    // Hora de comida de Malva Barber: 4:00pm a 5:00pm
    private const COMIDA_INICIO = '16:00';
    private const COMIDA_FIN    = '17:00';

    // ─── API pública ──────────────────────────────────────────────────────

    /**
     * Devuelve los próximos N días disponibles (no cerrados, no bloqueados).
     * El bot los muestra al cliente para que elija fecha.
     */
    public function diasDisponibles(Barberia $barberia, int $cantidad = 7): Collection
    {
        $horario = $barberia->horarioParseado();
        $diasCerrado = $horario['dias_cerrado'] ?? self::DIAS_CERRADO;

        $dias = collect();
        $fecha = now()->addHours(2); // mínimo 2 hrs de anticipación
        $intentos = 0;

        while ($dias->count() < $cantidad && $intentos < 60) {
            $intentos++;
            $carbon = Carbon::parse($fecha)->startOfDay();

            // Saltar si es día cerrado
            if (in_array($carbon->dayOfWeek, $diasCerrado)) {
                $fecha = $carbon->addDay();
                continue;
            }

            // Saltar si hay bloqueo de día completo
            if (BloqueoHorario::estaBloquead($carbon->toDateString(), '11:00')) {
                $fecha = $carbon->addDay();
                continue;
            }

            // Verificar que tenga al menos un slot libre ese día
            if ($this->slotsDia($barberia, $carbon->toDateString())->isNotEmpty()) {
                $dias->push($carbon->toDateString());
            }

            $fecha = $carbon->addDay();
        }

        return $dias;
    }

    /**
     * Devuelve los slots horarios disponibles para un día específico.
     * Considera: horario del negocio, comida, citas existentes, bloqueos, barberos.
     *
     * Retorna Collection de ['hora' => '11:00', 'barbero_id' => 3, 'barbero_nombre' => 'Carlos']
     */
    public function slotsDia(Barberia $barberia, string $fecha, int $duracionMin = 30): Collection
    {
        $horario   = $barberia->horarioParseado();
        $apertura  = $horario['apertura']  ?? '11:00';
        $cierre    = $horario['cierre']    ?? '19:00';
        $comidaIni = $horario['comida_inicio'] ?? self::COMIDA_INICIO;
        $comidaFin = $horario['comida_fin']    ?? self::COMIDA_FIN;

        // Generar todos los slots posibles del día
        $slots = $this->generarSlots($apertura, $cierre, $duracionMin, $comidaIni, $comidaFin);

        // Si es hoy, descartar slots que ya pasaron o están dentro de las 2 hrs
        if ($fecha === now()->toDateString()) {
            $limiteMin = now()->addHours(2)->format('H:i');
            $slots = $slots->filter(fn($slot) => $slot >= $limiteMin);
        }

        // Obtener barberos activos de esta barbería
        $barberos = Barbero::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->get();

        // Si no hay barberos registrados, crear uno "virtual" para compatibilidad
        // con barberías de un solo dueño que no configuraron barberos
        if ($barberos->isEmpty()) {
            $barberos = collect([(object)[
                'id'     => null,
                'nombre' => $barberia->nombre,
            ]]);
        }

        // Para cada slot, verificar si al menos un barbero está libre
        $slotsDisponibles = collect();

        foreach ($slots as $hora) {
            // Verificar bloqueo manual de ese horario específico
            if (BloqueoHorario::estaBloquead($fecha, $hora)) {
                continue;
            }

            $horaFin = Carbon::createFromFormat('H:i', $hora)
                ->addMinutes($duracionMin)
                ->format('H:i');

            foreach ($barberos as $barbero) {
                if ($this->barberoEstaLibre($barbero->id, $barberia->id, $fecha, $hora, $horaFin)) {
                    $slotsDisponibles->push([
                        'hora'           => $hora,
                        'hora_formato'   => $this->formatoAmPm($hora),
                        'barbero_id'     => $barbero->id,
                        'barbero_nombre' => $barbero->nombre,
                    ]);
                    break; // Con un barbero libre basta para mostrar el slot
                }
            }
        }

        return $slotsDisponibles;
    }

    /**
     * Para un slot específico, devuelve qué barberos están libres.
     * Usado cuando el cliente elige hora y queremos asignar barbero.
     */
    public function barberosLibresEnSlot(
        Barberia $barberia,
        string $fecha,
        string $hora,
        int $duracionMin = 30
    ): Collection {
        $horaFin = Carbon::createFromFormat('H:i', $hora)
            ->addMinutes($duracionMin)
            ->format('H:i');

        return Barbero::where('barberia_id', $barberia->id)
            ->where('activo', true)
            ->get()
            ->filter(fn($b) => $this->barberoEstaLibre($b->id, $barberia->id, $fecha, $hora, $horaFin));
    }

    /**
     * Verifica si un slot puntual sigue disponible antes de crear la cita.
     * (Evita race condition si dos clientes eligen al mismo tiempo)
     */
    public function slotSigueDisponible(
        Barberia $barberia,
        string $fecha,
        string $hora,
        int $duracionMin = 30
    ): bool {
        return $this->barberosLibresEnSlot($barberia, $fecha, $hora, $duracionMin)->isNotEmpty();
    }

    // ─── Privados ─────────────────────────────────────────────────────────

    private function generarSlots(
        string $apertura,
        string $cierre,
        int $intervalo,
        string $comidaIni,
        string $comidaFin
    ): Collection {
        $slots  = collect();
        $actual = Carbon::createFromFormat('H:i', $apertura);
        $fin    = Carbon::createFromFormat('H:i', $cierre);
        $cIni   = Carbon::createFromFormat('H:i', $comidaIni);
        $cFin   = Carbon::createFromFormat('H:i', $comidaFin);

        while ($actual->lt($fin)) {
            $horaStr = $actual->format('H:i');

            // Saltar hora de comida
            if (! ($actual->gte($cIni) && $actual->lt($cFin))) {
                $slots->push($horaStr);
            }

            $actual->addMinutes($intervalo);
        }

        return $slots;
    }

    private function barberoEstaLibre(
        ?int $barberoId,
        int $barberiaId,
        string $fecha,
        string $horaInicio,
        string $horaFin
    ): bool {
        $query = Cita::where('barberia_id', $barberiaId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            // Detectar solapamiento: la cita existente toca el nuevo slot
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($q2) use ($horaInicio, $horaFin) {
                    // Cita existente empieza dentro del nuevo slot
                    $q2->where('hora_inicio', '>=', $horaInicio)
                       ->where('hora_inicio', '<',  $horaFin);
                })->orWhere(function ($q2) use ($horaInicio, $horaFin) {
                    // Cita existente termina dentro del nuevo slot
                    $q2->where('hora_fin', '>',  $horaInicio)
                       ->where('hora_fin', '<=', $horaFin);
                })->orWhere(function ($q2) use ($horaInicio, $horaFin) {
                    // Cita existente envuelve completamente el nuevo slot
                    $q2->where('hora_inicio', '<=', $horaInicio)
                       ->where('hora_fin',    '>=', $horaFin);
                });
            });

        // Si barbero_id es null (barbería sin barberos configurados)
        // verificamos por barbería completa
        if ($barberoId !== null) {
            $query->where('barbero_id', $barberoId);
        }

        return $query->doesntExist();
    }

    private function formatoAmPm(string $hora): string
    {
        return Carbon::createFromFormat('H:i', $hora)->format('g:i A');
    }
}