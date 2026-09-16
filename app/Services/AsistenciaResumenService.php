<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fuente única de verdad para el resumen de asistencia de un estudiante.
 *
 * Reglas acordadas:
 *  - "días hábiles del calendario" = lunes-viernes en el rango (sin descontar feriados).
 *  - "días trabajados del curso"   = días distintos con registros en colegio_asistencia
 *                                    dentro del rango y en día L-V.
 *  - presencias                    = días distintos donde el estudiante tiene registro.
 *  - atrasos                       = filas en asistencia_atrasos del estudiante (L-V).
 *  - licencias_dias                = días trabajados del curso cubiertos por algún permiso activo.
 *  - licencias_solicitudes         = cantidad de permisos cuya FECHA INICIO cae en el rango.
 *  - faltas                        = días trabajados del curso − presencias − licencias_dias.
 *  - dias_trabajados_efectivos     = días trabajados del curso − faltas
 *                                    (atrasos y licencias NO restan).
 *
 * Se ofrece además `resumenPorTrimestre` que aplica deduplicación global:
 * cada fecha (presencia, falta, atraso, permiso, día con permiso) se asigna al
 * primer trimestre cuyo rango la contenga, evitando doble conteo cuando los
 * periodos tienen configuración traslapada.
 */
class AsistenciaResumenService
{
    /**
     * Fallback si no hay configuración cargada para el curso del estudiante.
     * Solo se usa cuando no hay ninguna config con turno=Mañana aplicable.
     */
    private const FALLBACK_DESDE = '07:00:00';
    private const FALLBACK_HASTA = '13:00:00';

    /** Cache en memoria por curso+turno: "cur|turno" => ['desde','hasta','tolerancia'] */
    private array $configCache = [];

    /** Turno con el que trabaja esta instancia ('Mañana' por defecto, 'Tarde', etc.). */
    private string $turno;

    /**
     * Resuelve los horarios especiales por rango de fechas (horario de invierno,
     * recesos). Se comparte entre llamadas para aprovechar su cache interna.
     */
    private HorarioEspecialService $horarios;

    /** Cache de fechas sin clases por rango: "inicio|fin" => Collection['Y-m-d' => true] */
    private array $sinClasesCache = [];

    // ── Caches de lo que NO depende del estudiante ──────────────────────────
    // Un centralizador llama a este service una vez por estudiante y trimestre.
    // Sin estas caches se repetían 38 consultas por estudiante, casi todas
    // idénticas entre compañeros del mismo curso: 10 s por curso, ~4 min el
    // centralizador completo. Ver el encabezado de fechasTrabajadasCurso().

    /** "est_codigo" => fila de colegio_estudiantes (o null). */
    private array $estudianteCache = [];

    /** "cur_codigo" => cur_nivel. */
    private array $nivelCache = [];

    /** "inicio|fin" => feriados oficiales del rango, volteados para lookup. */
    private array $feriadosCache = [];

    /** "desde|hasta|inicio|fin" => días en que el colegio trabajó. */
    private array $trabajadasCache = [];

    /** Rangos ya traídos en bloque por curso: "cur|inicio|fin" => true */
    private array $lotes = [];

    /** "inicio|fin" => [est_codigo => [['fecha'=>…,'hora'=>…], …]] */
    private array $marcasCache = [];

    /** "inicio|fin" => [est_codigo => [['fecha'=>…,'hora'=>…], …]] atrasos manuales */
    private array $manualesCache = [];

    /** "inicio|fin" => [est_codigo => [['desde'=>…,'hasta'=>…,'fila'=>…], …]] permisos activos */
    private array $permisosCache = [];

    /** "periodo_id" => si el trimestre ya se puede mostrar. */
    private array $visibleCache = [];

    public function __construct(string $turno = 'Mañana')
    {
        $this->turno = $turno;
        $this->horarios = new HorarioEspecialService();
    }

    /** Fila del estudiante, cacheada: se pide varias veces por reporte. */
    private function estudiante(string $estCodigo)
    {
        return $this->estudianteCache[$estCodigo]
            ??= DB::table('colegio_estudiantes')->where('est_codigo', $estCodigo)->first() ?: false;
    }

    // ── Carga en bloque ─────────────────────────────────────────────────────
    //
    // Un centralizador pide el resumen de cada estudiante del curso, y antes eso
    // eran ~20 consultas por alumno: marcaciones, atrasos manuales y permisos,
    // una por estudiante y trimestre. Como el reporte siempre recorre el curso
    // entero, la primera consulta trae de una vez a TODO el curso para ese rango
    // y las demás salen de memoria: pasa de ~20 consultas por alumno a 3 por
    // curso y rango.
    //
    // Para el boletín de un solo estudiante el costo extra es traer las filas de
    // sus compañeros en la misma consulta, que es despreciable frente a la ida y
    // vuelta a la base.

    /**
     * Trae en una sola consulta —por curso y rango— las marcaciones, los atrasos
     * manuales y los permisos, y deja todo indexado por estudiante.
     */
    private function asegurarLote(string $estCodigo, string $inicio, string $fin): void
    {
        $est = $this->estudiante($estCodigo);
        $cur = $est ? $est->cur_codigo : null;

        $loteKey = ($cur ?? $estCodigo) . '|' . $inicio . '|' . $fin;
        if (isset($this->lotes[$loteKey])) return;
        $this->lotes[$loteKey] = true;

        // Compañeros de curso; si el estudiante no tiene curso, sólo él.
        $codigos = $cur
            ? DB::table('colegio_estudiantes')->where('cur_codigo', $cur)->pluck('est_codigo')->all()
            : [$estCodigo];
        if (!in_array($estCodigo, $codigos, true)) $codigos[] = $estCodigo;

        $rangoKey = $inicio . '|' . $fin;

        // Sólo se piden los estudiantes que aún no estén en memoria para este rango.
        $faltantes = array_values(array_filter(
            $codigos,
            fn($c) => !isset($this->marcasCache[$rangoKey][$c])
        ));
        if (empty($faltantes)) return;

        foreach ($faltantes as $c) {
            $this->marcasCache[$rangoKey][$c]   = [];
            $this->manualesCache[$rangoKey][$c] = [];
            $this->permisosCache[$rangoKey][$c] = [];
        }

        foreach (array_chunk($faltantes, 500) as $trozo) {
            DB::table('colegio_asistencia')
                ->whereIn('estud_codigo', $trozo)
                ->whereBetween('asis_fecha', [$inicio, $fin])
                ->select('estud_codigo', 'asis_fecha', 'asis_hora')
                ->orderBy('asis_fecha')
                ->get()
                ->each(function ($r) use ($rangoKey) {
                    $this->marcasCache[$rangoKey][$r->estud_codigo][] = [
                        'fecha' => substr((string) $r->asis_fecha, 0, 10),
                        'hora'  => self::soloHora($r->asis_hora),
                    ];
                });

            DB::table('asistencia_atrasos')
                ->whereIn('estud_codigo', $trozo)
                ->whereBetween('atraso_fecha', [$inicio, $fin])
                ->select('estud_codigo', 'atraso_fecha', 'atraso_hora')
                ->get()
                ->each(function ($r) use ($rangoKey) {
                    $this->manualesCache[$rangoKey][$r->estud_codigo][] = [
                        'fecha' => substr((string) $r->atraso_fecha, 0, 10),
                        'hora'  => $r->atraso_hora,
                    ];
                });

            DB::table('asistencia_permisos')
                ->whereIn('estud_codigo', $trozo)
                ->where('permiso_estado', 1)
                ->where('permiso_fecha_inicio', '<=', $fin)
                ->where('permiso_fecha_fin', '>=', $inicio)
                ->select('estud_codigo', 'permiso_codigo', 'permiso_tipo', 'permiso_motivo',
                         'permiso_origen', 'permiso_fecha_inicio', 'permiso_fecha_fin')
                ->orderBy('permiso_fecha_inicio')
                ->get()
                ->each(function ($r) use ($rangoKey) {
                    $this->permisosCache[$rangoKey][$r->estud_codigo][] = [
                        'desde' => substr((string) $r->permiso_fecha_inicio, 0, 10),
                        'hasta' => substr((string) $r->permiso_fecha_fin, 0, 10),
                        'fila'  => (object) [
                            'permiso_codigo'       => $r->permiso_codigo,
                            'permiso_tipo'         => $r->permiso_tipo,
                            'permiso_fecha_inicio' => $r->permiso_fecha_inicio,
                            'permiso_fecha_fin'    => $r->permiso_fecha_fin,
                            'permiso_motivo'       => $r->permiso_motivo,
                            'permiso_origen'       => $r->permiso_origen,
                        ],
                    ];
                });
        }
    }

    /** Marcaciones del estudiante en el rango: [['fecha','hora'], …]. */
    private function marcasDe(string $estCodigo, string $inicio, string $fin): array
    {
        $this->asegurarLote($estCodigo, $inicio, $fin);
        return $this->marcasCache[$inicio . '|' . $fin][$estCodigo] ?? [];
    }

    /** Atrasos cargados a mano en `asistencia_atrasos`. */
    private function manualesDe(string $estCodigo, string $inicio, string $fin): array
    {
        $this->asegurarLote($estCodigo, $inicio, $fin);
        return $this->manualesCache[$inicio . '|' . $fin][$estCodigo] ?? [];
    }

    /** Permisos activos que tocan el rango. */
    private function permisosDe(string $estCodigo, string $inicio, string $fin): array
    {
        $this->asegurarLote($estCodigo, $inicio, $fin);
        return $this->permisosCache[$inicio . '|' . $fin][$estCodigo] ?? [];
    }

    /**
     * 'HH:MM:SS' a partir de un TIME, un DATETIME o un 'HH:MM' suelto.
     * Equivale al TIME() de MySQL que usaban las consultas anteriores.
     */
    private static function soloHora($v): string
    {
        if ($v instanceof \DateTimeInterface) return $v->format('H:i:s');
        $s = (string) $v;
        if (strlen($s) > 8 && strpos($s, ' ') !== false) $s = substr($s, 11);
        $s = substr($s, 0, 8);
        return strlen($s) === 5 ? $s . ':00' : $s;
    }

    /** ¿Es lunes a viernes? Equivale a DAYOFWEEK(...) BETWEEN 2 AND 6. */
    private static function esHabil(string $fecha): bool
    {
        $d = (int) date('N', strtotime($fecha));
        return $d >= 1 && $d <= 5;
    }

    /**
     * Fechas de receso (sin clases) dentro del rango. Los recesos aplican a todo
     * el colegio, así que no dependen del estudiante y se cachean por rango.
     */
    private function fechasSinClases(string $inicio, string $fin)
    {
        $key = $inicio . '|' . $fin;
        return $this->sinClasesCache[$key]
            ??= $this->horarios->fechasSinClases($inicio, $fin);
    }

    /**
     * Devuelve el rango horario válido del TURNO MAÑANA y el límite de tolerancia
     * para el curso del estudiante, leídos de `asistencia_configuracion`.
     * Prioridad: config específica del curso > config global por categoría (cur_nivel).
     * Solo considera configs con turno = 'Mañana'.
     *
     * @return array{desde:string,hasta:string,tolerancia:string|null}
     */
    private function configTurnoManana(string $estCodigo): array
    {
        $estudiante = $this->estudiante($estCodigo);
        if (!$estudiante) {
            return ['desde' => self::FALLBACK_DESDE, 'hasta' => self::FALLBACK_HASTA, 'tolerancia' => null];
        }
        $cur = $estudiante->cur_codigo;
        $cacheKey = $cur . '|' . $this->turno;
        if (isset($this->configCache[$cacheKey])) return $this->configCache[$cacheKey];

        $curNivel = $this->nivelCache[$cur]
            ??= DB::table('colegio_cursos')->where('cur_codigo', $cur)->value('cur_nivel');

        // Vínculo principal: pivot asistencia_configuracion_cursos (config ↔ cursos).
        // Se resuelve aparte para evitar "Illegal mix of collations" en el join.
        $pivotConfigIds = DB::table('asistencia_configuracion_cursos')
            ->whereRaw('cur_codigo COLLATE utf8mb4_general_ci = ?', [$cur])
            ->pluck('config_id')
            ->map(fn($id) => (int) $id)
            ->all();
        $pivotList = empty($pivotConfigIds) ? '0' : implode(',', $pivotConfigIds);

        $cfg = DB::table('asistencia_configuracion')
            ->where('config_estado', 1)
            ->where('config_turno', $this->turno)
            ->where(function ($q) use ($cur, $curNivel, $pivotConfigIds) {
                // (1) vínculo explícito por pivot
                if (!empty($pivotConfigIds)) $q->orWhereIn('config_id', $pivotConfigIds);
                // (2) config de un solo curso (columna cur_codigo)
                $q->orWhere('cur_codigo', $cur);
                // (3) config "Todos" que aplica por categoría = nivel del curso
                if ($curNivel) $q->orWhere(function ($qq) use ($curNivel) {
                    $qq->whereNull('cur_codigo')->where('config_categoria', $curNivel);
                });
            })
            // Prioridad: pivot explícito > curso puntual > categoría/"Todos".
            ->orderByRaw("CASE WHEN config_id IN ($pivotList) THEN 0 WHEN cur_codigo IS NOT NULL THEN 1 ELSE 2 END")
            ->first();

        if ($cfg) {
            // 'desde' capta llegadas tempranas antes de hora_entrada; para Mañana arranca 07:00,
            // para otros turnos (Tarde) arranca 2h antes de la hora de entrada para no mezclar
            // marcaciones del turno mañana. 'hasta' = hora_salida corta el otro turno.
            $desde = ($this->turno === 'Mañana')
                ? self::FALLBACK_DESDE
                : date('H:i:s', strtotime(substr($cfg->hora_entrada, 0, 8)) - 2 * 3600);
            $resultado = [
                'desde'      => $desde,
                'hasta'      => substr($cfg->hora_salida, 0, 8),
                'tolerancia' => $cfg->tolerancia_atraso ?: null,
                'aplica'     => true,
            ];
        } elseif ($this->turno === 'Mañana') {
            // Turno por defecto: si no hay config, se asume jornada de mañana (compatibilidad).
            $resultado = ['desde' => self::FALLBACK_DESDE, 'hasta' => self::FALLBACK_HASTA, 'tolerancia' => null, 'aplica' => true];
        } else {
            // El curso NO tiene configuración para este turno → no pertenece a él.
            // Ventana imposible para que no capture marcaciones de otro turno.
            $resultado = ['desde' => '23:59:59', 'hasta' => '00:00:00', 'tolerancia' => null, 'aplica' => false];
        }

        // La categoría (cur_nivel) es la clave con la que los horarios especiales
        // distinguen INICIAL de PRIMARIA/SECUNDARIA dentro de un mismo rango.
        $resultado['categoria'] = $curNivel;

        return $this->configCache[$cacheKey] = $resultado;
    }

    /**
     * ¿El turno de esta instancia aplica al curso del estudiante?
     * (false = el curso no tiene configuración de horario para este turno).
     */
    public function turnoAplica(string $estCodigo): bool
    {
        return $this->configTurnoManana($estCodigo)['aplica'];
    }

    /**
     * Resumen para un rango aislado (sin dedup con otros rangos).
     */
    public function resumen(string $estCodigo, string $inicio, string $fin): array
    {
        // Si el turno seleccionado no aplica al curso del estudiante, no hay datos en ese turno.
        if (!$this->configTurnoManana($estCodigo)['aplica']) {
            return $this->resumenVacio();
        }

        [$inicio, $fin] = $this->acotarRango($inicio, $fin);

        $fechasCalendario = $this->fechasHabilesCalendario($inicio, $fin);
        $calSet = $fechasCalendario->flip();
        $diasHabilesCalendario = $fechasCalendario->count();

        $diasTrabajadosCurso = $this->fechasTrabajadasCurso($inicio, $fin, $estCodigo);

        // pres y licencias se restringen al calendario para que DT+TF=TOT.
        $presFechas = $this->fechasPresencia($estCodigo, $inicio, $fin)
            ->filter(fn($f) => $calSet->has((string) $f))->values();

        $atrasosRows = $this->atrasos($estCodigo, $inicio, $fin);

        $licenciasSolicitudes = $this->permisosCantidadEnRango($estCodigo, $inicio, $fin);

        $presSet = $presFechas->flip();

        // Días con permiso EXCLUYENDO los días con presencia (si vino, no cuenta como licencia),
        // y constreñidos al calendario hábil.
        $diasConPermiso = $this->diasConPermisoCubiertos($estCodigo, $inicio, $fin, $fechasCalendario)
            ->reject(fn($f) => $presSet->has((string) $f))
            ->filter(fn($f) => $calSet->has((string) $f))
            ->values();
        $permisoSet = $diasConPermiso->flip();

        // Faltas = calendario − presencias − licencias  →  DT + TF = TOT por construcción.
        $faltas = $fechasCalendario->filter(function ($f) use ($presSet, $permisoSet) {
            $f = (string) $f;
            return !$presSet->has($f) && !$permisoSet->has($f);
        })->values();

        return [
            'dias_habiles_calendario'    => $diasHabilesCalendario,
            'dias_trabajados_curso'      => $diasTrabajadosCurso->count(),
            'dias_trabajados_efectivos'  => max(0, $diasHabilesCalendario - $faltas->count()),
            'presencias'                 => $presFechas->count(),
            'faltas'                     => $faltas->count(),
            'atrasos'                    => $atrasosRows->count(),
            'licencias_dias'             => $diasConPermiso->count(),
            'licencias_solicitudes'      => $licenciasSolicitudes,
            // detalle (colecciones)
            'fechas_presencia'           => $presFechas,
            'fechas_faltas'              => $faltas,
            'fechas_atrasos'             => $atrasosRows,
            'fechas_dias_con_permiso'    => $diasConPermiso,
        ];
    }

    /**
     * Resumen por trimestre con deduplicación global.
     * $periodos: colección de NotaPeriodo ordenada por periodo_numero.
     *
     * @return array<int, array> keyed by periodo_numero
     */
    public function resumenPorTrimestre(string $estCodigo, $periodos): array
    {
        $vistosPres     = [];
        $vistosFaltas   = [];
        $vistosAtrasos  = [];
        $vistosDiasPerm = [];
        $vistosPermiso  = [];

        $resultado = [];

        // Si el turno no aplica al curso, todos los periodos salen en cero.
        $turnoAplica = $this->configTurnoManana($estCodigo)['aplica'];

        foreach ($periodos as $periodo) {
            if (!$turnoAplica) {
                $resultado[$periodo->periodo_numero] = $this->periodoVacio($periodo);
                continue;
            }
            [$inicio, $fin] = $this->acotarRango(
                Carbon::parse($periodo->periodo_fecha_inicio)->format('Y-m-d'),
                Carbon::parse($periodo->periodo_fecha_fin)->format('Y-m-d')
            );

            $diasTrabajadosCurso = $this->fechasTrabajadasCurso($inicio, $fin, $estCodigo);
            $fechasCalendario    = $this->fechasHabilesCalendario($inicio, $fin);
            $calSet              = $fechasCalendario->flip();

            // Presencias del estudiante (constreñidas al calendario, dedup global)
            $presFechas = $this->fechasPresencia($estCodigo, $inicio, $fin)
                ->filter(fn($f) => $calSet->has((string) $f))
                ->reject(function ($f) use (&$vistosPres) {
                    $f = (string) $f;
                    if (isset($vistosPres[$f])) return true;
                    $vistosPres[$f] = true;
                    return false;
                })->values();

            // Atrasos (dedup global por fecha|hora)
            $atrasosRows = $this->atrasos($estCodigo, $inicio, $fin)
                ->reject(function ($a) use (&$vistosAtrasos) {
                    $key = (string) $a->atraso_fecha . '|' . (string) $a->atraso_hora;
                    if (isset($vistosAtrasos[$key])) return true;
                    $vistosAtrasos[$key] = true;
                    return false;
                })->values();

            // Permisos cuya fecha de INICIO cae en este periodo (dedup por permiso_codigo)
            $permisosDelPeriodo = collect($this->permisosDe($estCodigo, $inicio, $fin))
                ->filter(fn($p) => $p['desde'] >= $inicio && $p['desde'] <= $fin)
                ->map(fn($p) => $p['fila'])
                ->values()
                ->reject(function ($p) use (&$vistosPermiso) {
                    if (isset($vistosPermiso[$p->permiso_codigo])) return true;
                    $vistosPermiso[$p->permiso_codigo] = true;
                    return false;
                })->values();

            // Días con permiso dentro del periodo (constreñidos al calendario),
            // EXCLUYENDO los que también son presencia.
            $presFechasSet = $this->fechasPresencia($estCodigo, $inicio, $fin)->flip();
            $diasConPermiso = $this->diasConPermisoCubiertos($estCodigo, $inicio, $fin, $fechasCalendario)
                ->filter(fn($f) => $calSet->has((string) $f))
                ->reject(function ($f) use (&$vistosDiasPerm, $presFechasSet) {
                    $f = (string) $f;
                    if ($presFechasSet->has($f)) return true;
                    if (isset($vistosDiasPerm[$f])) return true;
                    $vistosDiasPerm[$f] = true;
                    return false;
                })->values();

            // Faltas = calendario − presencias − días con permiso  →  DT + TF = TOT.
            $faltas = $fechasCalendario->filter(function ($f) use (&$vistosFaltas, $vistosPres, $vistosDiasPerm) {
                $f = (string) $f;
                if (isset($vistosFaltas[$f])) return false;
                if (isset($vistosPres[$f]) || isset($vistosDiasPerm[$f])) return false;
                $vistosFaltas[$f] = true;
                return true;
            })->values();

            $visible    = $this->periodoVisible($periodo);
            $totCalDias = $fechasCalendario->count();

            $resultado[$periodo->periodo_numero] = [
                'periodo'                    => $periodo,
                'visible'                    => $visible,
                'rango'                      => ['inicio' => $inicio, 'fin' => $fin],
                'dias_habiles_calendario'    => $visible ? $totCalDias : 0,
                'dias_trabajados_curso'      => $visible ? $diasTrabajadosCurso->count() : 0,
                'dias_trabajados_efectivos'  => $visible ? max(0, $totCalDias - $faltas->count()) : 0,
                'presencias'                 => $visible ? $presFechas->count() : 0,
                'faltas'                     => $visible ? $faltas->count() : 0,
                'atrasos'                    => $visible ? $atrasosRows->count() : 0,
                'licencias_dias'             => $visible ? $diasConPermiso->count() : 0,
                'licencias_solicitudes'      => $visible ? $permisosDelPeriodo->count() : 0,
                'fechas_presencia'           => $visible ? $presFechas : collect(),
                'fechas_faltas'              => $visible ? $faltas : collect(),
                'fechas_atrasos'             => $visible ? $atrasosRows : collect(),
                'fechas_dias_con_permiso'    => $visible ? $diasConPermiso : collect(),
                'permisos'                   => $visible ? $permisosDelPeriodo : collect(),
            ];
        }

        return $resultado;
    }

    /**
     * Un periodo es "visible" (sus datos de asistencia/notas se muestran) si:
     *   - su fecha_fin ya pasó (today >= fecha_fin), o
     *   - tiene al menos una nota aprobada (nota_estado = 2) en ese periodo.
     *
     * Permite ocultar el 2°/3° trimestre mientras aún esté en curso y sin notas aprobadas.
     */
    public function periodoVisible($periodo): bool
    {
        $fin = Carbon::parse($periodo->periodo_fecha_fin);
        if (Carbon::today()->greaterThanOrEqualTo($fin)) return true;

        // Depende del periodo, no del estudiante: en un centralizador esto se
        // preguntaba una vez por alumno y trimestre.
        return $this->visibleCache[$periodo->periodo_id]
            ??= DB::table('colegio_notas')
                ->where('periodo_id', $periodo->periodo_id)
                ->where('nota_estado', 2)
                ->exists();
    }

    /**
     * Resumen en cero — usado cuando el turno seleccionado no aplica al curso.
     */
    private function resumenVacio(): array
    {
        return [
            'dias_habiles_calendario'    => 0,
            'dias_trabajados_curso'      => 0,
            'dias_trabajados_efectivos'  => 0,
            'presencias'                 => 0,
            'faltas'                     => 0,
            'atrasos'                    => 0,
            'licencias_dias'             => 0,
            'licencias_solicitudes'      => 0,
            'fechas_presencia'           => collect(),
            'fechas_faltas'              => collect(),
            'fechas_atrasos'             => collect(),
            'fechas_dias_con_permiso'    => collect(),
            'turno_no_aplica'            => true,
        ];
    }

    /**
     * Entrada de periodo en cero — usado cuando el turno no aplica al curso.
     */
    private function periodoVacio($periodo): array
    {
        $inicio = Carbon::parse($periodo->periodo_fecha_inicio)->format('Y-m-d');
        $fin    = Carbon::parse($periodo->periodo_fecha_fin)->format('Y-m-d');
        return [
            'periodo'                    => $periodo,
            'visible'                    => $this->periodoVisible($periodo),
            'rango'                      => ['inicio' => $inicio, 'fin' => $fin],
            'dias_habiles_calendario'    => 0,
            'dias_trabajados_curso'      => 0,
            'dias_trabajados_efectivos'  => 0,
            'presencias'                 => 0,
            'faltas'                     => 0,
            'atrasos'                    => 0,
            'licencias_dias'             => 0,
            'licencias_solicitudes'      => 0,
            'fechas_presencia'           => collect(),
            'fechas_faltas'              => collect(),
            'fechas_atrasos'             => collect(),
            'fechas_dias_con_permiso'    => collect(),
            'permisos'                   => collect(),
            'turno_no_aplica'            => true,
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    // Helpers internos
    // ────────────────────────────────────────────────────────────────────

    private function diasHabilesCalendario(string $inicio, string $fin): int
    {
        return $this->fechasHabilesCalendario($inicio, $fin)->count();
    }

    /**
     * Ningún periodo escolar dura más que esto. Recortar a un tope evita que una
     * fecha mal cargada convierta el cálculo en un recorrido de siglos.
     */
    private const MAX_DIAS_PERIODO = 550;   // ~18 meses, de sobra para un trimestre

    /**
     * Protege el cálculo de fechas imposibles.
     *
     * El 1er Trimestre llegó a estar cargado como '0026-02-02': el recorrido día
     * por día daba 521 775 días hábiles y el boletín imprimía esa cifra como
     * faltas. Los datos ya se corrigieron (upgrade_2026_09_fechas_corruptas.sql),
     * pero un tipeo nuevo no debe volver a producir un número imposible ni colgar
     * el reporte: se recorta el rango a los últimos MAX_DIAS_PERIODO días.
     *
     * @return array{0:string,1:string}
     */
    private function acotarRango(string $inicio, string $fin): array
    {
        $ini = Carbon::parse($inicio)->startOfDay();
        $end = Carbon::parse($fin)->startOfDay();

        if ($end->lt($ini)) return [$end->format('Y-m-d'), $end->format('Y-m-d')];

        if ($ini->diffInDays($end) > self::MAX_DIAS_PERIODO) {
            $ini = $end->copy()->subDays(self::MAX_DIAS_PERIODO);
        }
        return [$ini->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /**
     * Conjunto de fechas L-V del rango, descontando feriados oficiales (tipo=1)
     * y los días de receso cargados como horario especial.
     * Es la "verdad" del calendario contra la que se calculan pres/licencias/faltas
     * para que se cumpla DT + Faltas = TOT.
     *
     * Los recesos se descuentan acá para que las vacaciones no cuenten como días
     * hábiles ni, en consecuencia, generen faltas.
     */
    private function fechasHabilesCalendario(string $inicio, string $fin)
    {
        $feriados = $this->feriadosCache[$inicio . '|' . $fin]
            ??= DB::table('asistencia_fechas_festivas')
                ->where('festivo_estado', 1)
                ->where('festivo_tipo', 1)
                ->whereBetween('festivo_fecha', [$inicio, $fin])
                ->whereRaw('DAYOFWEEK(festivo_fecha) BETWEEN 2 AND 6')
                ->pluck('festivo_fecha')->map(fn($f) => (string) $f)->flip();

        $sinClases = $this->fechasSinClases($inicio, $fin);

        $cur = Carbon::parse($inicio)->copy();
        $end = Carbon::parse($fin);
        $fechas = collect();
        while ($cur <= $end) {
            $f = $cur->format('Y-m-d');
            if ($cur->isWeekday() && !$feriados->has($f) && !$sinClases->has($f)) {
                $fechas->push($f);
            }
            $cur->addDay();
        }
        return $fechas;
    }

    /**
     * Días distintos L-V con al menos un registro en colegio_asistencia
     * (independientemente del estudiante) — refleja días donde el colegio "trabajó".
     * Los días de receso se excluyen aunque existan marcaciones sueltas.
     *
     * Del estudiante sólo usa su ventana horaria, así que el resultado es el
     * mismo para todos sus compañeros: se cachea por ventana + rango. Sin eso,
     * un centralizador repetía este scan de colegio_asistencia una vez por
     * estudiante y trimestre.
     */
    private function fechasTrabajadasCurso(string $inicio, string $fin, ?string $estCodigo = null)
    {
        $win = $estCodigo
            ? $this->configTurnoManana($estCodigo)
            : ['desde' => self::FALLBACK_DESDE, 'hasta' => self::FALLBACK_HASTA];

        $key = $win['desde'] . '|' . $win['hasta'] . '|' . $inicio . '|' . $fin;
        if (isset($this->trabajadasCache[$key])) return $this->trabajadasCache[$key];

        $sinClases = $this->fechasSinClases($inicio, $fin);

        return $this->trabajadasCache[$key] = DB::table('colegio_asistencia')
            ->whereBetween('asis_fecha', [$inicio, $fin])
            ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
            ->whereRaw('TIME(asis_hora) BETWEEN ? AND ?', [$win['desde'], $win['hasta']])
            ->select('asis_fecha')->distinct()->orderBy('asis_fecha')
            ->pluck('asis_fecha')
            ->map(fn($f) => (string) $f)
            ->reject(fn($f) => $sinClases->has($f))
            ->values();
    }

    /**
     * Días distintos L-V donde el estudiante tiene al menos un registro.
     * Filtrado a L-V para que la ecuación DT = Pres + Faltas + Licencias_dias se cumpla
     * (registros de sábado/domingo no se cuentan como presencia escolar regular).
     */
    private function fechasPresencia(string $estCodigo, string $inicio, string $fin)
    {
        $win = $this->configTurnoManana($estCodigo);

        $fechas = [];
        foreach ($this->marcasDe($estCodigo, $inicio, $fin) as $m) {
            if (!self::esHabil($m['fecha'])) continue;
            if ($m['hora'] < $win['desde'] || $m['hora'] > $win['hasta']) continue;
            $fechas[$m['fecha']] = true;
        }
        ksort($fechas);
        return collect(array_keys($fechas));
    }

    /**
     * Atrasos = unión de:
     *   (a) registros manuales en `asistencia_atrasos`
     *   (b) marcaciones en `colegio_asistencia` cuya hora supera el `tolerancia_atraso`
     *       VIGENTE ESE DÍA (curso > global, pisado por el horario especial del rango).
     * Dedup por fecha: un solo atraso por día por estudiante.
     *
     * El rango se parte en tramos con `HorarioEspecialService`: cada tramo se consulta
     * con SU tolerancia y SU hora de salida, y los tramos de receso no generan nada.
     * Así, un boletín ya emitido deja de mostrar atrasos del horario de invierno sin
     * tocar una sola marcación (los atrasos se derivan al leer, no están guardados).
     */
    private function atrasos(string $estCodigo, string $inicio, string $fin)
    {
        // (a) Atrasos manuales — los que caen en receso se descartan más abajo.
        $manuales = collect($this->manualesDe($estCodigo, $inicio, $fin))
            ->filter(fn($m) => self::esHabil($m['fecha']))
            ->map(fn($m) => (object) $m)
            ->values();

        // (b) Atrasos derivados: cada tramo se evalúa con SU tolerancia y SU
        //     hora de salida, sobre las marcaciones ya traídas en bloque.
        $win = $this->configTurnoManana($estCodigo);
        $marcas = $this->marcasDe($estCodigo, $inicio, $fin);
        $derivados = collect();

        foreach ($this->ventanasAtraso($win, $inicio, $fin) as $v) {
            foreach ($marcas as $m) {
                if (!self::esHabil($m['fecha'])) continue;
                if ($m['hora'] <= $v['tolerancia'] || $m['hora'] > $v['hasta']) continue;

                foreach ($v['rangos'] as [$d, $h]) {
                    if ($m['fecha'] >= $d && $m['fecha'] <= $h) {
                        $derivados->push((object) $m);
                        break;
                    }
                }
            }
        }

        // Los días de receso no generan atrasos, ni derivados ni manuales.
        $sinClases = $this->fechasSinClases($inicio, $fin);

        // Unión + dedup por fecha (un atraso por día)
        return $manuales->concat($derivados)
            ->reject(fn($r) => $sinClases->has((string) $r->fecha))
            ->unique(fn($r) => (string) $r->fecha)
            ->sortBy('fecha')
            ->map(fn($r) => (object) ['atraso_fecha' => $r->fecha, 'atraso_hora' => $r->hora])
            ->values();
    }

    /**
     * Ventanas [tolerancia, hasta] con los rangos de fecha en que rige cada una.
     *
     * Para los tramos normales se usa la configuración resuelta por
     * `configTurnoManana()` —que además de la categoría considera el pivot y la
     * config puntual del curso—, y sólo los tramos de horario especial traen sus
     * propias horas. Así, sin horarios especiales cargados, el resultado es
     * exactamente el de antes: una sola ventana sobre todo el rango.
     *
     * Tramos con la misma tolerancia y salida se agrupan en una sola consulta.
     *
     * @return array<int, array{tolerancia:string,hasta:string,rangos:array<int,array{0:string,1:string}>}>
     */
    private function ventanasAtraso(array $win, string $inicio, string $fin): array
    {
        $categoria = $win['categoria'] ?? null;

        // Sin categoría no hay con qué cruzar los horarios especiales: comportamiento previo.
        if (!$categoria) {
            return empty($win['tolerancia'])
                ? []
                : [['tolerancia' => $win['tolerancia'], 'hasta' => $win['hasta'], 'rangos' => [[$inicio, $fin]]]];
        }

        $grupos = [];
        foreach ($this->horarios->tramos($inicio, $fin, $categoria, $this->turno) as $t) {
            if ($t['tipo'] === 'receso') continue;

            if ($t['tipo'] === 'especial') {
                $tol   = $t['tolerancia'];
                $hasta = $t['salida'];
            } else {
                $tol   = $win['tolerancia'];
                $hasta = $win['hasta'];
            }
            if (empty($tol) || empty($hasta)) continue;

            $tol   = substr((string) $tol, 0, 8);
            $hasta = substr((string) $hasta, 0, 8);
            $grupos[$tol . '|' . $hasta]['tolerancia'] = $tol;
            $grupos[$tol . '|' . $hasta]['hasta']      = $hasta;
            $grupos[$tol . '|' . $hasta]['rangos'][]   = [$t['desde'], $t['hasta']];
        }
        return array_values($grupos);
    }

    private function permisosCantidadEnRango(string $estCodigo, string $inicio, string $fin): int
    {
        // Los del lote ya vienen acotados al rango; acá sólo cuentan los que
        // ADEMÁS empiezan dentro de él (una solicitud se imputa a su inicio).
        $n = 0;
        foreach ($this->permisosDe($estCodigo, $inicio, $fin) as $p) {
            if ($p['desde'] >= $inicio && $p['desde'] <= $fin) $n++;
        }
        return $n;
    }

    /**
     * Días trabajados del curso cubiertos por al menos un permiso activo,
     * considerando permisos que se traslapan aunque hayan iniciado antes.
     */
    private function diasConPermisoCubiertos(string $estCodigo, string $inicio, string $fin, $diasTrabajadosCurso)
    {
        $diasTrabSet = $diasTrabajadosCurso->flip();

        $dias = collect();
        foreach ($this->permisosDe($estCodigo, $inicio, $fin) as $p) {
            $iniP = max($p['desde'], $inicio);
            $finP = min($p['hasta'], $fin);
            $cur  = Carbon::parse($iniP);
            $end  = Carbon::parse($finP);
            while ($cur <= $end) {
                $f = $cur->format('Y-m-d');
                if ($diasTrabSet->has($f)) {
                    $dias->push($f);
                }
                $cur->addDay();
            }
        }
        return $dias->unique()->values();
    }
}
