<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega barberia_id y barbero_id a la tabla citas.
 * El BotService y DisponibilidadService necesitan estos campos
 * para identificar a qué barbería/barbero pertenece cada cita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Agregar columnas SIN foreign key primero
            $table->unsignedBigInteger('barberia_id')->nullable()->after('id');
            $table->unsignedBigInteger('barbero_id')->nullable()->after('servicio_id');
        });

        // Asignar la primera barbería a las citas que ya existen
        $primeraBarberiaId = DB::table('barberias')->value('id');
        if ($primeraBarberiaId) {
            DB::table('citas')->whereNull('barberia_id')->update([
                'barberia_id' => $primeraBarberiaId,
            ]);
        }

        // Ahora sí agregar las foreign keys
        Schema::table('citas', function (Blueprint $table) {
            $table->foreign('barberia_id')
                ->references('id')->on('barberias')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('barbero_id')
                ->references('id')->on('barberos')
                ->cascadeOnUpdate()->nullOnDelete();

            $table->index('barberia_id');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['barberia_id']);
            $table->dropForeign(['barbero_id']);
            $table->dropColumn(['barberia_id', 'barbero_id']);
        });
    }
};
