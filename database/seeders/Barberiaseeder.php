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
    }
}