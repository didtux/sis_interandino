<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfiguracionCursosTable extends Migration
{
    public function up()
    {
        Schema::create('asistencia_configuracion_cursos', function (Blueprint $table) {
            $table->id('config_curso_id');
            $table->unsignedBigInteger('config_id');
            $table->string('cur_codigo', 20);
            $table->timestamps();
            
            $table->foreign('config_id')->references('config_id')->on('asistencia_configuracion')->onDelete('cascade');
            $table->foreign('cur_codigo')->references('cur_codigo')->on('colegio_cursos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asistencia_configuracion_cursos');
    }
}
