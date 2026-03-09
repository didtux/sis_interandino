<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 10px; width: 200px; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 8px; margin-bottom: 8px; font-size: 8px; line-height: 1.3; }
        .header h1 { font-size: 11px; margin: 2px 0; }
        .info { margin: 8px 0; font-size: 9px; }
        .label { font-weight: bold; }
        .ventas { border-top: 1px dashed #000; padding: 8px 0; margin: 8px 0; }
        .venta-item { margin: 5px 0; font-size: 9px; border-bottom: 1px dotted #000; padding-bottom: 5px; }
        .total { font-size: 12px; font-weight: bold; text-align: right; margin-top: 10px; border-top: 2px solid #000; padding-top: 5px; }
        .footer { text-align: center; margin-top: 15px; font-size: 8px; border-top: 1px dashed #000; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>U.E. INTERANDINO BOLIVIANO</h1>
        <p>ARQUEO DE ALMACÉN</p>
    </div>
    
    <div class="info">
        <span class="label">Total Ventas:</span> {{ $ventas->count() }}
    </div>

    <div class="ventas">
        @foreach($ventas as $venta)
        <div class="venta-item">
            <div><span class="label">{{ $venta->venta_fecha->format('d/m/Y H:i') }}</span></div>
            <div>{{ $venta->producto->prod_nombre ?? 'N/A' }}</div>
            <div>Cant: {{ $venta->venta_cantidad }} x Bs. {{ number_format($venta->venta_precio, 2) }}</div>
            <div><span class="label">Total: Bs. {{ number_format($venta->venta_preciototal, 2) }}</span></div>
        </div>
        @endforeach
    </div>

    <div class="total">
        TOTAL: Bs. {{ number_format($ventas->sum('venta_preciototal'), 2) }}
    </div>

    <div class="footer">
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
