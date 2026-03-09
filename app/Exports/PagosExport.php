<?php

namespace App\Exports;

use App\Models\Pago;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PagosExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Pago::with('estudiante', 'padreFamilia');

        if (isset($this->filters['fecha_inicio'])) {
            $query->whereDate('pagos_fecha', '>=', $this->filters['fecha_inicio']);
        }
        if (isset($this->filters['fecha_fin'])) {
            $query->whereDate('pagos_fecha', '<=', $this->filters['fecha_fin']);
        }
        if (isset($this->filters['est_codigo'])) {
            $query->where('est_codigo', $this->filters['est_codigo']);
        }
        if (isset($this->filters['concepto'])) {
            $query->where('concepto', 'like', '%' . $this->filters['concepto'] . '%');
        }

        return $query->orderBy('pagos_fecha', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Estudiante',
            'Padre',
            'Concepto',
            'Precio',
            'Descuento',
            'Total'
        ];
    }

    public function map($pago): array
    {
        return [
            $pago->pagos_fecha->format('d/m/Y'),
            $pago->estudiante->est_nombres ?? 'N/A',
            $pago->padreFamilia->pfam_nombres ?? 'N/A',
            $pago->concepto,
            $pago->pagos_precio,
            $pago->pagos_descuento,
            $pago->pagos_precio - $pago->pagos_descuento
        ];
    }
}
