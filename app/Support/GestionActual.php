<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Gestión (año escolar) con la que trabaja el sistema.
 *
 * Antes salía de date('Y'): el 1 de enero el sistema cambiaba de año solo,
 * aunque las clases de la gestión nueva empezaran en febrero y caja todavía
 * tuviera que cobrar deudas del año anterior. Ahora la decide el colegio desde
 * Parametrización → Gestiones ("Activar"); si no hay ninguna marcada, se cae al
 * año del reloj para que nada se rompa.
 */
class GestionActual
{
    private static ?int $vigente = null;
    private static $abierta = false; // false = no calculado; null = ninguna

    /** Gestión académica vigente (colegio_gestiones.ges_estado = 1). */
    public static function vigente(): int
    {
        if (self::$vigente !== null) return self::$vigente;
        try {
            $anio = DB::table('colegio_gestiones')->where('ges_estado', 1)->value('ges_anio');
        } catch (\Throwable $e) {
            $anio = null; // tabla ausente (instalación sin migrar)
        }
        return self::$vigente = $anio ? (int) $anio : (int) date('Y');
    }

    /**
     * Gestión con la inscripción abierta que NO es la vigente (p.ej. 2027 en
     * noviembre de 2026), o null si no hay ninguna.
     */
    public static function inscripcionAbierta(): ?int
    {
        if (self::$abierta !== false) return self::$abierta;
        try {
            $anio = DB::table('colegio_gestiones')
                ->where('ges_inscripcion_abierta', 1)
                ->where('ges_anio', '>', self::vigente())
                ->orderBy('ges_anio')->value('ges_anio');
        } catch (\Throwable $e) {
            $anio = null;
        }
        return self::$abierta = $anio ? (int) $anio : null;
    }

    /** La gestión anterior a la vigente (para cobrar saldos pendientes). */
    public static function anterior(): int
    {
        return self::vigente() - 1;
    }

    /** Vacía la caché (después de activar otra gestión, y en pruebas). */
    public static function olvidar(): void
    {
        self::$vigente = null;
        self::$abierta = false;
    }
}
