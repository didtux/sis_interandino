<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtrasToColegioDocentes extends Migration
{
    public function up()
    {
        Schema::table('colegio_docentes', function (Blueprint $table) {
            if (!Schema::hasColumn('colegio_docentes', 'doc_telefono')) {
                $table->string('doc_telefono', 20)->nullable()->after('doc_ci');
            }
            if (!Schema::hasColumn('colegio_docentes', 'doc_sexo')) {
                $table->string('doc_sexo', 15)->nullable()->after('doc_telefono');
            }
            if (!Schema::hasColumn('colegio_docentes', 'doc_descripcion')) {
                $table->text('doc_descripcion')->nullable()->after('doc_sexo');
            }
        });
    }

    public function down()
    {
        Schema::table('colegio_docentes', function (Blueprint $table) {
            $cols = ['doc_telefono', 'doc_sexo', 'doc_descripcion'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('colegio_docentes', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
