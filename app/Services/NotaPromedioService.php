<?php

namespace App\Services;

/**
 * Fórmula ÚNICA del promedio trimestral.
 *
 * Existe para que la carga manual (NotaController::guardar) y la importación de
 * Excel (NotaController::importarConfirmar) no puedan volver a divergir de lo que
 * el docente ve en pantalla (resources/views/notas/calificar.blade.php).
 *
 * Regla oficial del colegio: el promedio de CADA dimensión se redondea a entero
 * (topado a dimension_valor_max) y el PROM. TRIM. es la suma de esos enteros.
 * El decimal exacto se conserva aparte y se usa sólo en el cuadro de honor.
 */
class NotaPromedioService
{
    /**
     * Promedio de una sola dimensión a partir de los valores de sus columnas.
     * Replica exactamente calcularFila() del blade: si la dimensión tiene una
     * única columna el valor es directo; si tiene varias, promedia sólo las
     * columnas con nota (> 0).
     *
     * @param  array $valores  [columna_num => valor]
     * @return float           promedio SIN redondear, ya topado al máximo
     */
    public function promedioDimension($dim, array $valores): float
    {
        $suma = 0.0;
        $count = 0;

        for ($col = 1; $col <= $dim->dimension_columnas; $col++) {
            $val = $valores[$col] ?? null;
            if ($val !== null && $val !== '' && (float) $val > 0) {
                $suma += (float) $val;
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        $prom = $dim->dimension_columnas == 1 ? $suma : $suma / $count;

        // Tope por dimensión — la pantalla lo aplica y el servidor no lo hacía.
        return (float) min($prom, (float) $dim->dimension_valor_max);
    }

    /**
     * Calcula el promedio trimestral completo.
     *
     * @param  iterable $dimensiones     colección de NotaDimension
     * @param  array    $valoresPorDim   [dimension_id => [columna_num => valor]]
     * @return array{oficial:int, decimal:float, por_dimension:array<int,int>}
     */
    public function calcular($dimensiones, array $valoresPorDim): array
    {
        $oficial = 0;
        $decimal = 0.0;
        $porDimension = [];

        foreach ($dimensiones as $dim) {
            $valores = $valoresPorDim[$dim->dimension_id] ?? [];
            $prom    = $this->promedioDimension($dim, $valores);

            $promRedondeado = (int) round($prom);

            $porDimension[$dim->dimension_id] = $promRedondeado;
            $oficial += $promRedondeado;   // ← suma de enteros = lo que ve el docente
            $decimal += $prom;             // ← suma exacta, sólo para cuadro de honor
        }

        return [
            'oficial'       => $oficial,
            'decimal'       => round($decimal, 2),
            'por_dimension' => $porDimension,
        ];
    }

    /**
     * Rango de valoración del sistema boliviano, calculado SIEMPRE sobre el
     * entero oficial para que la etiqueta coincida con el número impreso.
     */
    public function rango($promedioOficial): string
    {
        $pt = (int) round($promedioOficial);

        if ($pt < 20) return '';
        if ($pt < 51) return 'ED';
        if ($pt < 67) return 'DA';
        if ($pt < 85) return 'DO';
        return 'DP';
    }
}
