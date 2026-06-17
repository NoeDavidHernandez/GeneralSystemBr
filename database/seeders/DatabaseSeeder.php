<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Usuario Super Administrador Global ───────────────────────────
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@sistema.com',
            'password'          => \Illuminate\Support\Facades\Hash::make('superadmin123'),
            'barberia_id'       => null, // No pertenece a una barbería específica
            'is_superadmin'     => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}