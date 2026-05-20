<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // El orden importa por las foreign keys
        $this->call([
            BarberiaSeeder::class,  // primero barberias y barberos
            ServiciosSeeder::class, // luego servicios (necesitan barberia_id)
            DatosPruebaSeeder::class, // Citas y clientes aleatorios
        ]);
    }
}