<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder de datos de prueba para el panel de administración.
 * Genera clientes y citas ficticias de los últimos 3 meses
 * para que las gráficas se vean con datos realistas.
 *
 * Ejecutar: php artisan db:seed --class=DatosPruebaSeeder
 */
class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧪 Generando datos de prueba...');

        // ─── Clientes ficticios ──────────────────────────────────────────
        $nombres = [
            'Carlos López', 'Ana Martínez', 'Diego Hernández', 'María García',
            'José Rodríguez', 'Laura Sánchez', 'Pedro Ramírez', 'Sofía Torres',
            'Luis Morales', 'Gabriela Flores', 'Miguel Díaz', 'Valentina Cruz',
            'Roberto Vargas', 'Fernanda Reyes', 'Andrés Mendoza', 'Camila Rosas',
            'Ricardo Peña', 'Daniela Castillo', 'Fernando Ortiz', 'Isabella Navarro',
        ];

        $clientes = [];
        foreach ($nombres as $i => $nombre) {
            $clientes[] = Cliente::create([
                'nombre'        => $nombre,
                'telefono'      => '521222' . str_pad(1000000 + $i, 7, '0', STR_PAD_LEFT),
                'total_visitas' => rand(1, 15),
                'bloqueado'     => false,
                'created_at'    => now()->subDays(rand(1, 90)),
            ]);
        }

        $this->command->info('   ✅ ' . count($clientes) . ' clientes creados');

        // ─── Obtener servicios disponibles ────────────────────────────────
        $servicios = Servicio::where('activo', true)->get();

        if ($servicios->isEmpty()) {
            $this->command->error('   ❌ No hay servicios. Ejecuta primero: php artisan db:seed --class=ServiciosSeeder');
            return;
        }

        // ─── Generar citas de los últimos 90 días ────────────────────────
        $estados = ['completada', 'completada', 'completada', 'completada',
                     'cancelada', 'no_asistio', 'confirmada', 'pendiente'];

        $citasCreadas = 0;

        for ($dia = 90; $dia >= 0; $dia--) {
            $fecha = now()->subDays($dia);

            // Saltar martes (día cerrado)
            if ($fecha->dayOfWeek === 2) continue;

            // Generar entre 2 y 8 citas por día
            $cantidadCitas = rand(2, 8);
            $horasUsadas   = [];

            for ($c = 0; $c < $cantidadCitas; $c++) {
                // Elegir hora aleatoria dentro del horario (11:00-19:00, sin 16:00)
                $horasDisponibles = ['11:00','11:30','12:00','12:30','13:00','13:30',
                                      '14:00','14:30','15:00','15:30','17:00','17:30',
                                      '18:00','18:30'];

                // Filtrar horas ya usadas
                $horasLibres = array_diff($horasDisponibles, $horasUsadas);
                if (empty($horasLibres)) break;

                $hora = $horasLibres[array_rand($horasLibres)];
                $horasUsadas[] = $hora;

                $cliente  = $clientes[array_rand($clientes)];
                $servicio = $servicios->random();

                // Para citas futuras, solo pendiente/confirmada
                $estado = $fecha->isFuture()
                    ? (rand(0, 1) ? 'pendiente' : 'confirmada')
                    : $estados[array_rand($estados)];

                // Precio cobrado solo para completadas
                $precioCobrado = null;
                if ($estado === 'completada' && $servicio->precio) {
                    $precioCobrado = $servicio->precio;
                }

                $horaFin = Carbon::createFromFormat('H:i', $hora)
                    ->addMinutes($servicio->duracion_min)
                    ->format('H:i');

                Cita::create([
                    'barberia_id'    => $servicio->barberia_id,
                    'cliente_id'     => $cliente->id,
                    'servicio_id'    => $servicio->id,
                    'fecha'          => $fecha->toDateString(),
                    'hora_inicio'    => $hora,
                    'hora_fin'       => $horaFin,
                    'estado'         => $estado,
                    'precio_cobrado' => $precioCobrado,
                    'confirmada_at'  => $estado === 'confirmada' ? $fecha : null,
                    'notas'          => $estado === 'cancelada' ? 'Canceló por motivos personales' : null,
                ]);

                $citasCreadas++;
            }
        }

        $this->command->info("   ✅ {$citasCreadas} citas generadas (últimos 90 días)");
        $this->command->info('');
        $this->command->info('🎉 ¡Datos de prueba listos! Abre /admin para ver las gráficas.');
    }
}
