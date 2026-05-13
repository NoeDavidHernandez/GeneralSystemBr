<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── barberias ────────────────────────────────────────────────────
        Schema::create('barberias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 200)->nullable();
            $table->string('telefono', 20)->nullable();

            // Datos de Meta Cloud API — únicos por barbería
            $table->string('whatsapp_phone_id', 60)->unique();   // ID del número en Meta
            $table->text('whatsapp_token');                       // Token de acceso (largo)
            $table->string('whatsapp_admin_numero', 20)->nullable(); // Número del dueño para alertas

            // Horario como JSON para que cada barbería tenga el suyo
            // Ejemplo: {"apertura":"11:00","cierre":"19:00","dias_cerrado":[2],"comida_inicio":"16:00","comida_fin":"17:00"}
            $table->json('horario_json')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ─── barberos ─────────────────────────────────────────────────────
        // Una barbería puede tener 1 o varios barberos.
        // Para Malva Barber (1 sola persona) se crea un registro con el nombre del dueño.
        Schema::create('barberos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')
                ->constrained('barberias')
                ->cascadeOnDelete();

            $table->string('nombre', 100);

            // Color en Google Calendar (hex) — para distinguir citas por barbero
            $table->string('color_calendario', 10)->default('#6B7FD4');

            // Si el barbero tiene horario diferente al de la barbería
            // null = usa el horario general de la barbería
            $table->json('horario_propio_json')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('barberia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barberos');
        Schema::dropIfExists('barberias');
    }
};