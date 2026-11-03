<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// WithStrictNullComparison: sin esto Maatwebsite escribe los 0 como celda vacía.
class AsistenciasTrimestralExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithStrictNullComparison
{
    protected $data;
    protected $curso;
    protected $trimestre;
    protected $meses;
    protected $turno;

    public function __construct($data, $curso, $trimestre, $meses, $turno = 'Mañana')
    {
        $this->data = $data;
        $this->curso = $curso;
        $this->trimestre = $trimestre;
        $this->meses = $meses;
        $this->turno = $turno;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        // "N°" faltaba: los datos traen número de lista + nombre y todos los
        // encabezados quedaban corridos una columna.
        $headers = ['N°', 'ESTUDIANTE'];
        
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
        return mb_substr('Trim ' . $this->trimestre . ' - ' . $this->turno, 0, 31);
    }
}
