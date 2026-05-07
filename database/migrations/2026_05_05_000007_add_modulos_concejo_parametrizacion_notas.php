<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddModulosConcejoParametrizacionNotas extends Migration
{
    public function up()
    {
        $now = now();

        $insertIfMissing = function (array $row) {
            if (!DB::table('rol_modulos')->where('mod_slug', $row['mod_slug'])->exists()) {
                DB::table('rol_modulos')->insert($row);
            }
        };

        $notasId = DB::table('rol_modulos')->where('mod_slug', 'notas')->value('mod_id');

        if ($notasId) {
            $base = DB::table('rol_modulos')->where('mod_padre_id', $notasId)->max('mod_orden') ?? 0;
            $children = [
                ['notas.rendimiento',         'Rendimiento',         'fas fa-chart-line'],
                ['notas.centralizador-anual', 'Centralizador Anual', 'fas fa-table'],
                ['notas.cuadro-honor',        'Cuadro de Honor',     'fas fa-medal'],
                ['notas.top3-cursos',         'Top 3 por Curso',     'fas fa-trophy'],
            ];
            foreach ($children as $i => [$slug, $nombre, $icon]) {
                $insertIfMissing([
                    'mod_nombre'   => $nombre,
                    'mod_slug'     => $slug,
                    'mod_icono'    => $icon,
                    'mod_padre_id' => $notasId,
                    'mod_orden'    => $base + $i + 1,
                    'mod_visible'  => 1,
                ]);
            }
        }

        $insertIfMissing([
            'mod_nombre'   => 'Concejo Educativo',
            'mod_slug'     => 'concejo',
            'mod_icono'    => 'fas fa-gavel',
            'mod_padre_id' => null,
            'mod_orden'    => 41,
            'mod_visible'  => 1,
        ]);

        $insertIfMissing([
            'mod_nombre'   => 'Parametrización',
            'mod_slug'     => 'parametrizacion',
            'mod_icono'    => 'fas fa-cogs',
            'mod_padre_id' => null,
            'mod_orden'    => 50,
            'mod_visible'  => 1,
        ]);

        $paramId = DB::table('rol_modulos')->where('mod_slug', 'parametrizacion')->value('mod_id');

        if ($paramId) {
            $hijos = [
                ['unidad-educativa', 'Unidad Educativa', 'fas fa-school'],
                ['niveles',          'Niveles',          'fas fa-layer-group'],
                ['gestiones',        'Gestiones',        'fas fa-calendar'],
            ];
            foreach ($hijos as $i => [$slug, $nombre, $icon]) {
                $insertIfMissing([
                    'mod_nombre'   => $nombre,
                    'mod_slug'     => $slug,
                    'mod_icono'    => $icon,
                    'mod_padre_id' => $paramId,
                    'mod_orden'    => $i + 1,
                    'mod_visible'  => 1,
                ]);
            }
        }
    }

    public function down()
    {
        $slugs = [
            'notas.rendimiento',
            'notas.centralizador-anual',
            'notas.cuadro-honor',
            'notas.top3-cursos',
            'unidad-educativa',
            'niveles',
            'gestiones',
            'parametrizacion',
            'concejo',
        ];
        DB::table('rol_permisos')
            ->whereIn('mod_id', DB::table('rol_modulos')->whereIn('mod_slug', $slugs)->pluck('mod_id'))
            ->delete();
        DB::table('rol_modulos')->whereIn('mod_slug', $slugs)->delete();
    }
}
