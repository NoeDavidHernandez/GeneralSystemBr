<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barbero extends Model
{
    protected $fillable = [
        'barberia_id',
        'nombre',
        'telefono',
        'color_calendario',
        'horario_propio_json',
        'activo',
    ];

    protected $casts = [
        'horario_propio_json' => 'array',
        'activo'              => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'barbero_id');
    }
}
