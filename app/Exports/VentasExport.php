<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VentasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $ventas;

    public function __construct($ventas)
    {
        $this->ventas = $ventas;
    }

    public function collection()
    {
        return $this->ventas;
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Cliente',
            'Celular',
            'Dirección',
            'Cantidad',
            'Precio Unitario',
            'Total',
            'Tipo',
            'Fecha',
            'Estado'
        ];
    }

    public function map($venta): array
    {
        return [
            $venta->ven_codigo,
            $venta->producto->prod_nombre ?? 'N/A',
            $venta->ven_cliente,
            $venta->ven_celular,
            $venta->ven_direccion,
            $venta->venta_cantidad,
            $venta->venta_precio,
            $venta->venta_preciototal,
            ucfirst($venta->venta_tipo),
            $venta->venta_fecha->format('d/m/Y H:i'),
            $venta->venta_estado
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4CAF50']]],
        ];
    }

    public function title(): string
    {
        return 'Reporte Ventas';
    }
}
