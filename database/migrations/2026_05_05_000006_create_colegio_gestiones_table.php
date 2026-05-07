<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateColegioGestionesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('colegio_gestiones')) {
            Schema::create('colegio_gestiones', function (Blueprint $table) {
                $table->increments('ges_id');
                $table->string('ges_anio', 10);
                $table->string('ges_nombre', 80);
                $table->string('ges_abreviado', 20)->nullable();
                $table->tinyInteger('ges_estado')->default(0);
                $table->dateTime('ges_fecha')->useCurrent();

                $table->unique('ges_anio');
            });

            $anioActual = (int) date('Y');
            DB::table('colegio_gestiones')->insert([
                ['ges_anio' => '2024', 'ges_nombre' => 'GESTIÓN 2024', 'ges_abreviado' => '2024', 'ges_estado' => 0],
                ['ges_anio' => '2025', 'ges_nombre' => 'GESTIÓN 2025', 'ges_abreviado' => '2025', 'ges_estado' => 0],
                ['ges_anio' => '2026', 'ges_nombre' => 'GESTIÓN 2026', 'ges_abreviado' => '2026', 'ges_estado' => $anioActual === 2026 ? 1 : 0],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('colegio_gestiones');
    }
}
