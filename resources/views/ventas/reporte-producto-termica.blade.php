<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 10px; width: 200px; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 8px; margin-bottom: 8px; font-size: 8px; line-height: 1.3; }
        .header h1 { font-size: 11px; margin: 2px 0; }
        .info { margin: 8px 0; font-size: 9px; }
        .info-row { margin: 3px 0; }
        .label { font-weight: bold; }
        .ventas { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 8px 0; margin: 8px 0; }
        .venta-item { margin: 5px 0; font-size: 9px; }
        .total { font-size: 12px; font-weight: bold; text-align: right; margin-top: 10px; }
        .footer { text-align: center; margin-top: 15px; font-size: 8px; border-top: 1px dashed #000; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>U.E. INTERANDINO BOLIVIANO</h1>
        <p>REPORTE VENTA POR PRODUCTO</p>
    </div>
    
    <div class="info">
        <div class="info-row"><span class="label">Producto:</span><br>{{ $producto->prod_nombre }}</div>
        <div class="info-row"><span class="label">Período:</span><br>{{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</div>
    </div>

    <div class="ventas">
        @foreach($ventas as $fecha => $total)
        <div class="venta-item">
            <span class="label">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span><br>
            Bs. {{ number_format($total, 2) }}
        </div>
        @endforeach
    </div>

    <div class="total">
        TOTAL: Bs. {{ number_format($ventas->sum(), 2) }}
    </div>

    <div class="footer">
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
