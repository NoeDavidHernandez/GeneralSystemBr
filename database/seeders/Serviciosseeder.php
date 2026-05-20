<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        $barberiaId = DB::table('barberias')
            ->where('nombre', 'Estudio Malva Barber')
            ->value('id');

        $servicios = [

            // ─── CORTES DE CABELLO ────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cortes de cabello',
                'nombre'           => 'Corte caballero',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cortes de cabello',
                'nombre'           => 'Corte niño',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cortes de cabello',
                'nombre'           => 'Corte dama',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cortes de cabello',
                'nombre'           => 'Corte a tijera',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── DISEÑOS ──────────────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseños',
                'nombre'           => 'Greca sencilla',
                'precio'           => null,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => true, // Sin precio en el menú
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseños',
                'nombre'           => 'Diseño de ceja con navaja',
                'precio'           => 50.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseños',
                'nombre'           => 'Líneas en ceja',
                'precio'           => 25.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── CUIDADO FACIAL ───────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cuidado facial',
                'nombre'           => 'Mascarilla sencilla',
                'precio'           => 60.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Cuidado facial',
                'nombre'           => 'Exfoliación + tratamiento para manos',
                'precio'           => 100.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── DISEÑO DE CEJA – TÉC HINDÚ ──────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseño de ceja técnica hindú',
                'nombre'           => 'Ceja hindú',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseño de ceja técnica hindú',
                'nombre'           => 'Bigote hindú',
                'precio'           => 100.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseño de ceja técnica hindú',
                'nombre'           => 'Cara completa hindú',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Diseño de ceja técnica hindú',
                'nombre'           => 'Paquete completo hindú',
                'precio'           => 300.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── ALISADOS Y ONDAS ─────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Alisados y ondas',
                'nombre'           => 'Alisado cabello corto',
                'precio'           => 100.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Alisados y ondas',
                'nombre'           => 'Alisado cabello medio',
                'precio'           => 150.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Alisados y ondas',
                'nombre'           => 'Alisado cabello largo',
                'precio'           => 200.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── SEMIONDULACIÓN ───────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Semiondulación',
                'nombre'           => 'Semiondulación caballero',
                'precio'           => 500.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Semiondulación',
                'nombre'           => 'Semiondulación mujer',
                'precio'           => null,
                'duracion_min'     => 30,
                'precio_variable'  => true, // precio según largo
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── SERVICIO VIP ─────────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Servicio VIP',
                'nombre'           => 'Servicio VIP completo',
                'precio'           => 500.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── RITUAL DE BARBA ──────────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Ritual de barba',
                'nombre'           => 'Ritual completo de barba',
                'precio'           => 250.00,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── ALISADO PERMANENTE ───────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Alisado permanente',
                'nombre'           => 'Alisado permanente',
                'precio'           => null,
                'duracion_min'     => 30,
                'precio_variable'  => true, // "precio según el largo del cabello"
                'precio_consultar' => false,
                'activo'           => true,
            ],

            // ─── SERVICIOS ADICIONALES ────────────────────────────────────
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Servicios adicionales',
                'nombre'           => 'Colorimetría',
                'precio'           => null,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => true,
                'activo'           => true,
            ],
            [
                'barberia_id'      => $barberiaId,
                'categoria'        => 'Servicios adicionales',
                'nombre'           => 'Maquillaje',
                'precio'           => null,
                'duracion_min'     => 30,
                'precio_variable'  => false,
                'precio_consultar' => true,
                'activo'           => true,
            ],
        ];

        DB::table('servicios')->insert($servicios);

        // ─── SERVICIOS PARA LA BARBERÍA VIP ───────────────────────────────
        $barberiaVipId = DB::table('barberias')
            ->where('nombre', 'Barbería VIP')
            ->value('id');

        if ($barberiaVipId) {
            $serviciosVip = [
                [
                    'barberia_id'      => $barberiaVipId,
                    'categoria'        => 'Cortes de cabello',
                    'nombre'           => 'Corte VIP',
                    'precio'           => 300.00,
                    'duracion_min'     => 45,
                    'precio_variable'  => false,
                    'precio_consultar' => false,
                    'activo'           => true,
                ],
                [
                    'barberia_id'      => $barberiaVipId,
                    'categoria'        => 'Cuidado facial',
                    'nombre'           => 'Mascarilla VIP de Oro',
                    'precio'           => 150.00,
                    'duracion_min'     => 30,
                    'precio_variable'  => false,
                    'precio_consultar' => false,
                    'activo'           => true,
                ],
                [
                    'barberia_id'      => $barberiaVipId,
                    'categoria'        => 'Ritual de barba',
                    'nombre'           => 'Ritual VIP Barba',
                    'precio'           => 200.00,
                    'duracion_min'     => 30,
                    'precio_variable'  => false,
                    'precio_consultar' => false,
                    'activo'           => true,
                ]
            ];
            DB::table('servicios')->insert($serviciosVip);
        }
    }
}