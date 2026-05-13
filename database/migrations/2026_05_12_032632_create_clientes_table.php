<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('telefono', 20)->unique();    // número sin el +52, ej: 5212223008628
            $table->string('whatsapp_id', 40)->nullable(); // id que manda Meta en el webhook
            $table->unsignedInteger('total_visitas')->default(0);
            $table->boolean('bloqueado')->default(false);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('telefono');
            $table->index('whatsapp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};