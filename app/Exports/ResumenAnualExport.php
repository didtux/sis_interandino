<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenAnualExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $estudiantes;
    protected $year;

    public function __construct($estudiantes, $year)
    {
        $this->estudiantes = $estudiantes;
        $this->year = $year;
    }

    public function collection()
    {
        $data = collect();
        $cursoActual = null;
        $contador = 1;

        foreach ($this->estudiantes as $estudiante) {
            if ($cursoActual != $estudiante->cur_codigo) {
                $cursoActual = $estudiante->cur_codigo;
                $contador = 1;
                $data->push([
                    'curso' => $estudiante->curso->cur_nombre ?? 'Sin curso',
                    '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
                ]);
            }

            $pagosPorMes = [];
            foreach ($estudiante->pagos as $pago) {
                // Pago múltiple: dividir entre 10 meses (feb-nov)
                if ($pago->es_pago_multiple_meses) {
                    $montoPorMes = $pago->pagos_precio / 10;
                    for ($m = 2; $m <= 11; $m++) {
                        if (!isset($pagosPorMes[$m])) {
                            $pagosPorMes[$m] = ['factura' => '', 'monto' => 0];
                        }
                        $pagosPorMes[$m]['factura'] = $pago->men_codigo;
                        $pagosPorMes[$m]['monto'] += $montoPorMes;
                    }
                } else {
                    $mes = $pago->mes_correspondiente;
                    if ($mes >= 2 && $mes <= 11) {
                        if (!isset($pagosPorMes[$mes])) {
                            $pagosPorMes[$mes] = ['factura' => '', 'monto' => 0];
                        }
                        $pagosPorMes[$mes]['factura'] = $pago->men_codigo;
                        $pagosPorMes[$mes]['monto'] += $pago->pagos_precio;
                    }
                }
            }

            $row = [
                $contador++,
                $estudiante->est_apellidos . ' ' . $estudiante->est_nombres
            ];

            for ($mes = 2; $mes <= 11; $mes++) {
                $row[] = $pagosPorMes[$mes]['factura'] ?? '';
                $row[] = isset($pagosPorMes[$mes]) ? $pagosPorMes[$mes]['monto'] : '';
            }

            $row[] = array_sum(array_column($pagosPorMes, 'monto'));
            $data->push($row);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['RESUMEN ANUAL DE MENSUALIDADES ' . $this->year],
            ['U.E. INTERANDINO'],
            [],
            [
                'N°', 'ESTUDIANTE',
                'FEB-F', 'FEB-Bs', 'MAR-F', 'MAR-Bs', 'ABR-F', 'ABR-Bs',
                'MAY-F', 'MAY-Bs', 'JUN-F', 'JUN-Bs', 'JUL-F', 'JUL-Bs',
                'AGO-F', 'AGO-Bs', 'SEP-F', 'SEP-Bs', 'OCT-F', 'OCT-Bs',
                'NOV-F', 'NOV-Bs', 'TOTAL'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'CCCCCC']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 30,
            'C' => 12, 'D' => 8,
            'E' => 12, 'F' => 8,
            'G' => 12, 'H' => 8,
            'I' => 12, 'J' => 8,
            'K' => 12, 'L' => 8,
            'M' => 12, 'N' => 8,
            'O' => 12, 'P' => 8,
            'Q' => 12, 'R' => 8,
            'S' => 12, 'T' => 8,
            'U' => 12, 'V' => 8,
            'W' => 12,
        ];
    }
}
