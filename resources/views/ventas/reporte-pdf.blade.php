<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Ventas</title>
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
        .info { background-color: #f5f5f5; padding: 8px; margin: 10px 0; border-radius: 4px; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; font-size: 8px; }
        th { background-color: #4CAF50; color: white; font-weight: bold; text-align: center; }
        .numero { text-align: right; }
        .centro { text-align: center; }
        .total-row { background-color: #e0e0e0; font-weight: bold; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 7px; }
        .badge-venta { background-color: #27ae60; color: white; }
        .badge-prestamo { background-color: #f39c12; color: white; }
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
        <h2>REPORTE DE VENTAS</h2>
        <p>Gestión {{ date('Y') }}</p>
    </div>

    <div class="info">
        @if($request->filled('fecha_inicio') || $request->filled('fecha_fin'))
            <strong>Período:</strong> 
            {{ $request->fecha_inicio ? \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') : 'Inicio' }} 
            - 
            {{ $request->fecha_fin ? \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') : 'Fin' }}
            <br>
        @endif
        @if($request->filled('tipo'))
            <strong>Tipo:</strong> {{ ucfirst($request->tipo) }}<br>
        @endif
        @if($request->filled('cliente'))
            <strong>Cliente:</strong> {{ $request->cliente }}<br>
        @endif
        <strong>Total de registros:</strong> {{ $ventas->count() }}
        @if($ventas->count() >= 500)
            <span style="color: red;"> (Limitado a 500 registros - Use filtros para refinar)</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">CÓDIGO</th>
                <th width="22%">PRODUCTO</th>
                <th width="18%">CLIENTE</th>
                <th width="8%">CANT.</th>
                <th width="10%">P. UNIT.</th>
                <th width="10%">TOTAL</th>
                <th width="10%">TIPO</th>
                <th width="12%">FECHA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
                <tr>
                    <td class="centro">{{ $venta->ven_codigo }}</td>
                    <td>{{ $venta->producto->prod_nombre ?? 'N/A' }}</td>
                    <td>{{ $venta->ven_cliente }}</td>
                    <td class="centro">{{ $venta->venta_cantidad }}</td>
                    <td class="numero">{{ number_format($venta->venta_precio, 2) }}</td>
                    <td class="numero"><strong>{{ number_format($venta->venta_preciototal, 2) }}</strong></td>
                    <td class="centro">
                        <span class="badge badge-{{ $venta->venta_tipo }}">{{ ucfirst($venta->venta_tipo) }}</span>
                    </td>
                    <td class="centro">{{ $venta->venta_fecha->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;"><strong>TOTAL GENERAL:</strong></td>
                <td colspan="3" class="numero"><strong>Bs. {{ number_format($total, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
