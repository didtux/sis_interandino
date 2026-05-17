<?php

namespace App\Services;

use App\Models\Materia;
use App\Models\MateriaCurso;
use Illuminate\Support\Collection;

/**
 * Fuente única para resolver la configuración de materias en el contexto de UN curso:
 *  - campo/área del Ministerio
 *  - orden de aparición
 *  - promediable (si suma al promedio del campo)
 *
 * Si para una materia no hay fila en `colegio_materia_curso`, cae al valor global
 * de `colegio_materias` como fallback. Esto permite migrar reportes gradualmente.
 */
class MateriaCursoService
{
    /**
     * Devuelve un mapa `mat_codigo => stdClass` con campo/orden/promediable
     * resuelto para el curso dado.
     */
    public function configPorCurso(string $curCodigo): array
    {
        $rows = MateriaCurso::where('cur_codigo', $curCodigo)
            ->where('matc_estado', 1)
            ->get()->keyBy('mat_codigo');

        // Solo materias que aparecen en la pivote del curso (si está poblada).
        $matCodigos = $rows->keys()->all();
        $materias = Materia::whereIn('mat_codigo', $matCodigos)->get()->keyBy('mat_codigo');

        $out = [];
        foreach ($rows as $code => $row) {
            $mat = $materias->get($code);
            if (!$mat) continue;
            $out[$code] = (object) [
                'mat_codigo'   => $code,
                'mat_nombre'   => $mat->mat_nombre,
                'campo'        => $row->matc_campo ?: ($mat->mat_campo ?: null),
                'orden'        => $row->matc_orden ?? ($mat->mat_orden ?? 999),
                'promediable'  => (int) ($row->matc_promediable ?? 0),
                'materia'      => $mat,
            ];
        }
        return $out;
    }

    /**
     * Construye los "grupos por campo" para un curso específico.
     * Devuelve [$grupos: Collection, $map: array mat_codigo => grupo].
     */
    public function gruposPorCampo(string $curCodigo): array
    {
        $config = $this->configPorCurso($curCodigo);

        // Fallback: si la pivote está vacía para este curso, usar global.
        if (empty($config)) {
            return $this->gruposPorCampoGlobal();
        }

        $porCampo = collect($config)
            ->filter(fn($c) => !empty($c->campo))
            ->groupBy(fn($c) => trim((string) $c->campo));

        $grupos = collect();
        $map = [];
        foreach ($porCampo as $campo => $items) {
            $items = $items->sortBy('orden')->values();
            if ($items->count() < 2) continue; // solo campos con 2+ materias son "grupo"

            $materias = $items->pluck('materia');
            $matProm  = $items->where('promediable', 1)->pluck('materia');

            $grupo = (object) [
                'grupo_id'             => 'campo_' . md5($campo),
                'grupo_nombre'         => $campo,
                'materias'             => $materias,
                'materiasPromediables' => $matProm,
            ];
            $grupos->push($grupo);
            foreach ($items as $it) {
                $map[$it->mat_codigo] = $grupo;
            }
        }
        return [$grupos, $map];
    }

    /**
     * Fallback al modo legacy: usa los campos globales de `colegio_materias`.
     */
    private function gruposPorCampoGlobal(): array
    {
        $materias = Materia::whereNotNull('mat_campo')
            ->where('mat_campo', '!=', '')
            ->orderBy('mat_orden')->get();

        $porCampo = $materias->groupBy(fn($m) => trim((string) $m->mat_campo));

        $grupos = collect();
        $map = [];
        foreach ($porCampo as $campo => $mats) {
            if ($mats->count() < 2) continue;
            $grupo = (object) [
                'grupo_id'             => 'campo_' . md5($campo),
                'grupo_nombre'         => $campo,
                'materias'             => $mats->values(),
                'materiasPromediables' => $mats->where('mat_promediable', 1)->values(),
            ];
            $grupos->push($grupo);
            foreach ($mats as $m) {
                $map[$m->mat_codigo] = $grupo;
            }
        }
        return [$grupos, $map];
    }

    /**
     * Orden ministerial de materias para un curso (mat_codigo => orden).
     */
    public function ordenMinisterial(string $curCodigo): array
    {
        $config = $this->configPorCurso($curCodigo);
        $out = [];
        foreach ($config as $code => $c) {
            $out[$code] = $c->orden;
        }
        return $out;
    }
}
