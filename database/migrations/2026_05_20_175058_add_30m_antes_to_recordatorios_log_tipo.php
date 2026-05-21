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
        DB::statement("ALTER TABLE recordatorios_log MODIFY COLUMN tipo ENUM('24h_antes','1h_antes','30m_antes','solicitud_confirmacion','gracias','cancelacion') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE recordatorios_log MODIFY COLUMN tipo ENUM('24h_antes','1h_antes','solicitud_confirmacion','gracias','cancelacion') NOT NULL");
    }
};
