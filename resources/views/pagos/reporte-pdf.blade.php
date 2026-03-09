<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Pagos y Mensualidades</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 { 
            color: #333;
            margin: 5px 0;
        }
        .filtros { 
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 10px;
        }
        .filtros strong {
            color: #4CAF50;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 6px; 
            text-align: left;
        }
        th { 
            background-color: #4CAF50; 
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .total { 
            font-weight: bold; 
            text-align: right; 
            margin-top: 15px;
            font-size: 13px;
            color: #4CAF50;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>REPORTE DE PAGOS Y MENSUALIDADES</h2>
        <p style="margin: 5px 0; color: #666;">Sistema Interandino</p>
    </div>
    
    <div class="filtros">
        <strong>Filtros aplicados:</strong><br>
        @if($request->filled('fecha_inicio'))
            <strong>Fecha Inicio:</strong> {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }}<br>
        @endif
        @if($request->filled('fecha_fin'))
            <strong>Fecha Fin:</strong> {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}<br>
        @endif
        @if($request->filled('tipo'))
            <strong>Tipo:</strong> {{ $request->tipo == '1' ? 'Mensualidad' : 'Otros' }}<br>
        @endif
        @if($request->filled('concepto'))
            <strong>Concepto:</strong> {{ $request->concepto }}<br>
        @endif
        <strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Total de registros:</strong> {{ $pagos->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Fecha</th>
                <th width="20%">Estudiante</th>
                <th width="20%">Padre</th>
                <th width="20%">Concepto</th>
                <th width="10%" class="text-right">Precio</th>
                <th width="10%" class="text-right">Descuento</th>
                <th width="10%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagos as $pago)
                <tr>
                    <td>{{ $pago->pagos_fecha->format('d/m/Y') }}</td>
                    <td>{{ $pago->estudiante->est_nombres ?? 'N/A' }}</td>
                    <td>{{ $pago->padreFamilia->pfam_nombres ?? 'N/A' }}</td>
                    <td>{{ $pago->concepto }}</td>
                    <td class="text-right">Bs. {{ number_format($pago->pagos_precio, 2) }}</td>
                    <td class="text-right">Bs. {{ number_format($pago->pagos_descuento, 2) }}</td>
                    <td class="text-right"><strong>Bs. {{ number_format($pago->pagos_precio - $pago->pagos_descuento, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <p>TOTAL GENERAL: Bs. {{ number_format($total, 2) }}</p>
    </div>

    <div class="footer">
        <p>Reporte generado por Sistema Interandino - {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
