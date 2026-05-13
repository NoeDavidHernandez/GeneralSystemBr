<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El admin puede bloquear horas específicas o días completos.
        // La lógica de disponibilidad siempre revisa esta tabla antes de ofrecer horarios.
        Schema::create('bloqueos_horario', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();     // null si todo_el_dia = true
            $table->time('hora_fin')->nullable();        // null si todo_el_dia = true
            $table->boolean('todo_el_dia')->default(false);
            $table->string('motivo', 100)->nullable();   // "Vacaciones", "Evento", etc.
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_horario');
    }
};