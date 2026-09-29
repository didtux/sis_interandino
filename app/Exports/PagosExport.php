<?php

namespace App\Exports;

use App\Models\Pago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Listado de pagos en Excel.
 *
 * Toma los mismos filtros que PagoController::reportePdf para que el PDF y el
 * Excel de la misma pantalla devuelvan exactamente las mismas filas: se
 * excluyen los anulados (pagos_estado = 1) y se ordena por curso, que es como
 * el colegio lee los listados.
 */
class PagosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Pago::with('estudiante.curso', 'padreFamilia')
            ->where('pagos_estado', 1);

        if (!empty($this->filters['fecha_inicio'])) {
            $query->whereDate('pagos_fecha', '>=', $this->filters['fecha_inicio']);
        }
        if (!empty($this->filters['fecha_fin'])) {
            $query->whereDate('pagos_fecha', '<=', $this->filters['fecha_fin']);
        }
        if (!empty($this->filters['est_codigo'])) {
            $query->where('est_codigo', $this->filters['est_codigo']);
        }
        if (!empty($this->filters['cur_codigo'])) {
            $curso = $this->filters['cur_codigo'];
            $query->whereHas('estudiante', fn($q) => $q->where('cur_codigo', $curso));
        }
        if (!empty($this->filters['concepto'])) {
            $query->where('concepto', 'like', '%' . $this->filters['concepto'] . '%');
        }

        // El orden por curso se resuelve en PHP: cur_orden vive en otra tabla y
        // con otra colación, así que un orderBy por SQL obligaría a un join
        // conflictivo (mismo criterio que el resto del sistema).
        return $query->get()->sortBy([
            fn($a, $b) => ($a->estudiante->curso->cur_orden ?? 9999) <=> ($b->estudiante->curso->cur_orden ?? 9999),
            fn($a, $b) => strcmp($a->estudiante->curso->cur_nombre ?? '', $b->estudiante->curso->cur_nombre ?? ''),
            fn($a, $b) => strcmp($a->estudiante->est_apellidos ?? '', $b->estudiante->est_apellidos ?? ''),
        ])->values();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Recibo',
            'Curso',
            'Estudiante',
            'Padre / Tutor',
            'Concepto',
            'Precio',
            'Descuento',
            'Total',
        ];
    }

    public function map($pago): array
    {
        $est = $pago->estudiante;
        $nombreEst = $est
            ? trim(($est->est_apellidos ?? '') . ' ' . ($est->est_nombres ?? ''))
            : 'N/A';

        return [
            optional($pago->pagos_fecha)->format('d/m/Y'),
            $pago->pagos_codigo ?? '',
            $est->curso->cur_nombre ?? 'SIN CURSO',
            $nombreEst !== '' ? $nombreEst : 'N/A',
            $pago->padreFamilia->pfam_nombres ?? 'N/A',
            $pago->concepto,
            (float) $pago->pagos_precio,
            (float) $pago->pagos_descuento,
            (float) $pago->pagos_precio - (float) $pago->pagos_descuento,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
