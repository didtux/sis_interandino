<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Arqueo Semanal de Almacén</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 15px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; font-weight: bold; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 15px; right: 15px; text-align: right; }
        .fecha-box .label { font-size: 8px; font-weight: bold; }
        .fecha-box .fecha { font-size: 10px; font-weight: bold; }
        .fecha-box .saib { font-size: 7px; font-style: italic; }
        .title-section { text-align: center; margin: 10px 0; }
        .title-section h2 { font-size: 12px; font-weight: bold; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 4px 3px; font-size: 8px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { vertical-align: top; }
        .col-num { width: 4%; text-align: center; }
        .col-fecha { width: 10%; text-align: center; }
        .col-recibo { width: 8%; text-align: center; }
        .col-cliente { width: 15%; }
        .col-producto { width: 40%; }
        .col-cantidad { width: 8%; text-align: center; }
        .col-monto { width: 15%; text-align: right; }
        .footer { position: fixed; bottom: 10px; left: 15px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        <div class="label">Fecha</div>
        <div class="fecha">{{ now()->format('d/m/Y') }}</div>
        <div class="saib">Saib</div>
    </div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>UNIDAD EDUCATIVA PRIVADA</h3>
            <h3>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>Arqueo Semanal de almacen</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-fecha">Fecha</th>
                <th class="col-recibo">Nro recibo</th>
                <th class="col-cliente">Cliente</th>
                <th class="col-producto">Producto</th>
                <th class="col-cantidad">Cantidad</th>
                <th class="col-monto">Monto</th>
            </tr>
        </thead>
        <tbody>
            @php $contador = 1; $total = 0; @endphp
            @foreach($ventas as $venta)
                @php $total += $venta->venta_preciototal; @endphp
                <tr>
                    <td class="col-num">{{ $contador++ }}</td>
                    <td class="col-fecha">{{ \Carbon\Carbon::parse($venta->venta_fecha)->format('d/m/Y') }}</td>
                    <td class="col-recibo">{{ $venta->ven_codigo }}</td>
                    <td class="col-cliente">{{ strtoupper($venta->ven_cliente) }}</td>
                    <td class="col-producto">{{ strtoupper($venta->producto->prod_nombre ?? 'N/A') }}</td>
                    <td class="col-cantidad">{{ $venta->venta_cantidad }}</td>
                    <td class="col-monto">Bs. {{ number_format($venta->venta_preciototal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold; background-color: #f0f0f0;">TOTAL:</td>
                <td class="col-monto" style="font-weight: bold; background-color: #f0f0f0;">Bs. {{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
