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
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_telefono_unique');
            $table->foreignId('barberia_id')->nullable()->constrained('barberias')->cascadeOnDelete();
            $table->unique(['barberia_id', 'telefono'], 'clientes_barberia_telefono_unique');
        });

        // Asignar los clientes existentes a la primera barbería si la hay
        $primeraBarberia = \App\Models\Barberia::first();
        if ($primeraBarberia) {
            \Illuminate\Support\Facades\DB::table('clientes')->whereNull('barberia_id')->update(['barberia_id' => $primeraBarberia->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_barberia_telefono_unique');
            $table->dropForeign(['barberia_id']);
            $table->dropColumn('barberia_id');
            $table->unique('telefono', 'clientes_telefono_unique');
        });
    }
};
