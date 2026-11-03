<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Entrega el siguiente número de recibo de una serie sin repetir.
 *
 * Antes se calculaba MAX(código)+1 al momento de guardar: dos cobros
 * simultáneos leían el mismo máximo y salían con el mismo número. Acá el
 * contador de la serie se bloquea (SELECT ... FOR UPDATE) hasta que termina la
 * transacción del cobro, así que el segundo espera al primero.
 *
 * Debe llamarse DENTRO de la transacción que guarda el pago: si el cobro falla
 * y se revierte, el número también vuelve y no quedan saltos.
 */
class ReciboNumerador
{
    public static function siguiente(string $serie): string
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn() => self::siguiente($serie));
        }

        $sec = DB::table('recibo_secuencias')->where('serie', $serie)->lockForUpdate()->first();
        if (!$sec) {
            throw new \RuntimeException("No existe la serie de recibos {$serie} (falta upgrade_2026_11_recibo_secuencias.sql).");
        }

        $numero = $sec->ultimo + 1;
        DB::table('recibo_secuencias')->where('serie', $serie)->update(['ultimo' => $numero]);

        return $sec->prefijo . str_pad($numero, $sec->digitos, '0', STR_PAD_LEFT);
    }
}
