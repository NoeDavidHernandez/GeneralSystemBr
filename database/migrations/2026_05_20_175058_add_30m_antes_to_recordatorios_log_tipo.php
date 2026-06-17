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
        // Se movió la adición de '30m_antes' a la migración original para compatibilidad con PostgreSQL.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No operation
    }
};
