<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->string('categoria', 60);            // corte, facial, diseño, alisado, etc.
            $table->string('nombre', 100);
            $table->decimal('precio', 8, 2)->nullable(); // null = precio variable o sin definir
            $table->tinyInteger('duracion_min')->default(30);
            $table->boolean('precio_variable')->default(false); // alisado permanente, semiondulación
            $table->boolean('precio_consultar')->default(false); // greca, colorimetría, maquillaje
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};