<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo - {{ $ventas->first()->ven_codigo }}</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            font-size: 10px;
            margin: 10px;
            width: 200px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
            font-size: 8px;
            line-height: 1.3;
        }
        .header h1 {
            font-size: 11px;
            margin: 2px 0;
        }
        .info {
            margin: 5px 0;
        }
        .info-row {
            margin: 3px 0;
        }
        .productos {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px 0;
            margin: 8px 0;
        }
        .producto-item {
            margin: 5px 0;
        }
        .total {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        .literal {
            font-size: 8px;
            margin-top: 5px;
            font-style: italic;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 8px;
            border-top: 1px dashed #000;
            padding-top: 8px;
        }
        .label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>U.E. PRIVADA INTERANDINO BOLIVIANO</h1>
        <p>C/ VICTOR GUTIERREZ N° 3339</p>
        <p>TELEFONO: 2840320 - 67304340</p>
        <p style="margin-top: 5px; font-weight: bold;">RECIBO DE VENTA</p>
    </div>
    
    <div class="info">
        <div class="info-row"><span class="label">Código:</span> {{ $ventas->first()->ven_codigo }}</div>
        <div class="info-row"><span class="label">Fecha:</span> {{ $ventas->first()->venta_fecha->format('d/m/Y H:i') }}</div>
        <div class="info-row"><span class="label">Cliente:</span> {{ $ventas->first()->ven_cliente }}</div>
        @if($ventas->first()->ven_celular)
            <div class="info-row"><span class="label">Celular:</span> {{ $ventas->first()->ven_celular }}</div>
        @endif
        @if($ventas->first()->ven_direccion)
            <div class="info-row"><span class="label">Dirección:</span> {{ $ventas->first()->ven_direccion }}</div>
        @endif
        <div class="info-row"><span class="label">Tipo:</span> {{ ucfirst($ventas->first()->venta_tipo) }}</div>
    </div>

    <div class="productos">
        @foreach($ventas as $venta)
        <div class="producto-item">
            <div><span class="label">Producto:</span></div>
            <div>{{ $venta->producto->prod_nombre ?? 'N/A' }}</div>
        </div>
        <div class="producto-item">
            <span class="label">Cantidad:</span> {{ $venta->venta_cantidad }}
        </div>
        <div class="producto-item">
            <span class="label">Precio Unit.:</span> Bs. {{ number_format($venta->venta_precio, 2) }}
        </div>
        <div class="producto-item">
            <span class="label">Subtotal:</span> Bs. {{ number_format($venta->venta_preciototal, 2) }}
        </div>
        @if(!$loop->last)
        <div style="border-bottom: 1px dotted #000; margin: 5px 0;"></div>
        @endif
        @endforeach
    </div>

    <div class="total">
        TOTAL: Bs. {{ number_format($total, 2) }}
    </div>
    
    @php
        function numeroALetras($numero) {
            $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
            $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            $especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
            $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
            
            $entero = floor($numero);
            $decimal = round(($numero - $entero) * 100);
            
            if ($entero == 0) return 'cero ' . str_pad($decimal, 2, '0', STR_PAD_LEFT) . '/100 Bolivianos';
            
            $literal = '';
            if ($entero >= 1000) {
                $miles = floor($entero / 1000);
                $literal .= ($miles == 1 ? 'mil ' : $unidades[$miles] . ' mil ');
                $entero %= 1000;
            }
            if ($entero >= 100) {
                $literal .= ($entero == 100 ? 'cien ' : $centenas[floor($entero / 100)] . ' ');
                $entero %= 100;
            }
            if ($entero >= 20) {
                $literal .= $decenas[floor($entero / 10)];
                if ($entero % 10 > 0) $literal .= ' y ' . $unidades[$entero % 10];
            } elseif ($entero >= 10) {
                $literal .= $especiales[$entero - 10];
            } elseif ($entero > 0) {
                $literal .= $unidades[$entero];
            }
            
            return ucfirst(trim($literal)) . ' ' . str_pad($decimal, 2, '0', STR_PAD_LEFT) . '/100 Bolivianos';
        }
        
        $literal = numeroALetras($total);
    @endphp
    
    <div class="literal">
        Son: {{ $literal }}
    </div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
