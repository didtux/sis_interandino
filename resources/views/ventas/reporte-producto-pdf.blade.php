<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Venta de {{ $producto->prod_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; padding: 20px; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.2; font-weight: bold; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .info-box { position: absolute; top: 20px; right: 20px; text-align: right; font-size: 9px; }
        .info-box .label { font-weight: bold; }
        .title-section { text-align: center; margin: 15px 0; }
        .title-section h2 { font-size: 13px; font-weight: bold; margin: 3px 0; }
        .title-section p { font-size: 10px; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 8px; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { text-align: center; }
        .total-row { background-color: #e8e8e8; font-weight: bold; font-size: 12px; }
        .footer { margin-top: 40px; font-size: 11px; font-style: italic; }
        .footer-line { margin: 20px 0; }
        .page-number { position: fixed; bottom: 10px; right: 20px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="info-box">
        <div class="label">Saib: Fecha impresión</div>
        <div>{{ now()->format('d/m/Y') }}</div>
        <div>{{ now()->format('H:i:s') }}</div>
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
        <h2>VENTA DE {{ strtoupper($producto->prod_nombre) }}</h2>
        <p>DEL {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">FECHA</th>
                <th style="width: 60%;">TOTAL RECAUDADO</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($ventas as $fecha => $monto)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd DD/MM/YYYY') }}</td>
                    <td>Bs. {{ number_format($monto, 2) }}</td>
                </tr>
                @php $total += $monto; @endphp
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                <td>Bs. {{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div style="display: table; width: 100%; margin-top: 40px;">
            <div style="display: table-cell; width: 50%; padding-right: 20px;">
                <div>ENTREGUÉ CONFORME</div>
                <div style="border-bottom: 1px dotted #000; margin-top: 30px; width: 80%;"></div>
            </div>
            <div style="display: table-cell; width: 50%; padding-left: 20px;">
                <div>RECIBÍ CONFORME</div>
                <div style="border-bottom: 1px dotted #000; margin-top: 30px; width: 80%;"></div>
            </div>
        </div>
    </div>

    <div class="page-number">
        Pág. 1 de 1
    </div>
</body>
</html>
