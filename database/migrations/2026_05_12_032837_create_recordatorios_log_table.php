<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registra cada mensaje de recordatorio/confirmación para:
        // 1. No enviar duplicados si el job falla y se reintenta
        // 2. Auditoría y debug
        Schema::create('recordatorios_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')
                ->constrained('citas')
                ->cascadeOnDelete(); // si se borra la cita, se borran sus logs

            $table->enum('tipo', [
                '24h_antes',            // recordatorio del día anterior
                '1h_antes',             // recordatorio 1 hora antes
                'solicitud_confirmacion', // "¿confirmas tu cita?"
                'gracias',              // mensaje post-servicio
                'cancelacion',          // aviso de cita cancelada
            ]);

            $table->enum('status', [
                'pendiente',
                'enviado',
                'fallido',
            ])->default('pendiente');

            $table->timestamp('enviado_at')->nullable();
            $table->text('error')->nullable();   // detalle si falló
            $table->timestamps();

            $table->index(['cita_id', 'tipo']);  // evita duplicados en el job
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios_log');
    }
};