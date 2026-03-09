<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsistenciasTrimestralExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $curso;
    protected $trimestre;
    protected $meses;

    public function __construct($data, $curso, $trimestre, $meses)
    {
        $this->data = $data;
        $this->curso = $curso;
        $this->trimestre = $trimestre;
        $this->meses = $meses;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headers = ['ESTUDIANTE'];
        
        foreach ($this->meses as $mes) {
            $headers[] = $mes . ' D.T.';
            $headers[] = $mes . ' T.L.';
            $headers[] = $mes . ' T.F.';
            $headers[] = $mes . ' T.A.';
            $headers[] = $mes . ' TOTAL';
        }
        
        $headers[] = 'TOTAL D.T.';
        $headers[] = 'TOTAL T.L.';
        $headers[] = 'TOTAL T.F.';
        $headers[] = 'TOTAL T.A.';
        $headers[] = 'TOTAL TRIMESTRE';
        
        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4CAF50']]],
        ];
    }
    
    public function title(): string
    {
        return 'Trimestre ' . $this->trimestre;
    }
}
