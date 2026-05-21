<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ConvEstado extends Model
{
    protected $table = 'conv_estado';

    protected $fillable = [
        'telefono',
        'paso',
        'datos_temp',
        'modo_asesor',
        'expires_at',
    ];

    protected $casts = [
        'datos_temp'  => 'array',
        'modo_asesor' => 'boolean',
        'expires_at'  => 'datetime',
    ];

    // ─── Pasos del flujo del bot ──────────────────────────────────────────
    // Se definen como constantes para evitar strings sueltos en el código
    const PASO_INICIO                    = 'inicio';
    const PASO_ESPERANDO_OPCION_MENU     = 'esperando_opcion_menu';
    const PASO_ESPERANDO_NOMBRE          = 'esperando_nombre';
    const PASO_ESPERANDO_SERVICIO        = 'esperando_servicio';
    const PASO_AGREGAR_OTRO_SERVICIO     = 'agregar_otro_servicio';
    const PASO_ESPERANDO_BARBERO         = 'esperando_barbero';
    const PASO_ESPERANDO_FECHA           = 'esperando_fecha';
    const PASO_ESPERANDO_HORA            = 'esperando_hora';
    const PASO_CONFIRMANDO_CITA          = 'confirmando_cita';
    const PASO_CONF_RECORDATORIO         = 'esperando_confirmacion_recordatorio';
    const PASO_ESPERANDO_CALIFICACION    = 'esperando_calificacion';
    const PASO_MODO_ASESOR               = 'modo_asesor';
    const PASO_FUERA_DE_HORARIO          = 'fuera_de_horario';

    // ─── Helpers ─────────────────────────────────────────────────────────

    /**
     * Obtiene o crea el estado de conversación para un número.
     * Renueva la expiración en cada llamada (sesión activa = 30 min).
     */
    public static function obtenerOCrear(string $telefono): static
    {
        $estado = static::firstOrCreate(
            ['telefono' => $telefono],
            [
                'paso'       => self::PASO_INICIO,
                'expires_at' => now()->addMinutes(30),
            ]
        );

        // Renovar expiración en cada mensaje
        $estado->update(['expires_at' => now()->addMinutes(30)]);

        return $estado;
    }

    /**
     * Avanza al siguiente paso y guarda datos temporales.
     */
    public function avanzar(string $paso, array $datos = []): void
    {
        $datosMerged = array_merge($this->datos_temp ?? [], $datos);

        $this->update([
            'paso'       => $paso,
            'datos_temp' => $datosMerged,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Guarda un dato temporal sin cambiar el paso.
     */
    public function guardarDato(string $clave, mixed $valor): void
    {
        $datos = $this->datos_temp ?? [];
        $datos[$clave] = $valor;
        $this->update(['datos_temp' => $datos]);
    }

    /**
     * Reinicia completamente la sesión (cita terminada, error, etc).
     */
    public function reiniciar(): void
    {
        $this->update([
            'paso'        => self::PASO_INICIO,
            'datos_temp'  => null,
            'modo_asesor' => false,
            'expires_at'  => now()->addMinutes(30),
        ]);
    }

    /**
     * Activa el modo asesor (bot se silencia, humano toma el control).
     */
    public function activarModoAsesor(): void
    {
        $this->update(['modo_asesor' => true, 'paso' => self::PASO_MODO_ASESOR]);
    }

    /**
     * El asesor humano devuelve el control al bot.
     */
    public function desactivarModoAsesor(): void
    {
        $this->update(['modo_asesor' => false]);
        $this->reiniciar();
    }

    /**
     * ¿La sesión ya expiró?
     */
    public function estaExpirada(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Limpia sesiones expiradas — llamado por el Scheduler.
     */
    public static function limpiarExpiradas(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}