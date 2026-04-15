<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Control de Asistencia - {{ $asignacion->curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 5.5px; padding: 4mm; }

        /* ── Cabecera ── */
        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 45px; vertical-align: middle; }
        .logo img { width: 38px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 8px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 5.5px; margin: 0; }
        .fecha-box { position: absolute; top: 4mm; right: 4mm; background: #c0392b; color: #fff; padding: 2px 6px; border-radius: 6px; font-weight: bold; font-size: 5.5px; text-align: center; }
        .title-section { text-align: center; margin: 3px 0; border-bottom: 1.5px solid #000; padding-bottom: 2px; }
        .title-section h2 { font-size: 9px; font-weight: bold; }
        .title-section p { font-size: 6.5px; }

        /* ── Info trimestre ── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { padding: 1px 3px; font-size: 7.5px; vertical-align: top; border: none; }
        .lbl { font-weight: normal; color: #555; text-align: right; padding-right: 4px !important; }
        .val { font-weight: bold; }
        .val-red { font-weight: bold; color: #c0392b; }
        .trim-box { border: 2px solid #333; text-align: center; padding: 2px 10px; }
        .trim-box .trim-label { font-size: 6px; font-weight: bold; }
        .trim-box .trim-value { font-size: 20px; font-weight: bold; line-height: 1.1; }

        /* ── Tabla asistencia ── */
        table.asis { width: 100%; border-collapse: collapse; }
        table.asis th, table.asis td { border: 0.5px solid #555; text-align: center; font-size: 6px; vertical-align: middle; }
        table.asis th { padding: 2px 1px; }
        table.asis td { padding: 1px 2px; }

        .th-num { width: 14px; background: #ddd; vertical-align: bottom; padding-bottom: 3px !important; font-weight: bold; }
        .th-nombre { width: 85px; max-width: 85px; background: #ddd; text-align: left !important; vertical-align: bottom; padding: 0 0 3px 3px !important; }
        .th-nombre span { font-size: 6px; font-weight: bold; display: block; }
        .th-nombre small { font-size: 5px; font-weight: normal; color: #555; }

        /* Celdas con texto vertical */
        .th-vert {
            width: 16px;
            height: 80px;
            background: #fff;
            vertical-align: middle;
            text-align: center;
            padding: 2px 0 !important;
        }
        .th-vert-res {
            width: 20px;
            height: 80px;
            background: #f0f0f0;
            vertical-align: middle;
            text-align: center;
            padding: 2px 0 !important;
        }

        /* Celdas de datos */
        .col-num { font-weight: bold; font-size: 5.5px; width: 14px; }
        .col-nombre { text-align: left !important; padding-left: 3px !important; font-size: 5.5px; white-space: nowrap; width: 85px; max-width: 85px; overflow: hidden; }
        .cell-P { background: #d5f5e3; color: #155724; font-weight: bold; }
        .cell-A { background: #fff3cd; color: #856404; font-weight: bold; }
        .cell-F { background: #f8d7da; color: #721c24; font-weight: bold; }
        .cell-L { background: #d1ecf1; color: #0c5460; font-weight: bold; }
        .total-val { font-weight: bold; font-size: 6px; background: #f8f8f8; }

        .footer { position: fixed; bottom: 4mm; left: 4mm; font-size: 5px; color: #888; }
    </style>
</head>
<body>

    <div class="fecha-box">{{ now()->format('d/m/Y') }}</div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339 | Tel: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <p style="font-size:7px;font-weight:bold;">{{ mb_strtoupper($asignacion->curso->cur_nombre, 'UTF-8') }} — {{ mb_strtoupper($asignacion->materia->mat_nombre, 'UTF-8') }}</p>
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
                        <svg width="14" height="75" xmlns="http://www.w3.org/2000/svg">
                            <text x="10" y="73" transform="rotate(-90, 10, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#1a7a2e">{{ \Carbon\Carbon::parse($f)->format('d/m/Y') }}</text>
                        </svg>
                    </th>
                @endforeach
                <th class="th-vert-res">
                    <svg width="16" height="75" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="73" transform="rotate(-90, 11, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#333">Asist. (A)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="16" height="75" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="73" transform="rotate(-90, 11, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#333">Faltas (F)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="16" height="75" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="73" transform="rotate(-90, 11, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#333">Retrasos (R)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="16" height="75" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="73" transform="rotate(-90, 11, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#333">Licencias (L)</text>
                    </svg>
                </th>
                <th class="th-vert-res">
                    <svg width="16" height="75" xmlns="http://www.w3.org/2000/svg">
                        <text x="11" y="73" transform="rotate(-90, 11, 73)" font-family="Arial" font-size="7" font-weight="bold" fill="#333">DÍAS TRABAJADOS</text>
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
