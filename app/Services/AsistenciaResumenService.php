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
     * Resumen para un rango aislado (sin dedup con otros rangos).
     */
    public function resumen(string $estCodigo, string $inicio, string $fin): array
    {
        $diasHabilesCalendario = $this->diasHabilesCalendario($inicio, $fin);

        $diasTrabajadosCurso = $this->fechasTrabajadasCurso($inicio, $fin);

        $presFechas = $this->fechasPresencia($estCodigo, $inicio, $fin);

        $atrasosRows = $this->atrasos($estCodigo, $inicio, $fin);

        $licenciasSolicitudes = $this->permisosCantidadEnRango($estCodigo, $inicio, $fin);

        $presSet = $presFechas->flip();

        // Días con permiso EXCLUYENDO los días con presencia (si vino, no cuenta como licencia).
        $diasConPermiso = $this->diasConPermisoCubiertos($estCodigo, $inicio, $fin, $diasTrabajadosCurso)
            ->reject(fn($f) => $presSet->has((string) $f))->values();
        $permisoSet = $diasConPermiso->flip();

        $faltas = $diasTrabajadosCurso->filter(function ($f) use ($presSet, $permisoSet) {
            $f = (string) $f;
            if ($presSet->has($f) || $permisoSet->has($f)) return false;
            $dow = (int) date('w', strtotime($f));
            return $dow !== 0 && $dow !== 6;
        })->values();

        return [
            'dias_habiles_calendario'    => $diasHabilesCalendario,
            'dias_trabajados_curso'      => $diasTrabajadosCurso->count(),
            'dias_trabajados_efectivos'  => max(0, $diasTrabajadosCurso->count() - $faltas->count()),
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

        foreach ($periodos as $periodo) {
            $inicio = Carbon::parse($periodo->periodo_fecha_inicio)->format('Y-m-d');
            $fin    = Carbon::parse($periodo->periodo_fecha_fin)->format('Y-m-d');

            $diasTrabajadosCurso = $this->fechasTrabajadasCurso($inicio, $fin);

            // Presencias del estudiante (dedup global)
            $presFechas = $this->fechasPresencia($estCodigo, $inicio, $fin)
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
            $permisosDelPeriodo = DB::table('asistencia_permisos')
                ->where('estud_codigo', $estCodigo)
                ->where('permiso_estado', 1)
                ->whereBetween('permiso_fecha_inicio', [$inicio, $fin])
                ->orderBy('permiso_fecha_inicio')
                ->select('permiso_codigo', 'permiso_tipo', 'permiso_fecha_inicio',
                         'permiso_fecha_fin', 'permiso_motivo', 'permiso_origen')
                ->get()
                ->reject(function ($p) use (&$vistosPermiso) {
                    if (isset($vistosPermiso[$p->permiso_codigo])) return true;
                    $vistosPermiso[$p->permiso_codigo] = true;
                    return false;
                })->values();

            // Días con permiso dentro del periodo, EXCLUYENDO los que también son presencia
            // (si el estudiante vino ese día no cuenta como licencia).
            $presFechasSet = $this->fechasPresencia($estCodigo, $inicio, $fin)->flip();
            $diasConPermiso = $this->diasConPermisoCubiertos($estCodigo, $inicio, $fin, $diasTrabajadosCurso)
                ->reject(function ($f) use (&$vistosDiasPerm, $presFechasSet) {
                    $f = (string) $f;
                    if ($presFechasSet->has($f)) return true;
                    if (isset($vistosDiasPerm[$f])) return true;
                    $vistosDiasPerm[$f] = true;
                    return false;
                })->values();

            // Faltas = días trabajados del curso − presencias − días con permiso (global dedup)
            $faltas = $diasTrabajadosCurso->filter(function ($f) use (&$vistosFaltas, $vistosPres, $vistosDiasPerm) {
                $f = (string) $f;
                if (isset($vistosFaltas[$f])) return false;
                if (isset($vistosPres[$f]) || isset($vistosDiasPerm[$f])) return false;
                $dow = (int) date('w', strtotime($f));
                if ($dow === 0 || $dow === 6) return false;
                $vistosFaltas[$f] = true;
                return true;
            })->values();

            $visible = $this->periodoVisible($periodo);

            $resultado[$periodo->periodo_numero] = [
                'periodo'                    => $periodo,
                'visible'                    => $visible,
                'rango'                      => ['inicio' => $inicio, 'fin' => $fin],
                'dias_habiles_calendario'    => $visible ? $this->diasHabilesCalendario($inicio, $fin) : 0,
                'dias_trabajados_curso'      => $visible ? $diasTrabajadosCurso->count() : 0,
                'dias_trabajados_efectivos'  => $visible ? max(0, $diasTrabajadosCurso->count() - $faltas->count()) : 0,
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

        $hayAprobadas = DB::table('colegio_notas')
            ->where('periodo_id', $periodo->periodo_id)
            ->where('nota_estado', 2)
            ->exists();
        return $hayAprobadas;
    }

    // ────────────────────────────────────────────────────────────────────
    // Helpers internos
    // ────────────────────────────────────────────────────────────────────

    private function diasHabilesCalendario(string $inicio, string $fin): int
    {
        $cur = Carbon::parse($inicio)->copy();
        $end = Carbon::parse($fin);
        $dias = 0;
        while ($cur <= $end) {
            if ($cur->isWeekday()) $dias++;
            $cur->addDay();
        }
        return $dias;
    }

    /**
     * Días distintos L-V con al menos un registro en colegio_asistencia
     * (independientemente del estudiante) — refleja días donde el curso "trabajó".
     */
    private function fechasTrabajadasCurso(string $inicio, string $fin)
    {
        return DB::table('colegio_asistencia')
            ->whereBetween('asis_fecha', [$inicio, $fin])
            ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
            ->select('asis_fecha')->distinct()->orderBy('asis_fecha')
            ->pluck('asis_fecha')
            ->map(fn($f) => (string) $f);
    }

    /**
     * Días distintos L-V donde el estudiante tiene al menos un registro.
     * Filtrado a L-V para que la ecuación DT = Pres + Faltas + Licencias_dias se cumpla
     * (registros de sábado/domingo no se cuentan como presencia escolar regular).
     */
    private function fechasPresencia(string $estCodigo, string $inicio, string $fin)
    {
        return DB::table('colegio_asistencia')
            ->where('estud_codigo', $estCodigo)
            ->whereBetween('asis_fecha', [$inicio, $fin])
            ->whereRaw('DAYOFWEEK(asis_fecha) BETWEEN 2 AND 6')
            ->select('asis_fecha')->distinct()->orderBy('asis_fecha')
            ->pluck('asis_fecha')
            ->map(fn($f) => (string) $f);
    }

    private function atrasos(string $estCodigo, string $inicio, string $fin)
    {
        return DB::table('asistencia_atrasos')
            ->where('estud_codigo', $estCodigo)
            ->whereBetween('atraso_fecha', [$inicio, $fin])
            ->whereRaw('DAYOFWEEK(atraso_fecha) BETWEEN 2 AND 6')
            ->orderBy('atraso_fecha')
            ->select('atraso_fecha', 'atraso_hora')
            ->get();
    }

    private function permisosCantidadEnRango(string $estCodigo, string $inicio, string $fin): int
    {
        return DB::table('asistencia_permisos')
            ->where('estud_codigo', $estCodigo)
            ->where('permiso_estado', 1)
            ->whereBetween('permiso_fecha_inicio', [$inicio, $fin])
            ->count();
    }

    /**
     * Días trabajados del curso cubiertos por al menos un permiso activo,
     * considerando permisos que se traslapan aunque hayan iniciado antes.
     */
    private function diasConPermisoCubiertos(string $estCodigo, string $inicio, string $fin, $diasTrabajadosCurso)
    {
        $diasTrabSet = $diasTrabajadosCurso->flip();

        $permisos = DB::table('asistencia_permisos')
            ->where('estud_codigo', $estCodigo)
            ->where('permiso_estado', 1)
            ->where('permiso_fecha_inicio', '<=', $fin)
            ->where('permiso_fecha_fin', '>=', $inicio)
            ->select('permiso_fecha_inicio', 'permiso_fecha_fin')
            ->get();

        $dias = collect();
        foreach ($permisos as $p) {
            $iniP = max((string) $p->permiso_fecha_inicio, $inicio);
            $finP = min((string) $p->permiso_fecha_fin, $fin);
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
