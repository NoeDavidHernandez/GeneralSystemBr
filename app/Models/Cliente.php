<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'whatsapp_id',
        'total_visitas',
        'bloqueado',
        'notas',
    ];

    protected $casts = [
        'bloqueado'     => 'boolean',
        'total_visitas' => 'integer',
    ];


    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function convEstado(): HasOne
    {
        return $this->hasOne(ConvEstado::class, 'telefono', 'telefono');
    }


    /**
     * Busca o crea un cliente por su número de teléfono.
     * Se llama en cada mensaje entrante del bot.
     */
    public static function firstOrCreateByTelefono(string $telefono, string $nombre = ''): static
    {
        return static::firstOrCreate(
            ['telefono' => $telefono],
            ['nombre'   => $nombre ?: 'Cliente']
        );
    }

    public function esFrecuente(): bool
    {
        return $this->total_visitas >= 5;
    }
}