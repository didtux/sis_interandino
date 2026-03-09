<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchivoAdjuntoToPermisos extends Migration
{
    public function up()
    {
        Schema::table('asistencia_permisos', function (Blueprint $table) {
            $table->string('permiso_archivo', 255)->nullable()->after('permiso_observacion');
        });
    }

    public function down()
    {
        Schema::table('asistencia_permisos', function (Blueprint $table) {
            $table->dropColumn('permiso_archivo');
        });
    }
}
