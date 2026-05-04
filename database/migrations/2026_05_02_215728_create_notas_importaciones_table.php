<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotasImportacionesTable extends Migration
{
    public function up()
    {
        Schema::create('notas_importaciones', function (Blueprint $table) {
            $table->increments('import_id');
            $table->integer('curmatdoc_id');
            $table->integer('periodo_id');
            $table->enum('import_tipo', ['notas', 'asistencia', 'ambos']);
            $table->json('import_data'); // datos parseados del excel
            $table->json('import_errores')->nullable(); // errores de validación
            $table->json('import_resumen')->nullable(); // resumen: matcheados, no encontrados, etc
            $table->tinyInteger('import_estado')->default(0); // 0=pendiente, 1=confirmado, 2=cancelado
            $table->integer('import_usuario_id');
            $table->string('import_archivo', 255)->nullable();
            $table->datetime('import_fecha')->nullable();
            $table->datetime('import_fecha_confirmacion')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notas_importaciones');
    }
}
