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
            $table->integer('recompensas_acumuladas')->default(0)->after('fecha_proximo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn('recompensas_acumuladas');
        });
    }
};
