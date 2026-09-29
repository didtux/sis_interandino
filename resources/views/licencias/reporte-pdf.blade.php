<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    {{--
        Matriz genérica de licencias. La usan los tres reportes (mensual,
        anual por estudiante y anual por curso) porque los tres son la misma
        forma: una columna de etiqueta, N columnas de período y un TOTAL.
        El ancho se reparte solo según cuántas columnas haya.
    --}}
    @php
        $nCols  = count($columnas);
        $wEtiq  = $nCols > 15 ? 18 : 26;         // % para la primera columna
        $wCol   = ($nCols > 0) ? (100 - $wEtiq - 7) / $nCols : 0;
        $fs     = $nCols > 20 ? 7 : ($nCols > 12 ? 8 : 9);
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: {{ $fs }}px; padding: 14px; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .logo img { width: 52px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 14px; right: 14px; text-align: right; font-size: 8px; }
        .title-section { text-align: center; margin: 8px 0; }
        .title-section h2 { font-size: 12px; }
        .title-section p { font-size: 9px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 3px 2px; font-size: {{ $fs }}px; text-align: center; word-wrap: break-word; }
        th { background-color: #1c4789; color: #fff; font-weight: bold; }
        td.etiqueta { text-align: left; }
        .total-col, .total-row td { font-weight: bold; background-color: #ededed; }
        .retirado { color: #c0392b; }
        .festivo { background-color: #f2f2f2; font-size: {{ max(5, $fs - 2) }}px; }
        .footer { position: fixed; bottom: 8px; left: 14px; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="fecha-box">
        <div><strong>Fecha</strong></div>
        <div>{{ now()->format('d/m/Y') }}</div>
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
        <h2>{{ $titulo }}</h2>
        <p>{{ $subtitulo }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: {{ $wEtiq }}%;">{{ $etiquetaColumna }}</th>
                @foreach($columnas as $col)
                    <th style="width: {{ $wCol }}%;">{{ $col['titulo'] }}</th>
                @endforeach
                <th style="width: 7%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $fila)
                <tr class="{{ !empty($fila['retirado']) ? 'retirado' : '' }}">
                    <td class="etiqueta">{{ $fila['etiqueta'] }}</td>
                    @foreach($columnas as $col)
                        @php $v = $fila['valores'][$col['clave']] ?? 0; @endphp
                        <td class="{{ is_string($v) ? 'festivo' : '' }}">{{ $v }}</td>
                    @endforeach
                    <td class="total-col">{{ $fila['total'] }}</td>
                </tr>
            @endforeach

            @if(!empty($totales))
                <tr class="total-row">
                    <td class="etiqueta">TOTAL</td>
                    @foreach($columnas as $col)
                        <td>{{ $totales['valores'][$col['clave']] ?? 0 }}</td>
                    @endforeach
                    <td>{{ $totales['total'] }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} — Los estudiantes retirados se imprimen en rojo.
    </div>
</body>
</html>
