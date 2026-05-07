<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCurOrdenAndExtrasToColegioCursos extends Migration
{
    public function up()
    {
        Schema::table('colegio_cursos', function (Blueprint $table) {
            if (!Schema::hasColumn('colegio_cursos', 'cur_orden')) {
                $table->integer('cur_orden')->default(0)->after('cur_nombre');
            }
            if (!Schema::hasColumn('colegio_cursos', 'cur_abreviado')) {
                $table->string('cur_abreviado', 30)->nullable()->after('cur_orden');
            }
            if (!Schema::hasColumn('colegio_cursos', 'cur_nivel')) {
                $table->string('cur_nivel', 20)->nullable()->after('cur_abreviado');
            }
            if (!Schema::hasColumn('colegio_cursos', 'cur_cupo')) {
                $table->integer('cur_cupo')->default(0)->after('cur_nivel');
            }
        });

        $mapeo = [
            'PreKinder' => 1,  'Kinder'   => 2,
            '1roPRIM'   => 3,  '2doPRIM'  => 5,  '5500504'  => 6,
            '3roPRIM'   => 7,  'eb67ecb'  => 8,
            '4toPRIM'   => 9,  '5e7c089'  => 10,
            '5toPRIM'   => 11, '6toPRIM'  => 13, '4cb7d92'  => 14,
            '1roSEC'    => 15, 'af2534c'  => 16,
            '2doSEC'    => 17, '556e9b7'  => 18,
            '3roSEC'    => 19, '64b2b3e'  => 20,
            '4toSEC'    => 21, 'ffb2f67'  => 22,
            '5toSEC'    => 23, '6toSEC'   => 25,
        ];

        foreach ($mapeo as $codigo => $orden) {
            DB::table('colegio_cursos')
                ->where('cur_codigo', (string) $codigo)
                ->update(['cur_orden' => $orden]);
        }

        DB::statement("UPDATE colegio_cursos SET cur_nivel = CASE
            WHEN cur_codigo IN ('PreKinder','Kinder') THEN 'INICIAL'
            WHEN cur_nombre LIKE '%PRIM%' OR cur_nombre LIKE '%Primaria%' THEN 'PRIMARIA'
            WHEN cur_nombre LIKE '%SEC%' OR cur_nombre LIKE '%Secundaria%' THEN 'SECUNDARIA'
            ELSE cur_nivel END
            WHERE cur_nivel IS NULL OR cur_nivel = ''");
    }

    public function down()
    {
        Schema::table('colegio_cursos', function (Blueprint $table) {
            $cols = ['cur_orden','cur_abreviado','cur_nivel','cur_cupo'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('colegio_cursos', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
