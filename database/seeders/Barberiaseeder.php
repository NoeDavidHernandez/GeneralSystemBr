<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarberiaSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Dar de alta Malva Barber ─────────────────────────────────────
        $barberiaId = DB::table('barberias')->insertGetId([
            'nombre'                  => 'Estudio Malva Barber',
            'direccion'               => 'Puebla, México', // actualizar con dirección real
            'telefono'                => '2223008628',
            'whatsapp_phone_id'       => 'REEMPLAZAR_CON_PHONE_ID_DE_META',
            'whatsapp_token'          => 'REEMPLAZAR_CON_TOKEN_DE_META',
            'whatsapp_admin_numero'   => '5212223008628', // número del dueño con código país
            'horario_json'            => json_encode([
                'apertura'       => '11:00',
                'cierre'         => '19:00',
                'dias_cerrado'   => [2],      // 2 = martes
                'comida_inicio'  => '16:00',
                'comida_fin'     => '17:00',
            ]),
            'activo'                  => true,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        // ─── Barbero principal (dueña/dueño del estudio) ──────────────────
        // Cuando tengan más personal, agregan más registros aquí
        DB::table('barberos')->insert([
            'barberia_id'       => $barberiaId,
            'nombre'            => 'Malva',          // cambiar al nombre real
            'color_calendario'  => '#B87FC4',        // morado acorde al branding
            'horario_propio_json' => null,            // usa el horario general
            'activo'            => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // ─── Usuario de prueba para acceder al panel ──────────────────────
        DB::table('users')->insert([
            'name'              => 'Admin Malva',
            'email'             => 'admin@malvabarber.com',
            'password'          => \Illuminate\Support\Facades\Hash::make('password123'),
            'barberia_id'       => $barberiaId,
            'is_superadmin'     => false,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // ─── Usuario Super Administrador Global ───────────────────────────
        DB::table('users')->insert([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@sistema.com',
            'password'          => \Illuminate\Support\Facades\Hash::make('superadmin123'),
            'barberia_id'       => null, // No pertenece a una barbería específica
            'is_superadmin'     => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // ─── Dar de alta Barbería VIP (Inquilino 2) ───────────────────────
        $barberiaVipId = DB::table('barberias')->insertGetId([
            'nombre'                  => 'Barbería VIP',
            'direccion'               => 'CDMX, México',
            'telefono'                => '5551234567',
            'whatsapp_phone_id'       => 'PHONE_ID_VIP',
            'whatsapp_token'          => 'TOKEN_VIP',
            'whatsapp_admin_numero'   => '5215551234567',
            'horario_json'            => json_encode([
                'apertura'       => '10:00',
                'cierre'         => '20:00',
                'dias_cerrado'   => [0],      // 0 = domingo
                'comida_inicio'  => '14:00',
                'comida_fin'     => '15:00',
            ]),
            'activo'                  => true,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        DB::table('barberos')->insert([
            'barberia_id'       => $barberiaVipId,
            'nombre'            => 'Alex VIP',
            'color_calendario'  => '#EAB308', // Amarillo
            'horario_propio_json' => null,
            'activo'            => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('users')->insert([
            'name'              => 'Admin VIP',
            'email'             => 'admin@barberiavip.com',
            'password'          => \Illuminate\Support\Facades\Hash::make('password123'),
            'barberia_id'       => $barberiaVipId,
            'is_superadmin'     => false,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}