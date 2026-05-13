<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Barberia extends Model
{
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'whatsapp_phone_id',
        'whatsapp_token',
        'whatsapp_admin_numero',
        'horario_json',
        'activo',
    ];

    protected $casts = [
        'horario_json' => 'array',
        'activo'       => 'boolean',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function barberos(): HasMany
    {
        return $this->hasMany(Barbero::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    // ─── Horario ──────────────────────────────────────────────────────────

    /**
     * Devuelve el horario parseado con valores por defecto.
     */
    public function horarioParseado(): array
    {
        $h = $this->horario_json ?? [];

        return [
            'apertura'      => $h['apertura']      ?? '11:00',
            'cierre'        => $h['cierre']         ?? '19:00',
            'dias_cerrado'  => $h['dias_cerrado']   ?? [2],
            'comida_inicio' => $h['comida_inicio']  ?? '16:00',
            'comida_fin'    => $h['comida_fin']     ?? '17:00',
        ];
    }

    /**
     * ¿Está abierta la barbería en este momento?
     */
    public function estaAbierta(): bool
    {
        $ahora   = now()->setTimezone('America/Mexico_City');
        $horario = $this->horarioParseado();

        // ¿Hoy es día cerrado?
        if (in_array($ahora->dayOfWeek, $horario['dias_cerrado'])) {
            return false;
        }

        $horaActual = $ahora->format('H:i');
        $apertura   = $horario['apertura'];
        $cierre     = $horario['cierre'];

        // ¿Fuera del horario general?
        if ($horaActual < $apertura || $horaActual >= $cierre) {
            return false;
        }

        // ¿En hora de comida?
        $comidaIni = $horario['comida_inicio'];
        $comidaFin = $horario['comida_fin'];

        if ($horaActual >= $comidaIni && $horaActual < $comidaFin) {
            return false;
        }

        return true;
    }
}
