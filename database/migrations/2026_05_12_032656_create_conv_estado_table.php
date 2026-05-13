<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarda en qué "paso" del flujo está cada número de WhatsApp.
        // Se crea al primer mensaje y se limpia al terminar o expirar.
        Schema::create('conv_estado', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->unique();

            // Pasos posibles del bot:
            // inicio | esperando_nombre | esperando_servicio | esperando_fecha
            // esperando_hora | confirmando_cita | esperando_confirmacion_recordatorio
            // modo_asesor | fuera_de_horario
            $table->string('paso', 50)->default('inicio');

            // Datos que se van acumulando durante la conversación
            // ej: {"nombre": "Juan", "servicio_id": 3, "fecha": "2025-06-10"}
            $table->json('datos_temp')->nullable();

            // true = el usuario pidió hablar con asesor, bot se silencia
            $table->boolean('modo_asesor')->default(false);

            // La sesión expira si no hay actividad en 30 minutos
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('telefono');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conv_estado');
    }
};