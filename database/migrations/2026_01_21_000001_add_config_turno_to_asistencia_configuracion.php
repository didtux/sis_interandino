<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigTurnoToAsistenciaConfiguracion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asistencia_configuracion', function (Blueprint $table) {
            $table->string('config_turno', 20)->nullable()->after('config_categoria');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asistencia_configuracion', function (Blueprint $table) {
            $table->dropColumn('config_turno');
        });
    }
}
