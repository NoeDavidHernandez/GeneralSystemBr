<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear tabla pivote
        Schema::create('cita_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Migrar los datos existentes (opcional pero buena práctica)
        DB::statement('INSERT INTO cita_servicio (cita_id, servicio_id, created_at, updated_at) SELECT id, servicio_id, created_at, updated_at FROM citas WHERE servicio_id IS NOT NULL');

        // 3. Eliminar la llave foránea y la columna de la tabla citas
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropColumn('servicio_id');
        });
    }

    public function down(): void
    {
        // 1. Restaurar columna en citas
        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
        });

        // 2. Migrar datos de vuelta (tomando el primer servicio de cada cita)
        DB::statement('UPDATE citas c SET servicio_id = (SELECT servicio_id FROM cita_servicio cs WHERE cs.cita_id = c.id LIMIT 1)');

        // 3. Eliminar tabla pivote
        Schema::dropIfExists('cita_servicio');
    }
};
