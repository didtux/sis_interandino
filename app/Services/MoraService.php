<?php

namespace App\Services;

use App\Models\Estudiante;

/**
 * Mora de mensualidades: única fuente para caja (pantalla y PDF de mora), la
 * pre-inscripción y el portal de padres (bloqueo por mora). Antes cada uno
 * tenía su propia cuenta y daban números distintos.
 *
 * Deuda acumulada: meses impagos desde el inicio de cuotas (febrero, o
 * insc_mes_inicio en Registro Especial) hasta el mes de corte, por el monto de
 * la mensualidad de la inscripción (insc_monto_final / 10).
 */
class MoraService
{
    /** Meses de cuota del año escolar (febrero a noviembre). */
    public const MESES = [2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio',
                          8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre'];

    /**
     * Estudiantes con al menos un mes impago. Deja en cada uno:
     * mora_meses_pagados, mora_meses_pendientes, mora_mensualidad, mora_deuda.
     */
    public function enMora(int $mesCorte, int $gestion, ?string $curCodigo = null, ?array $estCodigos = null)
    {
        $query = Estudiante::visible()->with([
            'curso',
            'inscripcion' => fn($q) => $q->where('insc_gestion', $gestion)->whereIn('insc_estado', [1, 2]),
            'pagos'       => fn($q) => $q->deGestion($gestion)->where('pagos_estado', 1),
        ]);
        if (!empty($curCodigo)) $query->where('cur_codigo', $curCodigo);
        if ($estCodigos !== null) $query->whereIn('est_codigo', $estCodigos);

        return $query->orderBy('cur_codigo')->orderBy('est_apellidos')->get()
            ->filter(fn($est) => $this->calcular($est, $mesCorte) > 0);
    }

    /** Mora de un estudiante a hoy (mes_de_gestion), o null si está al día. */
    public function deEstudiante(string $estCodigo, ?int $gestion = null): ?Estudiante
    {
        return $this->enMora(mes_de_gestion(), $gestion ?? gestion_actual(), null, [$estCodigo])->first();
    }

    /** Calcula y deja los datos de mora en el estudiante; devuelve los meses impagos. */
    public function calcular(Estudiante $est, int $mesCorte): int
    {
        // Sin inscripción activa no se le puede reclamar mensualidad.
        if (!$est->inscripcion) return 0;

        $mesInicio = 2;
        if (($est->inscripcion->insc_caso_especial ?? 0) == 1) {
            $mesInicio = (int) ($est->inscripcion->insc_mes_inicio ?? 2);
        }

        $mesesPagados = [];
        foreach ($est->pagos as $pago) {
            $mesesPagados = array_merge($mesesPagados, $pago->meses_cubiertos);
        }
        $mesesPagados = array_values(array_unique($mesesPagados));
        sort($mesesPagados);

        $pendientes = [];
        for ($m = $mesInicio; $m <= min($mesCorte, 11); $m++) {
            if (!in_array($m, $mesesPagados)) $pendientes[] = $m;
        }

        $mensualidad = $est->inscripcion->insc_monto_final / 10;
        $est->mora_meses_pagados    = $mesesPagados;
        $est->mora_meses_pendientes = $pendientes;
        $est->mora_mensualidad      = $mensualidad;
        $est->mora_deuda            = $mensualidad * count($pendientes);

        return count($pendientes);
    }
}
