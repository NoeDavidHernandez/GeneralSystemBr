<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En Postgres, cambiar de boolean a integer requiere el USING
        DB::statement('ALTER TABLE users ALTER COLUMN is_superadmin TYPE smallint USING (is_superadmin::integer)');
        DB::statement('ALTER TABLE users ALTER COLUMN is_superadmin SET DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN is_superadmin TYPE boolean USING (is_superadmin::boolean)');
        DB::statement('ALTER TABLE users ALTER COLUMN is_superadmin SET DEFAULT false');
    }
};
