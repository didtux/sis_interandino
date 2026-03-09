<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCursoToConfiguracionAsistencia extends Migration
{
    public function up()
    {
        Schema::table('asistencia_configuracion', function (Blueprint $table) {
            $table->string('cur_codigo', 20)->nullable()->after('config_turno');
            $table->index('cur_codigo');
        });
    }

    public function down()
    {
        Schema::table('asistencia_configuracion', function (Blueprint $table) {
            $table->dropIndex(['cur_codigo']);
            $table->dropColumn('cur_codigo');
        });
    }
}
