<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSistemaConfiguracionTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sistema_configuracion')) {
            Schema::create('sistema_configuracion', function (Blueprint $table) {
                $table->increments('config_id');
                $table->string('config_logo', 255)->nullable();
                $table->string('config_denominacion', 200)->nullable();
                $table->string('config_nombre_ue', 200)->nullable();
                $table->string('config_direccion', 255)->nullable();
                $table->string('config_telefono', 50)->nullable();
                $table->string('config_ciudad', 100)->nullable();
                $table->string('config_email', 100)->nullable();
                $table->dateTime('config_fecha')->useCurrent();
            });

            DB::table('sistema_configuracion')->insert([
                'config_denominacion' => 'UNIDAD EDUCATIVA',
                'config_nombre_ue'    => 'INTERANDINO BOLIVIANO',
                'config_direccion'    => '',
                'config_telefono'     => '',
                'config_ciudad'       => '',
                'config_email'        => '',
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('sistema_configuracion');
    }
}
