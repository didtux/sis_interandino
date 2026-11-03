<?php

/*
 * Funciones globales del sistema. Se cargan desde AppServiceProvider::register
 * (no desde composer.json) para que un despliegue sin `composer dump-autoload`
 * no deje al sistema sin ellas.
 */

if (!function_exists('gestion_actual')) {
    /** Gestión escolar vigente. Reemplaza a date('Y') donde significa "año escolar". */
    function gestion_actual(): int
    {
        return \App\Support\GestionActual::vigente();
    }
}

if (!function_exists('parametro')) {
    /**
     * Parámetro del colegio (tabla sistema_parametros): decisiones que no
     * corresponde fijar en el código, como cuántas materias reprobadas hacen
     * repetir el año o qué porcentaje de la reserva se retiene al devolver.
     */
    function parametro(string $clave, $defecto = null, bool $olvidar = false)
    {
        static $cache = null;
        if ($olvidar) { $cache = null; return null; } // parametro('', null, true) vacía la caché
        if ($cache === null) {
            try {
                $cache = \Illuminate\Support\Facades\DB::table('sistema_parametros')->pluck('param_valor', 'param_clave')->all();
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
        return array_key_exists($clave, $cache) && $cache[$clave] !== null && $cache[$clave] !== '' ? $cache[$clave] : $defecto;
    }
}

if (!function_exists('monto_literal')) {
    /** "Mil doscientos cincuenta 00/100 Bolivianos" (hasta 999.999.999). */
    function monto_literal(float $monto): string
    {
        $entero = (int) floor(round($monto, 2));
        $centavos = (int) round((round($monto, 2) - $entero) * 100);
        $txt = $entero === 0 ? 'cero' : numero_en_letras($entero);
        return mb_strtoupper(mb_substr($txt, 0, 1)) . mb_substr($txt, 1) . ' ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100 Bolivianos';
    }
}

if (!function_exists('numero_en_letras')) {
    function numero_en_letras(int $n): string
    {
        $u = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce',
              'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
              'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete',
              'veintiocho', 'veintinueve'];
        $d = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $c = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos',
              'setecientos', 'ochocientos', 'novecientos'];

        if ($n >= 1000000) {
            $m = intdiv($n, 1000000);
            $resto = $n % 1000000;
            return trim(($m === 1 ? 'un millón' : numero_en_letras($m) . ' millones') . ($resto ? ' ' . numero_en_letras($resto) : ''));
        }
        if ($n >= 1000) {
            $m = intdiv($n, 1000);
            $resto = $n % 1000;
            $miles = $m === 1 ? 'mil' : preg_replace(['/veintiuno$/u', '/uno$/u'], ['veintiún', 'un'], numero_en_letras($m)) . ' mil';
            return trim($miles . ($resto ? ' ' . numero_en_letras($resto) : ''));
        }
        if ($n >= 100) {
            if ($n === 100) return 'cien';
            return trim($c[intdiv($n, 100)] . ' ' . numero_en_letras($n % 100));
        }
        if ($n < 30) return $u[$n];
        return $d[intdiv($n, 10)] . ($n % 10 ? ' y ' . $u[$n % 10] : '');
    }
}

if (!function_exists('mes_de_gestion')) {
    /**
     * Mes "actual" dentro de la gestión vigente, para calcular mora y cuotas
     * vencidas. En enero de 2027 con 2026 todavía vigente, date('n') daba 1 y
     * nadie debía nada; acá la gestión ya terminó y se toman los 12 meses.
     */
    function mes_de_gestion(): int
    {
        $hoy = now()->year;
        $gestion = gestion_actual();
        if ($hoy > $gestion) return 12;
        if ($hoy < $gestion) return 1;
        return now()->month;
    }
}

if (!function_exists('landing')) {
    /** Texto editable de la web pública, listo para {{ }} (ver config/landing.php). */
    function landing(string $clave): \Illuminate\Support\HtmlString
    {
        return \App\Support\Landing::html($clave);
    }
}
