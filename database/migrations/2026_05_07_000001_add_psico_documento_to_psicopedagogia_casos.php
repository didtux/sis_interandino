<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPsicoDocumentoToPsicopedagogiaCasos extends Migration
{
    public function up()
    {
        Schema::table('psicopedagogia_casos', function (Blueprint $table) {
            if (!Schema::hasColumn('psicopedagogia_casos', 'psico_documento')) {
                $table->string('psico_documento', 255)->nullable()->after('psico_observaciones');
            }
        });
    }

    public function down()
    {
        Schema::table('psicopedagogia_casos', function (Blueprint $table) {
            if (Schema::hasColumn('psicopedagogia_casos', 'psico_documento')) {
                $table->dropColumn('psico_documento');
            }
        });
    }
}
