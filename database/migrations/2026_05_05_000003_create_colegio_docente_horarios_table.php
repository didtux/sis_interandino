<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColegioDocenteHorariosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('colegio_docente_horarios')) {
            Schema::create('colegio_docente_horarios', function (Blueprint $table) {
                $table->increments('horario_id');
                $table->string('doc_codigo', 14);
                $table->string('horario_turno', 20);
                $table->string('horario_dia', 15);
                $table->time('horario_inicio');
                $table->time('horario_fin');
                $table->tinyInteger('horario_estado')->default(1);
                $table->dateTime('horario_fecha_registro')->useCurrent();

                $table->index('doc_codigo', 'idx_dh_doc');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('colegio_docente_horarios');
    }
}
