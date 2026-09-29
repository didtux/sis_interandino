<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Control de Asistencia - {{ $asignacion->curso->cur_nombre }}</title>
    @php
        /*
         * La letra se escala según cuántas fechas entran, igual que en
         * notas/reporte-general-pdf.blade.php. Antes estaba fija en 5.5-6px
         * (~4pt impresos), que es ilegible en papel.
         *
         * Ancho útil en Legal apaisado ≈ 1286px. Se reservan el número, el
         * nombre y las 5 columnas de resumen; el resto se reparte entre fechas.
         */
        $nFechas = $fechas->count();
        if     ($nFechas <= 30) { $fsBody = 9;   $fsCelda = 8;   $wNombre = 170; }
        elseif ($nFechas <= 45) { $fsBody = 8.5; $fsCelda = 7.5; $wNombre = 160; }
        elseif ($nFechas <= 60) { $fsBody = 8;   $fsCelda = 7;   $wNombre = 150; }
        else                    { $fsBody = 7.5; $fsCelda = 6.5; $wNombre = 140; }
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: {{ $fsBody }}px; padding: 4mm; }

        /* ── Cabecera ── */
        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 45px; vertical-align: middle; }
        .logo img { width: 38px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 7.5px; margin: 0; }
        .fecha-box { position: absolute; top: 4mm; right: 4mm; background: #c0392b; color: #fff; padding: 2px 6px; border-radius: 6px; font-weight: bold; font-size: 8px; text-align: center; }
        .title-section { text-align: center; margin: 3px 0; border-bottom: 1.5px solid #000; padding-bottom: 2px; }
        .title-section h2 { font-size: 13px; font-weight: bold; }
        .title-section p { font-size: 9px; }

        /* ── Info trimestre ── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { padding: 1px 3px; font-size: 9px; vertical-align: top; border: none; }
        .lbl { font-weight: normal; color: #555; text-align: right; padding-right: 4px !important; }
        .val { font-weight: bold; }
        .val-red { font-weight: bold; color: #c0392b; }
        .trim-box { border: 2px solid #333; text-align: center; padding: 2px 10px; }
        .trim-box .trim-label { font-size: 8px; font-weight: bold; }
        .trim-box .trim-value { font-size: 20px; font-weight: bold; line-height: 1.1; }

        /* ── Tabla asistencia ──
           table-layout:fixed es lo que impide que DomPDF ensanche la tabla y se
           coma el margen cuando hay muchas fechas. */
        table.asis { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.asis th, table.asis td { border: 0.5px solid #555; text-align: center; font-size: {{ $fsCelda }}px; vertical-align: middle; }
        table.asis th { padding: 2px 1px; }
        table.asis td { padding: 1px 2px; }

        .th-num { width: 18px; background: #ddd; vertical-align: bottom; padding-bottom: 3px !important; font-weight: bold; }
        .th-nombre { width: {{ $wNombre }}px; background: #ddd; text-align: left !important; vertical-align: bottom; padding: 0 0 3px 3px !important; }
        .th-nombre span { font-size: {{ $fsCelda }}px; font-weight: bold; display: block; }
        .th-nombre small { font-size: {{ $fsCelda - 1 }}px; font-weight: normal; color: #555; }

        /* Celdas con texto vertical */
        .th-vert {
            width: 16px;
            height: 90px;
            background: #fff;
            vertical-align: middle;
            text-align: center;
            padding: 2px 0 !important;
        }
        .th-vert-res {
            width: 20px;
            height: 90px;
            background: #f0f0f0;
            vertical-align: middle;
            text-align: center;
            padding: 2px 0 !important;
        }

        /* Celdas de datos.
           Sin white-space:nowrap el apellido largo baja de línea en vez de
           cortarse sin aviso, que era lo que pasaba en 85px. */
        .col-num { font-weight: bold; font-size: {{ $fsCelda }}px; width: 18px; }
        .col-nombre { text-align: left !important; padding-left: 3px !important; font-size: {{ $fsCelda }}px; width: {{ $wNombre }}px; word-wrap: break-word; }
        .cell-P { background: #d5f5e3; color: #155724; font-weight: bold; }
        .cell-A { background: #fff3cd; color: #856404; font-weight: bold; }
        .cell-F { background: #f8d7da; color: #721c24; font-weight: bold; }
        .cell-L { background: #d1ecf1; color: #0c5460; font-weight: bold; }
        .total-val { font-weight: bold; font-size: {{ $fsCelda }}px; background: #f8f8f8; }

        .footer { position: fixed; bottom: 4mm; left: 4mm; font-size: 7px; color: #888; }
    </style>
</head>
<body>

    <div class="fecha-box">{{ now()->format('d/m/Y') }}</div>

    <div class="header">
        <div class="logo">
            @php $sc = $config ?? \App\Models\SistemaConfiguracion::actual(); @endphp
            @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" alt="Logo">
            @elseif(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339 | Tel: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <p style="font-size:10px;font-weight:bold;">{{ mb_strtoupper($asignacion->curso->cur_nombre, 'UTF-8') }} — {{ mb_strtoupper($asignacion->materia->mat_nombre, 'UTF-8') }}</p>
        <h2>CONTROL DE ASISTENCIA — {{ $periodo->periodo_numero }}º TRIMESTRE</h2>
    </div>

    <table class="info">
        <tr>
            <td class="lbl" style="width:18%;">DOCENTE</td>
            <td class="val" style="width:32%;">{{ $asignacion->docente->doc_nombres }} {{ $asignacion->docente->doc_apellidos }}</td>
            <td class="lbl" style="width:16%;">INICIO TRIMESTRE</td>
            <td class="val" style="width:14%;">{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }}</td>
            <td rowspan="3" style="width:20%;vertical-align:middle;text-align:center;">
                <div class="trim-box">
                    <div class="trim-label">TRIMESTRE</div>
                    <div class="trim-value">{{ $periodo->periodo_numero == 1 ? '1er.' : ($periodo->periodo_numero == 2 ? '2do.' : '3er.') }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="lbl">GESTIÓN</td>
            <td class="val">{{ $gestion }}</td>
            <td class="lbl">FIN TRIMESTRE</td>
            <td class="val">{{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td></td><td></td>
            <td class="lbl"><span style="color:#c0392b;">FECHA ACTUAL</span></td>
            <td class="val-red">{{ now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="asis">
        <thead>
            <tr>
                <th class="th-num">N°</th>
                <th class="th-nombre">
                    <span>NÓMINA DE ESTUDIANTES</span>
                    <small>Apellidos y Nombres</small>
                </th>
                @foreach($fechas as $f)
                    <th class="th-vert">
                        <svg width="16" height="86" xmlns="http://www.w3.org/2000/svg">
                            <text x="10" y="84" transform="rotate(-90, 11, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#1a7a2e">{{ \Carbon\Carbon::parse($f)->format('d/m/Y') }}</text>
                        </svg>
                    </th>
                @endforeach
                <th class="th-vert-res">
                    <svg width="18" height="86" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="84" transform="rotate(-90, 12, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#333">Asist. (A)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="18" height="86" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="84" transform="rotate(-90, 12, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#333">Faltas (F)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="18" height="86" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="84" transform="rotate(-90, 12, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#333">Retrasos (R)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="18" height="86" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="84" transform="rotate(-90, 12, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#333">Licencias (L)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="18" height="86" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="84" transform="rotate(-90, 12, 84)" font-family="Arial" font-size="9" font-weight="bold" fill="#333">DÍAS TRABAJADOS</text>
                    </svg>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($estudiantes as $i => $est)
                @php
                    $estAsis = $asistencias[$est->est_codigo] ?? collect();
                    $tot = $totales[$est->est_codigo] ?? collect();
                    $tP = $tot['P'] ?? 0;
                    $tA = $tot['A'] ?? 0;
                    $tF = $tot['F'] ?? 0;
                    $tL = $tot['L'] ?? 0;
                    $dias = $tP + $tA;
                @endphp
                <tr>
                    <td class="col-num">{{ $est->lista_numero ?? ($i + 1) }}</td>
                    <td class="col-nombre">{{ mb_strtoupper($est->est_apellidos . ' ' . $est->est_nombres, 'UTF-8') }}</td>
                    @foreach($fechas as $f)
                        @php $a = $estAsis[$f] ?? null; $estado = $a ? $a->asiscl_estado : ''; @endphp
                        <td class="{{ $estado ? 'cell-'.$estado : '' }}">{{ $estado }}</td>
                    @endforeach
                    <td class="total-val">{{ $tP }}</td>
                    <td class="total-val">{{ $tF }}</td>
                    <td class="total-val">{{ $tA }}</td>
                    <td class="total-val">{{ $tL }}</td>
                    <td class="total-val">{{ $dias }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $fechas->count() + 7 }}" style="padding:15px;text-align:center;color:#999;">Sin estudiantes registrados</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | {{ $asignacion->curso->cur_nombre }} | {{ $asignacion->materia->mat_nombre }} | {{ $periodo->periodo_nombre }} | Gestión {{ $gestion }}
    </div>
</body>
</html>
