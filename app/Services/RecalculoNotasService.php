<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;

/**
 * Detecta y corrige las notas guardadas con la fórmula vieja del promedio
 * trimestral (suma de decimales redondeada al final) y las reescribe con la
 * fórmula oficial (suma de los promedios por dimensión ya redondeados), que es
 * la que ve el docente en pantalla.
 *
 * Trabaja siempre a partir de colegio_notas_detalle, que es la evidencia real
 * de cada nota: no inventa valores, sólo recompone el promedio.
 *
 * Verificado contra NotaPromedioService y contra la fórmula de
 * calificar.blade.php sobre la totalidad de las notas: los tres coinciden.
 *
 * NOTA DE IMPLEMENTACIÓN — por qué no hay una sola consulta grande:
 * MariaDB 10.4 (la que corre en XAMPP) revienta con violación de acceso
 * (0xc0000005) al unir la tabla derivada de agregación con los LEFT JOIN de
 * cursos/materias/docentes/estudiantes, que además cruzan colaciones distintas
 * (colegio_materias es latin1 y el resto utf8mb4). Por eso se resuelve con
 * consultas simples e independientes y el cruce se arma en PHP: es a prueba de
 * ese bug del optimizador y encima evita todos los COLLATE/CONVERT.
 *
 * @see NotaPromedioService  fórmula de referencia (carga manual e importación)
 */
class RecalculoNotasService
{
    /** Tope de filas devueltas al preview (defensa ante volúmenes absurdos). */
    private const LIMITE_FILAS = 8000;

    /** Nota mínima de aprobación (para avisar cambios de situación). */
    private const UMBRAL_APROBACION = 51;

    /** Clave normalizada para cruzar códigos entre tablas de distinta colación. */
    private static function k($v): string
    {
        return mb_strtoupper(trim((string) $v));
    }

    /**
     * Promedio OFICIAL (entero) y decimal exacto de cada nota, calculado desde
     * los detalles. Consulta simple sobre dos tablas: sin joins de colación,
     * sin derivadas anidadas.
     *
     * Replica exactamente NotaPromedioService::calcular():
     *   - por dimensión: valor directo si tiene 1 columna, si no promedio de las
     *     columnas con nota (> 0);
     *   - se topa al dimension_valor_max;
     *   - se redondea cada dimensión y se suman los enteros.
     *
     * @return array [nota_id => ['oficial' => int, 'exacto' => float]]
     */
    private function calculados(): array
    {
        $rows = DB::select("
            SELECT nd.nota_id,
                   nd.dimension_id,
                   d.dimension_valor_max AS maximo,
                   SUM(CASE WHEN nd.detalle_valor > 0 THEN nd.detalle_valor ELSE 0 END) AS suma,
                   COUNT(CASE WHEN nd.detalle_valor > 0 THEN 1 END)                     AS cuantas
            FROM colegio_notas_detalle nd
            JOIN notas_config_dimensiones d ON d.dimension_id = nd.dimension_id
            WHERE d.dimension_estado = 1
              AND nd.columna_num <= d.dimension_columnas
            GROUP BY nd.nota_id, nd.dimension_id, d.dimension_valor_max
        ");

        $out = [];
        foreach ($rows as $r) {
            $prom = $r->cuantas > 0 ? ((float) $r->suma) / (int) $r->cuantas : 0.0;
            $prom = min($prom, (float) $r->maximo);          // tope por dimensión

            $id = (int) $r->nota_id;
            if (!isset($out[$id])) $out[$id] = ['oficial' => 0, 'exacto' => 0.0];
            $out[$id]['oficial'] += (int) round($prom);      // suma de enteros
            $out[$id]['exacto']  += $prom;                   // suma exacta
        }
        return $out;
    }

    /** Notas dentro del alcance, sin joins hacia catálogos. */
    private function notasDelAlcance(int $gestion, ?int $periodoId): array
    {
        $sql = "
            SELECT n.nota_id, n.est_codigo, n.curmatdoc_id, n.nota_estado,
                   n.nota_promedio_trimestral AS actual, n.nota_promedio_decimal AS dec_actual,
                   n.periodo_id
            FROM colegio_notas n
            JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id
            WHERE p.periodo_gestion = ?
        ";
        $params = [$gestion];
        if ($periodoId) { $sql .= ' AND n.periodo_id = ? '; $params[] = $periodoId; }

        return DB::select($sql, $params);
    }

    /** Catálogos que necesita el preview, como mapas indexados por clave normalizada. */
    private function catalogos(int $gestion): array
    {
        $periodos = [];
        foreach (DB::select("SELECT periodo_id, periodo_numero, periodo_nombre FROM notas_config_periodos") as $r)
            $periodos[(int) $r->periodo_id] = $r;

        $cmd = [];
        foreach (DB::select("SELECT curmatdoc_id, cur_codigo, mat_codigo, doc_codigo FROM colegio_curso_materia_docente") as $r)
            $cmd[(int) $r->curmatdoc_id] = $r;

        $cursos = [];
        foreach (DB::select("SELECT cur_codigo, cur_nombre, cur_nivel, cur_orden FROM colegio_cursos") as $r)
            $cursos[self::k($r->cur_codigo)] = $r;

        $materias = [];
        foreach (DB::select("SELECT mat_codigo, mat_nombre FROM colegio_materias") as $r)
            $materias[self::k($r->mat_codigo)] = $r;

        $docentes = [];
        foreach (DB::select("SELECT doc_codigo, doc_nombres, doc_apellidos FROM colegio_docentes") as $r)
            $docentes[self::k($r->doc_codigo)] = trim($r->doc_apellidos . ' ' . $r->doc_nombres);

        $estudiantes = [];
        foreach (DB::select("SELECT est_codigo, est_nombres, est_apellidos FROM colegio_estudiantes") as $r)
            $estudiantes[self::k($r->est_codigo)] = trim($r->est_apellidos . ' ' . $r->est_nombres);

        $lista = [];
        foreach (DB::select("SELECT est_codigo, lista_numero FROM colegio_lista_curso WHERE lista_gestion = ?", [$gestion]) as $r)
            $lista[self::k($r->est_codigo)] = $r->lista_numero;

        return compact('periodos', 'cmd', 'cursos', 'materias', 'docentes', 'estudiantes', 'lista');
    }

    /**
     * Analiza qué notas cambiarían, sin tocar nada.
     * Devuelve el resumen y el árbol curso → materia/trimestre → estudiante,
     * con el nota_id de cada fila para poder seleccionarlas en la pantalla.
     */
    public function analizar(int $gestion, ?int $periodoId = null): array
    {
        $calc  = $this->calculados();
        $notas = $this->notasDelAlcance($gestion, $periodoId);
        $cat   = $this->catalogos($gestion);

        $totalNotas  = count($notas);
        $soloDecimal = 0;
        $afectadasRaw = [];

        foreach ($notas as $n) {
            $id = (int) $n->nota_id;
            if (!isset($calc[$id])) continue;               // sin detalles: no se toca
            $oficial = $calc[$id]['oficial'];
            $guardado = (int) round((float) $n->actual);

            if ($oficial === $guardado) {
                if ($n->dec_actual === null) $soloDecimal++;
                continue;
            }
            $afectadasRaw[] = [$n, $oficial];
        }

        $truncado = count($afectadasRaw) > self::LIMITE_FILAS;
        if ($truncado) $afectadasRaw = array_slice($afectadasRaw, 0, self::LIMITE_FILAS);

        // ── Árbol curso → asignación → estudiante ───────────────────────────
        $estados = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $materiasSet = []; $estudiantesSet = []; $docentesSet = [];
        $sube = 0; $baja = 0; $maxDelta = 0; $flipsTotal = 0;
        $arbol = [];

        foreach ($afectadasRaw as [$n, $oficial]) {
            $actual = (int) round((float) $n->actual);
            $delta  = $oficial - $actual;

            $antesAprob   = $actual  >= self::UMBRAL_APROBACION;
            $despuesAprob = $oficial >= self::UMBRAL_APROBACION;
            $flip         = $antesAprob !== $despuesAprob;

            $asig    = $cat['cmd'][(int) $n->curmatdoc_id] ?? null;
            $curCod  = $asig->cur_codigo ?? '—';
            $matCod  = $asig->mat_codigo ?? '—';
            $curso   = $cat['cursos'][self::k($curCod)]   ?? null;
            $materia = $cat['materias'][self::k($matCod)] ?? null;
            $docente = $cat['docentes'][self::k($asig->doc_codigo ?? '')] ?? '—';
            $per     = $cat['periodos'][(int) $n->periodo_id] ?? null;
            $estNom  = $cat['estudiantes'][self::k($n->est_codigo)] ?? $n->est_codigo;
            $nroLista = $cat['lista'][self::k($n->est_codigo)] ?? null;

            $estados[(int) $n->nota_estado] = ($estados[(int) $n->nota_estado] ?? 0) + 1;
            $materiasSet[$matCod] = true;
            $estudiantesSet[$n->est_codigo] = true;
            if ($docente !== '—') $docentesSet[$docente] = true;
            if ($delta > 0) $sube++; elseif ($delta < 0) $baja++;
            if (abs($delta) > $maxDelta) $maxDelta = abs($delta);
            if ($flip) $flipsTotal++;

            $ck = (string) $curCod;
            if (!isset($arbol[$ck])) {
                $arbol[$ck] = [
                    'codigo' => $ck,
                    'nombre' => $curso->cur_nombre ?? $ck,
                    'nivel'  => $curso->cur_nivel  ?? null,
                    'orden'  => $curso->cur_orden  ?? 9999,
                    'notas' => 0, 'flips' => 0, 'aprobadas' => 0,
                    'estudiantes' => [], 'asignaciones' => [],
                ];
            }
            $arbol[$ck]['notas']++;
            if ($flip) $arbol[$ck]['flips']++;
            if ((int) $n->nota_estado === 2) $arbol[$ck]['aprobadas']++;
            $arbol[$ck]['estudiantes'][$n->est_codigo] = true;

            $ak = $matCod . '|' . $n->periodo_id;
            if (!isset($arbol[$ck]['asignaciones'][$ak])) {
                $arbol[$ck]['asignaciones'][$ak] = [
                    'key'            => $ck . '|' . $ak,
                    'materia'        => $materia->mat_nombre ?? $matCod,
                    'docente'        => $docente,
                    'periodo'        => $per->periodo_nombre ?? (($per->periodo_numero ?? '?') . '° Trim.'),
                    'periodo_numero' => (int) ($per->periodo_numero ?? 0),
                    'estado'         => (int) $n->nota_estado,
                    'notas' => 0, 'flips' => 0, 'filas' => [],
                ];
            }
            $arbol[$ck]['asignaciones'][$ak]['notas']++;
            if ($flip) $arbol[$ck]['asignaciones'][$ak]['flips']++;
            $arbol[$ck]['asignaciones'][$ak]['filas'][] = [
                'nota_id' => (int) $n->nota_id,
                'nro'     => $nroLista,
                'nombre'  => $estNom,
                'actual'  => $actual,
                'crudo'   => round((float) $n->actual, 2),
                'nuevo'   => $oficial,
                'delta'   => $delta,
                'flip'    => $flip,
                'antes'   => $antesAprob   ? 'APROBADO' : 'REPROBADO',
                'despues' => $despuesAprob ? 'APROBADO' : 'REPROBADO',
            ];
        }

        // Normalizar y ordenar: los cursos con cambios de situación primero.
        $cursos = [];
        foreach ($arbol as $c) {
            $c['estudiantes']  = count($c['estudiantes']);
            $c['asignaciones'] = array_values($c['asignaciones']);
            usort($c['asignaciones'], fn($a, $b) => [$b['flips'], $b['notas']] <=> [$a['flips'], $a['notas']]);
            foreach ($c['asignaciones'] as &$a) {
                usort($a['filas'], fn($x, $y) =>
                    [(int) ($x['nro'] ?? 9999), $x['nombre']] <=> [(int) ($y['nro'] ?? 9999), $y['nombre']]);
            }
            unset($a);
            $cursos[] = $c;
        }
        usort($cursos, fn($a, $b) =>
            [$b['flips'], $b['notas'], -$a['orden']] <=> [$a['flips'], $a['notas'], -$b['orden']]);

        $afectadas = count($afectadasRaw);

        return [
            'gestion'          => $gestion,
            'periodo_id'       => $periodoId,
            'total_notas'      => $totalNotas,
            'afectadas'        => $afectadas,
            'solo_decimal'     => $soloDecimal,
            'porcentaje'       => $totalNotas > 0 ? round($afectadas * 100 / $totalNotas, 1) : 0,
            'n_cursos'         => count($cursos),
            'n_materias'       => count($materiasSet),
            'n_estudiantes'    => count($estudiantesSet),
            'n_docentes'       => count($docentesSet),
            'n_asignaciones'   => array_sum(array_map(fn($c) => count($c['asignaciones']), $cursos)),
            'por_estado'       => $estados,
            'aprobadas'        => $estados[2] ?? 0,
            'sube'             => $sube,
            'baja'             => $baja,
            'max_delta'        => $maxDelta,
            'cambia_situacion' => $flipsTotal,
            'cursos'           => $cursos,
            'truncado'         => $truncado,
        ];
    }

    /**
     * Aplica el recálculo. Todo o nada, y queda registrado en auditoría.
     *
     * @param  int[]|null $notaIds  Si se pasa, sólo se corrigen esas notas
     *                              (selección hecha en pantalla). Si es null,
     *                              se corrige todo el alcance.
     * @return array{afectadas:int, decimal_completado:int, filas:int, cambia_situacion:int}
     */
    public function ejecutar(int $gestion, ?int $periodoId = null, ?array $notaIds = null): array
    {
        $previo    = $this->analizar($gestion, $periodoId);
        $seleccion = $this->filtrarSeleccion($previo, $notaIds);

        $calc  = $this->calculados();
        $notas = $this->notasDelAlcance($gestion, $periodoId);
        $set   = $notaIds === null ? null : array_flip(array_map('intval', $notaIds));

        // Qué escribir en cada nota: sólo las que cambian, más (si se aplica todo)
        // las que únicamente necesitan que se les complete el decimal.
        $aEscribir = [];
        foreach ($notas as $n) {
            $id = (int) $n->nota_id;
            if (!isset($calc[$id])) continue;
            if ($set !== null && !isset($set[$id])) continue;

            $oficial  = $calc[$id]['oficial'];
            $exacto   = round($calc[$id]['exacto'], 2);
            $guardado = (int) round((float) $n->actual);

            $cambia = ($oficial !== $guardado);
            $faltaDecimal = ($n->dec_actual === null);

            if ($cambia || ($set === null && $faltaDecimal)) {
                $aEscribir[$id] = [$oficial, $exacto];
            }
        }

        DB::beginTransaction();
        try {
            $filasTocadas = 0;

            // UPDATE por lotes con CASE: sin tablas derivadas, que es lo que
            // hace caer a MariaDB 10.4 en este esquema.
            foreach (array_chunk($aEscribir, 400, true) as $lote) {
                $ids = array_keys($lote);
                $casesProm = ''; $casesDec = ''; $bind = [];
                foreach ($lote as $id => [$of, $ex]) {
                    $casesProm .= " WHEN ? THEN ? ";  $bind[] = $id; $bind[] = $of;
                }
                foreach ($lote as $id => [$of, $ex]) {
                    $casesDec  .= " WHEN ? THEN ? ";  $bind[] = $id; $bind[] = $ex;
                }
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $bind = array_merge($bind, $ids);

                $filasTocadas += DB::update("
                    UPDATE colegio_notas
                    SET nota_promedio_trimestral = CASE nota_id $casesProm END,
                        nota_promedio_decimal    = CASE nota_id $casesDec  END
                    WHERE nota_id IN ($ph)
                ", $bind);
            }

            // audit_accion es un ENUM('crear','editar','eliminar'); el detalle
            // del tipo de acción va en el módulo y la descripción.
            Auditoria::registrar(
                'editar',
                'NOTAS_RECALCULO',
                sprintf(
                    'Recálculo de promedios trimestrales — gestión %d, %s. %s ' .
                    'Notas corregidas: %d; %d aprobadas; %d cambian de situación; ' .
                    '%d registro(s) actualizado(s).',
                    $gestion,
                    $periodoId ? ('trimestre ID ' . $periodoId) : 'TODOS los trimestres',
                    $notaIds === null
                        ? 'Alcance completo.'
                        : sprintf('Selección manual (%d de %d notas detectadas).',
                                  count($notaIds), $previo['afectadas']),
                    $seleccion['afectadas'],
                    $seleccion['aprobadas'],
                    $seleccion['cambia_situacion'],
                    $filasTocadas
                ),
                $periodoId,
                [
                    'resumen' => collect($previo)->except(['cursos'])->all(),
                    'cambios' => $seleccion['cambios'],
                ],
                ['filas_actualizadas' => $filasTocadas]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'afectadas'          => $seleccion['afectadas'],
            'decimal_completado' => $notaIds === null ? $previo['solo_decimal'] : 0,
            'filas'              => $filasTocadas,
            'cambia_situacion'   => $seleccion['cambia_situacion'],
        ];
    }

    /**
     * Recorta el análisis a las notas realmente seleccionadas y arma el
     * snapshot para auditoría (curso/materia/estudiante: antes → después).
     */
    /**
     * Desglose de UNA nota: qué celdas cargó el docente en cada dimensión, qué
     * promedio exacto dan, qué redondeo se le aplica a cada una y por qué.
     *
     * Es la justificación de la corrección: permite mostrarle al usuario que el
     * entero nuevo no sale de redondear el total, sino de sumar los promedios
     * de cada dimensión ya redondeados (que es lo que ve el docente).
     */
    public function desglose(int $notaId): ?array
    {
        $nota = DB::selectOne("
            SELECT n.nota_id, n.est_codigo, n.curmatdoc_id, n.periodo_id, n.nota_estado,
                   n.nota_promedio_trimestral AS actual, n.nota_promedio_decimal AS dec_actual,
                   p.periodo_nombre, p.periodo_numero, p.periodo_gestion
            FROM colegio_notas n
            JOIN notas_config_periodos p ON p.periodo_id = n.periodo_id
            WHERE n.nota_id = ?", [$notaId]);
        if (!$nota) return null;

        $dims = DB::select("
            SELECT dimension_id, dimension_nombre, dimension_valor_max, dimension_columnas
            FROM notas_config_dimensiones
            WHERE dimension_estado = 1 AND dimension_gestion = ?
            ORDER BY dimension_orden", [$nota->periodo_gestion]);

        $celdas = [];
        foreach (DB::select("
            SELECT dimension_id, columna_num, detalle_valor
            FROM colegio_notas_detalle WHERE nota_id = ?
            ORDER BY dimension_id, columna_num", [$notaId]) as $d) {
            $celdas[(int) $d->dimension_id][(int) $d->columna_num] = (float) $d->detalle_valor;
        }

        $filas = [];
        $exacto = 0.0;
        $oficial = 0;

        foreach ($dims as $dim) {
            $cols     = $celdas[(int) $dim->dimension_id] ?? [];
            $usadas   = [];
            $ignoradas = 0;
            for ($c = 1; $c <= $dim->dimension_columnas; $c++) {
                if (isset($cols[$c]) && $cols[$c] > 0) $usadas[$c] = $cols[$c];
                elseif (isset($cols[$c])) $ignoradas++;
            }

            $suma    = array_sum($usadas);
            $cuantas = count($usadas);
            $unaCol  = ((int) $dim->dimension_columnas === 1);

            $bruto  = $cuantas > 0 ? ($unaCol ? $suma : $suma / $cuantas) : 0.0;
            $maximo = (float) $dim->dimension_valor_max;
            $topado = $bruto > $maximo;
            $prom   = min($bruto, $maximo);

            $redondeado = (int) round($prom);
            $delta      = $redondeado - $prom;
            $fraccion   = $prom - floor($prom);

            // Tipo de redondeo, que es lo que justifica la diferencia final.
            if ($cuantas === 0)              $tipo = 'sin_notas';
            elseif (abs($fraccion) < 0.0001) $tipo = 'exacto';
            elseif (abs($fraccion - 0.5) < 0.0001) $tipo = 'medio';   // .5 sube
            elseif ($fraccion < 0.5)         $tipo = 'abajo';
            else                             $tipo = 'arriba';

            $exacto  += $prom;
            $oficial += $redondeado;

            $filas[] = [
                'nombre'      => $dim->dimension_nombre,
                'maximo'      => $maximo,
                'columnas'    => (int) $dim->dimension_columnas,
                'celdas'      => array_values($usadas),
                'vacias'      => $dim->dimension_columnas - $cuantas,
                'suma'        => round($suma, 2),
                'cuantas'     => $cuantas,
                'una_columna' => $unaCol,
                'bruto'       => round($bruto, 4),
                'topado'      => $topado,
                'promedio'    => round($prom, 4),
                'redondeado'  => $redondeado,
                'delta'       => round($delta, 4),
                'fraccion'    => round($fraccion, 4),
                'tipo'        => $tipo,
            ];
        }

        $cat     = $this->catalogos((int) $nota->periodo_gestion);
        $asig    = $cat['cmd'][(int) $nota->curmatdoc_id] ?? null;
        $curso   = $cat['cursos'][self::k($asig->cur_codigo ?? '')]   ?? null;
        $materia = $cat['materias'][self::k($asig->mat_codigo ?? '')] ?? null;

        $guardado = (int) round((float) $nota->actual);

        return [
            'nota_id'    => (int) $nota->nota_id,
            'estudiante' => $cat['estudiantes'][self::k($nota->est_codigo)] ?? $nota->est_codigo,
            'curso'      => $curso->cur_nombre ?? '—',
            'materia'    => $materia->mat_nombre ?? '—',
            'periodo'    => $nota->periodo_nombre ?: ($nota->periodo_numero . '° Trim.'),
            'dimensiones'=> $filas,
            'exacto'     => round($exacto, 4),
            'oficial'    => $oficial,
            'guardado'   => $guardado,
            'crudo'      => round((float) $nota->actual, 2),
            'metodo_viejo' => (int) round($exacto),
            'diferencia' => $oficial - $guardado,
        ];
    }

    private function filtrarSeleccion(array $previo, ?array $notaIds): array
    {
        $set = $notaIds === null ? null : array_flip(array_map('intval', $notaIds));

        $afectadas = 0; $aprobadas = 0; $flips = 0; $cambios = [];
        foreach ($previo['cursos'] as $curso) {
            foreach ($curso['asignaciones'] as $asig) {
                foreach ($asig['filas'] as $fila) {
                    if ($set !== null && !isset($set[$fila['nota_id']])) continue;
                    $afectadas++;
                    if ($asig['estado'] === 2) $aprobadas++;
                    if ($fila['flip']) $flips++;
                    if (count($cambios) < 3000) {
                        $cambios[] = [
                            'curso'      => $curso['nombre'],
                            'materia'    => $asig['materia'],
                            'periodo'    => $asig['periodo'],
                            'estudiante' => $fila['nombre'],
                            'antes'      => $fila['actual'],
                            'despues'    => $fila['nuevo'],
                        ];
                    }
                }
            }
        }

        return compact('afectadas', 'aprobadas', 'flips', 'cambios')
             + ['cambia_situacion' => $flips];
    }
}
