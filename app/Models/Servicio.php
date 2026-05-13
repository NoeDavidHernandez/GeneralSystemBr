<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $fillable = [
        'categoria',
        'barberia_id',
        'nombre',
        'precio',
        'duracion_min',
        'precio_variable',
        'precio_consultar',
        'activo',
    ];

    protected $casts = [
        'precio'           => 'decimal:2',
        'precio_variable'  => 'boolean',
        'precio_consultar' => 'boolean',
        'activo'           => 'boolean',
    ];


    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }


    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ─── Helpers para el bot de WhatsApp ─────────────────────────────────

    /**
     * Devuelve el precio formateado para mostrar en WhatsApp.
     * Ej: "$150", "Precio según largo del cabello", "Consultar precio"
     */
    public function precioTexto(): string
    {
        if ($this->precio_consultar) {
            return 'Consultar precio';
        }

        if ($this->precio_variable) {
            return 'Precio según largo del cabello';
        }

        return '$' . number_format($this->precio, 0);
    }

    /**
     * Línea completa para el menú del bot.
     * Ej: "3. Corte dama — $150"
     */
    public function lineaMenu(int $numero): string
    {
        return "{$numero}. {$this->nombre} — {$this->precioTexto()}";
    }


    public static function menuAgrupado(): array
    {
        return static::activo()
            ->orderBy('categoria')
            ->orderBy('id')
            ->get()
            ->groupBy('categoria')
            ->toArray();
    }
}