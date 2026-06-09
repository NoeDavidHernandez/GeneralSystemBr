<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoFinanciero extends Model
{
    protected $fillable = [
        'barberia_id',
        'cita_id',
        'tipo',
        'concepto',
        'monto',
        'metodo_pago',
        'persona',
        'fecha',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
