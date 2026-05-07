<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateColegioNivelesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('colegio_niveles')) {
            Schema::create('colegio_niveles', function (Blueprint $table) {
                $table->increments('niv_id');
                $table->string('niv_nombre', 50);
                $table->string('niv_abreviado', 20)->nullable();
                $table->integer('niv_orden')->default(0);
                $table->tinyInteger('niv_estado')->default(1);
                $table->dateTime('niv_fecha')->useCurrent();
            });

            DB::table('colegio_niveles')->insert([
                ['niv_nombre' => 'INICIAL',    'niv_abreviado' => 'INI', 'niv_orden' => 1, 'niv_estado' => 1],
                ['niv_nombre' => 'PRIMARIA',   'niv_abreviado' => 'PRI', 'niv_orden' => 2, 'niv_estado' => 1],
                ['niv_nombre' => 'SECUNDARIA', 'niv_abreviado' => 'SEC', 'niv_orden' => 3, 'niv_estado' => 1],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('colegio_niveles');
    }
}
