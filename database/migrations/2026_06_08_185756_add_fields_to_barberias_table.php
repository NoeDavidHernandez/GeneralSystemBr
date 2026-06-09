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
            $table->string('email', 100)->nullable()->after('telefono');
            $table->string('rif', 50)->nullable()->after('email'); // RIF, NIT, RUT, etc.
            $table->text('descripcion')->nullable()->after('rif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn(['email', 'rif', 'descripcion']);
        });
    }
};
