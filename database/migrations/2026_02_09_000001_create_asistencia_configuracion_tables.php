<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsistenciaConfiguracionTables extends Migration
{
    public function up()
    {
        // Configuración de horarios y atrasos por categoría
        Schema::create('asistencia_configuracion', function (Blueprint $table) {
            $table->id('config_id');
            $table->string('config_codigo', 14)->unique();
            $table->string('config_categoria', 50); // Primaria, Secundaria, etc.
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->time('tolerancia_atraso'); // Minutos de tolerancia
            $table->time('hora_atraso_desde'); // Desde qué hora se considera atraso
            $table->time('hora_atraso_hasta'); // Hasta qué hora se considera atraso
            $table->tinyInteger('config_estado')->default(1);
            $table->timestamp('config_fecha')->useCurrent();
        });

        // Registro de atrasos
        Schema::create('asistencia_atrasos', function (Blueprint $table) {
            $table->id('atraso_id');
            $table->string('atraso_codigo', 14)->unique();
            $table->string('estud_codigo', 14);
            $table->date('atraso_fecha');
            $table->time('atraso_hora');
            $table->integer('minutos_atraso');
            $table->string('atraso_observacion', 200)->nullable();
            $table->timestamp('atraso_fecha_registro')->useCurrent();
            
            $table->foreign('estud_codigo')->references('est_codigo')->on('colegio_estudiantes')->onDelete('cascade');
        });

        // Permisos estudiantiles
        Schema::create('asistencia_permisos', function (Blueprint $table) {
            $table->id('permiso_id');
            $table->string('permiso_codigo', 14)->unique();
            $table->string('estud_codigo', 14);
            $table->date('permiso_fecha_inicio');
            $table->date('permiso_fecha_fin');
            $table->string('permiso_motivo', 200);
            $table->text('permiso_observacion')->nullable();
            $table->string('permiso_documento', 100)->nullable(); // Archivo adjunto
            $table->tinyInteger('permiso_estado')->default(1); // 1=Aprobado, 0=Rechazado, 2=Pendiente
            $table->string('permiso_aprobado_por', 14)->nullable();
            $table->timestamp('permiso_fecha_registro')->useCurrent();
            
            $table->foreign('estud_codigo')->references('est_codigo')->on('colegio_estudiantes')->onDelete('cascade');
        });

        // Fechas festivas y especiales
        Schema::create('asistencia_fechas_festivas', function (Blueprint $table) {
            $table->id('festivo_id');
            $table->string('festivo_codigo', 14)->unique();
            $table->date('festivo_fecha');
            $table->string('festivo_nombre', 100);
            $table->string('festivo_descripcion', 200)->nullable();
            $table->time('festivo_hora_entrada')->nullable();
            $table->time('festivo_hora_salida')->nullable();
            $table->tinyInteger('festivo_tipo')->default(1); // 1=Feriado (sin clases), 2=Horario especial
            $table->tinyInteger('festivo_estado')->default(1);
            $table->timestamp('festivo_fecha_registro')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asistencia_fechas_festivas');
        Schema::dropIfExists('asistencia_permisos');
        Schema::dropIfExists('asistencia_atrasos');
        Schema::dropIfExists('asistencia_configuracion');
    }
}
