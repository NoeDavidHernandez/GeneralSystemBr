<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoSaaS extends Model
{
    protected $table = 'pago_saas';

    protected $fillable = [
        'barberia_id',
        'monto',
        'fecha_pago',
        'metodo',
        'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function barberia()
    {
        return $this->belongsTo(Barberia::class);
    }
}
