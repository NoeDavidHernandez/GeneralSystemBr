<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordatorioLog extends Model
{
    protected $table = 'recordatorios_log';

    protected $fillable = [
        'cita_id',
        'tipo',
        'status',
        'enviado_at',
        'error',
    ];

    protected $casts = [
        'enviado_at' => 'datetime',
    ];

    const TIPO_24H         = '24h_antes';
    const TIPO_1H          = '1h_antes';
    const TIPO_CONFIRMACION = 'solicitud_confirmacion';
    const TIPO_GRACIAS     = 'gracias';
    const TIPO_CANCELACION = 'cancelacion';

    const STATUS_PENDIENTE = 'pendiente';
    const STATUS_ENVIADO   = 'enviado';
    const STATUS_FALLIDO   = 'fallido';

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    /**
     * ¿Ya se envió este tipo de recordatorio para esta cita?
     * Previene duplicados si el job se reintenta.
     */
    public static function yaEnviado(int $citaId, string $tipo): bool
    {
        return static::where('cita_id', $citaId)
            ->where('tipo', $tipo)
            ->where('status', self::STATUS_ENVIADO)
            ->exists();
    }

    public function marcarEnviado(): void
    {
        $this->update(['status' => self::STATUS_ENVIADO, 'enviado_at' => now()]);
    }

    public function marcarFallido(string $error): void
    {
        $this->update(['status' => self::STATUS_FALLIDO, 'error' => $error]);
    }
}