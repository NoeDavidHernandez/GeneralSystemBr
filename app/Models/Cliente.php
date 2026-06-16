<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    protected $fillable = [
        'barberia_id',
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

    public function barberia()
    {
        return $this->belongsTo(Barberia::class);
    }


    /**
     * Busca o crea un cliente por su número de teléfono.
     * Se llama en cada mensaje entrante del bot.
     */
    public static function firstOrCreateByTelefono(string $telefono, string $nombre = '', Barberia $barberia = null): static
    {
        if (!$barberia) {
            // Fallback just in case
            return static::firstOrCreate(
                ['telefono' => $telefono],
                ['nombre'   => $nombre ?: 'Cliente']
            );
        }

        return static::firstOrCreate(
            ['telefono' => $telefono, 'barberia_id' => $barberia->id],
            ['nombre'   => $nombre ?: 'Cliente']
        );
    }

    public function esFrecuente(): bool
    {
        return $this->total_visitas >= 5;
    }
}