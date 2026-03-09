<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsistenciasAnualExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $curso;
    protected $year;

    public function __construct($data, $curso, $year)
    {
        $this->data = $data;
        $this->curso = $curso;
        $this->year = $year;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ESTUDIANTE',
            'T1 D.T.', 'T1 T.L.', 'T1 T.F.', 'T1 T.A.', 'T1 TOTAL',
            'T2 D.T.', 'T2 T.L.', 'T2 T.F.', 'T2 T.A.', 'T2 TOTAL',
            'T3 D.T.', 'T3 T.L.', 'T3 T.F.', 'T3 T.A.', 'T3 TOTAL',
            'TOTAL D.T.', 'TOTAL T.L.', 'TOTAL T.F.', 'TOTAL T.A.', 'TOTAL ANUAL'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4CAF50']]],
        ];
    }
    
    public function title(): string
    {
        return 'Asistencia ' . $this->year;
    }
}
