<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsistenciaConfigSeeder extends Seeder
{
    public function run()
    {
        // Configuración de horarios
        DB::table('asistencia_configuracion')->insert([
            [
                'config_codigo' => 'CONF001',
                'config_categoria' => 'Primaria',
                'hora_entrada' => '08:00:00',
                'hora_salida' => '12:30:00',
                'tolerancia_atraso' => '00:15:00',
                'hora_atraso_desde' => '08:15:00',
                'hora_atraso_hasta' => '09:00:00',
                'config_estado' => 1
            ],
            [
                'config_codigo' => 'CONF002',
                'config_categoria' => 'Secundaria',
                'hora_entrada' => '07:30:00',
                'hora_salida' => '13:00:00',
                'tolerancia_atraso' => '00:10:00',
                'hora_atraso_desde' => '07:40:00',
                'hora_atraso_hasta' => '08:30:00',
                'config_estado' => 1
            ]
        ]);

        // Fechas festivas de ejemplo
        DB::table('asistencia_fechas_festivas')->insert([
            [
                'festivo_codigo' => 'FEST001',
                'festivo_fecha' => '2026-01-01',
                'festivo_nombre' => 'Año Nuevo',
                'festivo_descripcion' => 'Feriado nacional',
                'festivo_tipo' => 1,
                'festivo_estado' => 1
            ],
            [
                'festivo_codigo' => 'FEST002',
                'festivo_fecha' => '2026-08-06',
                'festivo_nombre' => 'Día de la Patria',
                'festivo_descripcion' => 'Independencia de Bolivia',
                'festivo_tipo' => 1,
                'festivo_estado' => 1
            ],
            [
                'festivo_codigo' => 'FEST003',
                'festivo_fecha' => '2026-12-24',
                'festivo_nombre' => 'Nochebuena',
                'festivo_descripcion' => 'Horario especial',
                'festivo_hora_entrada' => '08:00:00',
                'festivo_hora_salida' => '11:00:00',
                'festivo_tipo' => 2,
                'festivo_estado' => 1
            ]
        ]);
    }
}
