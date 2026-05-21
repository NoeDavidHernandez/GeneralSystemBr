<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Cita extends Model
{
    protected $fillable = [
        'barberia_id',
        'cliente_id',
        'barbero_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'confirmada_at',
        'precio_cobrado',
        'google_event_id',
        'notas',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'confirmada_at' => 'datetime',
        'precio_cobrado'=> 'decimal:2',
    ];

    // Estados
    const ESTADO_PENDIENTE   = 'pendiente';
    const ESTADO_CONFIRMADA  = 'confirmada';
    const ESTADO_CANCELADA   = 'cancelada';
    const ESTADO_COMPLETADA  = 'completada';
    const ESTADO_NO_ASISTIO  = 'no_asistio';

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'cita_servicio');
    }

    public function barbero(): BelongsTo
    {
        return $this->belongsTo(Barbero::class);
    }

    public function recordatorios(): HasMany
    {
        return $this->hasMany(RecordatorioLog::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeDelDia($query, string $fecha = null)
    {
        return $query->whereDate('fecha', $fecha ?? today());
    }

    public function scopePendientesConfirmacion($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    public function duracionTotal(): int
    {
        return $this->servicios->sum('duracion_min');
    }

    public function precioTotalTexto(): string
    {
        $total = $this->servicios->sum('precio');
        if ($total == 0 && $this->servicios->where('precio_variable', true)->count() > 0) {
            return 'Precio variable';
        }
        return '$' . number_format($total, 2);
    }

    public function nombresServicios(): string
    {
        return $this->servicios->pluck('nombre')->join(', ');
    }

    /**
     * Confirma la cita (el cliente respondió "sí" al recordatorio).
     */
    public function confirmar(): void
    {
        $this->update([
            'estado'        => self::ESTADO_CONFIRMADA,
            'confirmada_at' => now(),
        ]);
    }

    /**
     * Cancela la cita y registra el motivo en notas.
     */
    public function cancelar(string $motivo = ''): void
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'notas'  => $motivo ?: $this->notas,
        ]);
    }

    /**
     * Texto resumen para mostrar en WhatsApp.
     */
    public function resumenWhatsApp(): string
    {
        $fecha   = Carbon::parse($this->fecha)->locale('es')->isoFormat('dddd D [de] MMMM');
        $hora    = Carbon::parse($this->hora_inicio)->format('g:i A');

        return "📅 *{$this->nombresServicios()}*\n"
             . "📆 {$fecha} a las {$hora}\n"
             . "💰 {$this->precioTotalTexto()}";
    }

    /**
     * Verifica si la cita puede cancelarse aún (mínimo 2 hrs de anticipación).
     */
    public function puedeCancelarse(): bool
    {
        $inicioCita = Carbon::parse("{$this->fecha} {$this->hora_inicio}");
        return now()->diffInMinutes($inicioCita, false) >= 120;
    }

    /**
     * ¿El cliente llegó tarde? (más de 15 min después de la hora de inicio).
     */
    public function clienteLlegoTarde(): bool
    {
        $inicioCita = Carbon::parse("{$this->fecha} {$this->hora_inicio}");
        return now()->diffInMinutes($inicioCita, false) < -15;
    }
}