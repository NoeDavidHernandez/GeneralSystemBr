<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueoHorario extends Model
{
    protected $table = 'bloqueos_horario';

    protected $fillable = [
        'fecha',
        'hora_inicio',
        'hora_fin',
        'todo_el_dia',
        'motivo',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'todo_el_dia' => 'boolean',
    ];

    /**
     * Verifica si una fecha+hora específica está bloqueada.
     * Usado por DisponibilidadService para filtrar slots.
     */
    public static function estaBloquead(string $fecha, string $hora): bool
    {
        return static::where('fecha', $fecha)
            ->where(function ($q) use ($hora) {
                // Bloqueo de día completo
                $q->where('todo_el_dia', true)
                  // O bloqueo de rango que incluye esta hora
                  ->orWhere(function ($q2) use ($hora) {
                      $q2->where('hora_inicio', '<=', $hora)
                         ->where('hora_fin', '>', $hora);
                  });
            })
            ->exists();
    }
}
