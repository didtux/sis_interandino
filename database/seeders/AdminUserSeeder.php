<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('rol_usuarios')->insert([
            'us_codigo' => 'US001',
            'rol_id' => 1,
            'us_ci' => '12345678',
            'us_nombres' => 'Admin',
            'us_apellidos' => 'Sistema',
            'us_user' => 'admin',
            'us_pass' => Hash::make('admin123'),
            'us_visible' => 1
        ]);
    }
}
