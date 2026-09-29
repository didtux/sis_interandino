<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Listado de estudiantes en Excel.
 *
 * Reemplaza al CSV que devolvía EstudianteController::listadoExcel: se
 * anunciaba como Excel pero era un .csv separado por comas, y Excel en es-BO
 * espera punto y coma, así que el archivo caía entero en una sola columna.
 *
 * Los retirados salen en rojo, igual que en los PDF de notas y asistencia.
 */
class EstudiantesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    /** @var \Illuminate\Support\Collection */
    protected $estudiantes;

    /** @var \Illuminate\Support\Collection numero de lista por est_codigo */
    protected $lista;

    /** @var string */
    protected $titulo;

    public function __construct($estudiantes, $lista, string $titulo = 'Estudiantes')
    {
        $this->estudiantes = $estudiantes;
        $this->lista       = $lista;
        $this->titulo      = $titulo;
    }

    public function collection()
    {
        return $this->estudiantes;
    }

    public function title(): string
    {
        // Excel rechaza estos caracteres en el nombre de la hoja.
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $this->titulo), 0, 31);
    }

    public function headings(): array
    {
        return [
            '#', 'Código', 'Apellidos', 'Nombres', 'CI', 'Curso', 'Sexo',
            'Fecha Nac.', 'Teléfono', 'Padre / Tutor', 'Tel. Padre', 'Estado',
        ];
    }

    public function map($e): array
    {
        $padre = $e->padres->first();

        return [
            $this->lista[$e->est_codigo] ?? '',
            $e->est_codigo,
            $e->est_apellidos,
            $e->est_nombres,
            $e->est_ci,
            $e->curso->cur_nombre ?? '',
            $e->est_sexo ?? '',
            $e->est_fechanac ?? '',
            $e->est_celular ?? '',
            $padre->pfam_nombres ?? '',
            $padre->pfam_numeroscelular ?? '',
            $e->est_visible == 0 ? 'RETIRADO' : 'ACTIVO',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $estilos = [1 => ['font' => ['bold' => true]]];

        // Fila 1 = encabezados, así que los datos arrancan en la 2.
        $fila = 2;
        foreach ($this->estudiantes as $e) {
            if ($e->est_visible == 0) {
                $estilos[$fila] = ['font' => ['color' => ['rgb' => 'C0392B']]];
            }
            $fila++;
        }

        return $estilos;
    }
}
