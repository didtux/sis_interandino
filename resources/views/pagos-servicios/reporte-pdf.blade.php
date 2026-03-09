<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Pagos de Servicios</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 15px; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.3; }
        .header-info p { font-size: 8px; margin: 2px 0; }
        .fecha-box { position: absolute; top: 15px; right: 15px; background-color: #2196F3; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 10px; }
        .title-section { text-align: center; margin: 15px 0; }
        .title-section h2 { font-size: 14px; font-weight: bold; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; font-size: 8px; }
        th { background-color: #4CAF50; color: white; font-weight: bold; text-align: center; }
        .numero { text-align: right; }
        .centro { text-align: center; }
        .total-row { background-color: #e0e0e0; font-weight: bold; }
        .footer { position: fixed; bottom: 10px; left: 15px; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        Fecha<br>{{ now()->format('d/m/Y') }}
    </div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa<br>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>REPORTE DE PAGOS DE SERVICIOS</h2>
        <p>Gestión {{ date('Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">CÓDIGO</th>
                <th width="10%">FECHA</th>
                <th width="18%">SERVICIO</th>
                <th width="25%">ESTUDIANTE</th>
                <th width="10%">MONTO</th>
                <th width="10%">DESCUENTO</th>
                <th width="10%">TOTAL</th>
                <th width="5%">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagos as $pago)
                <tr>
                    <td class="centro">{{ $pago->pserv_codigo }}</td>
                    <td class="centro">{{ $pago->pserv_fecha->format('d/m/Y') }}</td>
                    <td>{{ $pago->servicio->serv_nombre ?? 'N/A' }}</td>
                    <td>{{ $pago->estudiante->est_nombres ?? 'N/A' }} {{ $pago->estudiante->est_apellidos ?? '' }}</td>
                    <td class="numero">{{ number_format($pago->pserv_monto, 2) }}</td>
                    <td class="numero">{{ number_format($pago->pserv_descuento, 2) }}</td>
                    <td class="numero"><strong>{{ number_format($pago->pserv_total, 2) }}</strong></td>
                    <td class="centro">{{ $pago->pserv_estado == 1 ? 'Activo' : 'Anulado' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align: right;">TOTAL GENERAL:</td>
                <td class="numero"><strong>{{ number_format($total, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
