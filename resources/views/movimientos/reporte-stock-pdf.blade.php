<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Stock</title>
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
        .title-section p { font-size: 8px; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 4px 3px; font-size: 8px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { vertical-align: top; text-align: center; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }
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
        <h2>REPORTE DE STOCK DE PRODUCTOS</h2>
        @if($filtros)
            <p>
                @if(isset($filtros['buscar'])) Búsqueda: {{ $filtros['buscar'] }} | @endif
                @if(isset($filtros['estado'])) Estado: {{ ucfirst(str_replace('_', ' ', $filtros['estado'])) }} @endif
            </p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Código</th>
                <th style="width: 25%;">Producto</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Proveedor</th>
                <th style="width: 7%;">Stock</th>
                <th style="width: 10%;">P. Unit.</th>
                <th style="width: 10%;">Valor</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $valorTotal = 0; @endphp
            @foreach($productos as $p)
                @php 
                    $valor = $p->prod_cantidad * $p->prod_preciounitario;
                    $valorTotal += $valor;
                    
                    if($p->prod_cantidad == 0) {
                        $estado = 'Sin Stock';
                    } elseif($p->prod_cantidad <= 5) {
                        $estado = 'Bajo';
                    } elseif($p->prod_cantidad <= 10) {
                        $estado = 'Medio';
                    } else {
                        $estado = 'Normal';
                    }
                @endphp
                <tr>
                    <td>{{ $p->prod_codigo }}</td>
                    <td style="text-align: left;">{{ strtoupper($p->prod_nombre) }}</td>
                    <td>{{ strtoupper($p->categoria->categ_nombre ?? 'N/A') }}</td>
                    <td>{{ strtoupper($p->proveedor->prov_nombre ?? 'N/A') }}</td>
                    <td>{{ $p->prod_cantidad }}</td>
                    <td>Bs. {{ number_format($p->prod_preciounitario, 2) }}</td>
                    <td>Bs. {{ number_format($valor, 2) }}</td>
                    <td>{{ $estado }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align: right;">VALOR TOTAL INVENTARIO:</td>
                <td colspan="2">Bs. {{ number_format($valorTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
