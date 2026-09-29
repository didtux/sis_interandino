<?php

namespace App\Services;

use App\Models\CursoMateriaDocente;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Las notas tal como las imprimen el boletín y el centralizador. Los dos y el
 * cuadro de honor leen de acá: antes el cuadro tenía su propia consulta y
 * rankeaba con el promedio decimal, notas sin aprobar y asignaciones
 * inactivas, así que el puesto no cuadraba con ningún otro reporte.
 * El cuadro de honor usa la SUMA y el PROMEDIO del centralizador.
 *
 * Criterio (el del boletín):
 *  - sólo notas APROBADAS (nota_estado = 2);
 *  - nota trimestral = nota_promedio_trimestral redondeada a entero;
 *  - sólo asignaciones activas del curso, una por materia;
 *  - promedio anual de la materia = redondeo del promedio de los trimestres
 *    con nota (> 0).
 */
class BoletinNotasService
{
    /** @var array<string, array>  "cur|periodo" => [est_codigo][mat_codigo] => nota */
    private array $notasCursoCache = [];

    /** @var array<string, Collection> */
    private array $materiasCache = [];

    /** Asignaciones activas del curso, una por materia, en el orden del boletín. */
    public function materiasDelCurso(string $curCodigo): Collection
    {
        if (isset($this->materiasCache[$curCodigo])) return $this->materiasCache[$curCodigo];

        // Orden por config POR CURSO (matc_orden); fallback al mat_orden global.
        $orden = (new MateriaCursoService())->ordenMinisterial($curCodigo);
        return $this->materiasCache[$curCodigo] = CursoMateriaDocente::with('materia')
            ->where('cur_codigo', $curCodigo)->where('curmatdoc_estado', 1)
            ->get()
            ->sortBy(function ($cmd) use ($orden) {
                return $orden[$cmd->mat_codigo] ?? ($cmd->materia->mat_orden ?? 999);
            })
            ->unique('mat_codigo')
            ->values();
    }

    /**
     * Notas aprobadas de un curso y periodo: [est_codigo][mat_codigo] => nota.
     *
     * Los centralizadores recorren estudiante x materia x trimestre, así que pedir
     * la nota de a una era una consulta con subquery por celda: 444 consultas en
     * un curso de 37 alumnos. Todo el curso entra en una sola.
     */
    public function notasDelCurso(string $curCodigo, $periodoId): array
    {
        $key = $curCodigo . '|' . $periodoId;
        if (isset($this->notasCursoCache[$key])) return $this->notasCursoCache[$key];

        // Materia de cada asignación del curso. Se cruza en PHP: colegio_materias
        // es latin1 y colegio_notas utf8mb4, y unirlas en SQL da "Illegal mix of
        // collations".
        $materiaPorAsignacion = DB::table('colegio_curso_materia_docente')
            ->where('cur_codigo', $curCodigo)
            ->pluck('mat_codigo', 'curmatdoc_id')
            ->all();

        $mapa = [];
        if (!empty($materiaPorAsignacion)) {
            DB::table('colegio_notas')
                ->whereIn('curmatdoc_id', array_keys($materiaPorAsignacion))
                ->where('periodo_id', $periodoId)
                ->where('nota_estado', 2)
                ->select('est_codigo', 'curmatdoc_id', 'nota_promedio_trimestral')
                ->orderBy('nota_id')
                ->get()
                ->each(function ($n) use (&$mapa, $materiaPorAsignacion) {
                    $mat = $materiaPorAsignacion[$n->curmatdoc_id] ?? null;
                    if ($mat === null) return;
                    // Si una materia tiene más de una asignación en el curso se
                    // conserva la primera, igual que hacía el first() anterior.
                    if (!isset($mapa[$n->est_codigo][$mat])) {
                        $mapa[$n->est_codigo][$mat] = (int) round($n->nota_promedio_trimestral);
                    }
                });
        }

        return $this->notasCursoCache[$key] = $mapa;
    }

    public function notaPromedio(string $estCodigo, string $curCodigo, string $matCodigo, $periodoId): int
    {
        return $this->notasDelCurso($curCodigo, $periodoId)[$estCodigo][$matCodigo] ?? 0;
    }

    /**
     * Filas de materias del boletín de un estudiante:
     * [mat_codigo => ['nombre', 'trimestres' => [periodo_numero => nota], 'promedio']].
     */
    public function notasEstudiante(string $estCodigo, string $curCodigo, $periodos): array
    {
        $notasData = [];
        foreach ($this->materiasDelCurso($curCodigo) as $cmd) {
            $mat = $cmd->mat_codigo;
            $notasData[$mat] = ['nombre' => $cmd->materia->mat_nombre, 'trimestres' => [], 'promedio' => 0];
            $suma = 0; $count = 0;
            foreach ($periodos as $p) {
                $val = $this->notaPromedio($estCodigo, $curCodigo, $mat, $p->periodo_id);
                $notasData[$mat]['trimestres'][$p->periodo_numero] = $val;
                if ($val > 0) { $suma += $val; $count++; }
            }
            $notasData[$mat]['promedio'] = $count > 0 ? round($suma / $count) : 0;
        }
        return $notasData;
    }

    /**
     * Fila del centralizador de un estudiante: materias con sus trimestres y
     * promedio (1 decimal), SUMA de todas las notas y PROMEDIO final.
     *
     * Con un solo periodo es la vista trimestral; con varios, la anual.
     * El PROMEDIO final divide por las notas que realmente existen: antes
     * dividía por materias x trimestres configurados, así que con el 3er
     * trimestre sin cargar un alumno de 89 y 84 salía con 57,9 anual.
     *
     * @return array{materias:array, suma:int, promedio:float, exacto:float, notas:int}
     */
    public function filaCentralizador(string $estCodigo, string $curCodigo, $periodos): array
    {
        $materias = [];
        $suma = 0; $notas = 0;
        foreach ($this->materiasDelCurso($curCodigo) as $cmd) {
            $mat = $cmd->mat_codigo;
            $trimestres = [];
            $sumaMat = 0; $countMat = 0;
            foreach ($periodos as $p) {
                $val = $this->notaPromedio($estCodigo, $curCodigo, $mat, $p->periodo_id);
                $trimestres[$p->periodo_numero] = $val;
                if ($val > 0) { $sumaMat += $val; $countMat++; }
            }
            $materias[$mat] = [
                'trimestres' => $trimestres,
                'promedio'   => $countMat > 0 ? round($sumaMat / $countMat, 1) : 0,
            ];
            $suma  += $sumaMat;
            $notas += $countMat;
        }

        $exacto = $notas > 0 ? $suma / $notas : 0.0;
        return [
            'materias' => $materias,
            'suma'     => $suma,
            'promedio' => round($exacto, 1),
            'exacto'   => $exacto,
            'notas'    => $notas,
        ];
    }

    /**
     * Puntaje de cuadro de honor: la SUMA y el PROMEDIO del centralizador
     * (anual, o del trimestre pedido). Se ordena por el promedio sin redondear
     * para que el redondeo a 1 decimal no invente empates.
     *
     * @return array{suma:int, promedio:float, exacto:float, materias:int}
     */
    public function puntaje(string $estCodigo, string $curCodigo, $periodos, ?int $periodoNumero = null): array
    {
        if ($periodoNumero) {
            $periodos = collect($periodos)->where('periodo_numero', $periodoNumero)->values();
        }
        $fila = $this->filaCentralizador($estCodigo, $curCodigo, $periodos);
        return [
            'suma'     => $fila['suma'],
            'promedio' => $fila['promedio'],
            'exacto'   => $fila['exacto'],
            'materias' => $fila['notas'],
        ];
    }
}
