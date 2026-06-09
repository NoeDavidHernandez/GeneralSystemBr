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
        Schema::table('barberias', function (Blueprint $table) {
            $table->unsignedBigInteger('referido_por')->nullable()->after('horario_json');
            $table->date('fecha_proximo_pago')->nullable()->after('referido_por');

            $table->foreign('referido_por')->references('id')->on('barberias')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            $table->dropForeign(['referido_por']);
            $table->dropColumn(['referido_por', 'fecha_proximo_pago']);
        });
    }
};
