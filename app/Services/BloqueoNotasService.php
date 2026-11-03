<?php

namespace App\Services;

use App\Models\BloqueoNotas;

/**
 * ¿El padre puede ver las notas de este hijo? (parámetros del colegio)
 *
 *  - bloqueo_mora_activo = 0  → nunca se bloquea.
 *  - Exoneración manual vigente → se ve (manda sobre todo lo demás).
 *  - Bloqueo manual vigente (modo MANUAL o AMBOS) → bloqueado.
 *  - Automático (modo AUTO o AMBOS): desde el mes bloqueo_desde_mes, con
 *    bloqueo_meses_minimo o más mensualidades adeudadas → bloqueado.
 *
 * La deuda sale de MoraService, la misma cuenta que ve caja.
 */
class BloqueoNotasService
{
    /** @return array{bloqueado:bool, motivo:?string, meses:array, deuda:float} */
    public function estado(string $estCodigo, ?int $gestion = null): array
    {
        $gestion = $gestion ?? gestion_actual();
        $libre = ['bloqueado' => false, 'motivo' => null, 'meses' => [], 'deuda' => 0.0];
        if ((string) parametro('bloqueo_mora_activo', '0') !== '1') return $libre;

        $manual = BloqueoNotas::vigentes()->where('est_codigo', $estCodigo)->where('ges_anio', $gestion)
            ->orderByDesc('bloq_id')->get();
        if ($manual->firstWhere('bloq_tipo', 'EXONERAR')) return $libre;

        $modo = parametro('bloqueo_mora_modo', 'AMBOS');
        $mora = (new MoraService())->deEstudiante($estCodigo, $gestion);
        $meses = $mora->mora_meses_pendientes ?? [];
        $deuda = (float) ($mora->mora_deuda ?? 0);

        if (in_array($modo, ['MANUAL', 'AMBOS']) && ($b = $manual->firstWhere('bloq_tipo', 'BLOQUEAR'))) {
            return ['bloqueado' => true, 'motivo' => $b->bloq_motivo, 'meses' => $meses, 'deuda' => $deuda];
        }
        if (in_array($modo, ['AUTO', 'AMBOS'])
            && mes_de_gestion() >= (int) parametro('bloqueo_desde_mes', 3)
            && count($meses) >= (int) parametro('bloqueo_meses_minimo', 2)) {
            return ['bloqueado' => true, 'motivo' => count($meses) . ' mensualidades pendientes', 'meses' => $meses, 'deuda' => $deuda];
        }
        return $libre + ['meses' => $meses, 'deuda' => $deuda];
    }
}
