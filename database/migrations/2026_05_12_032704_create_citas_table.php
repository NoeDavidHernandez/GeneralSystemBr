<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('servicio_id')
                ->constrained('servicios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');           // calculado: hora_inicio + servicio.duracion_min

            $table->enum('estado', [
                'pendiente',     // agendada, esperando confirmación del cliente
                'confirmada',    // cliente confirmó asistencia
                'cancelada',     // cancelada por cliente o admin
                'completada',    // servicio realizado
                'no_asistio',    // no llegó ni canceló
            ])->default('pendiente');

            // Cuándo confirmó el cliente (para auditoría)
            $table->timestamp('confirmada_at')->nullable();

            // Para servicios de precio variable (alisado, semiondulación)
            $table->decimal('precio_cobrado', 8, 2)->nullable();

            // ID del evento creado en Google Calendar
            $table->string('google_event_id', 100)->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices para las consultas más frecuentes
            $table->index(['fecha', 'hora_inicio']);
            $table->index(['fecha', 'estado']);
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};