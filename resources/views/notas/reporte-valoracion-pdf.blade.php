<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Registro de Valoración - {{ $asignacion->curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; padding: 5mm; }

        /* Cabecera institucional */
        .header { display: table; width: 100%; margin-bottom: 4px; }
        .logo { display: table-cell; width: 50px; vertical-align: middle; }
        .logo img { width: 42px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 9px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 6px; margin: 0; }

        /* Título */
        .title-section { text-align: center; margin: 4px 0 6px; border-bottom: 2px solid #000; padding-bottom: 3px; }
        .title-section h2 { font-size: 10px; font-weight: bold; }

        /* Info del registro */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { padding: 1px 4px; font-size: 7.5px; border: none; vertical-align: top; }
        .lbl { font-weight: normal; color: #555; }
        .val { font-weight: bold; }

        /* Caja trimestre */
        .trim-box { position: absolute; top: 5mm; right: 5mm; border: 2px solid #333; text-align: center; padding: 3px 12px; }
        .trim-box .trim-label { font-size: 6px; font-weight: bold; }
        .trim-box .trim-value { font-size: 22px; font-weight: bold; line-height: 1.1; }

        /* Tabla principal */
        table.main { width: 100%; border-collapse: collapse; }
        table.main th, table.main td { border: 0.5px solid #555; padding: 1.5px 2px; text-align: center; font-size: 6.5px; }
        table.main th { font-weight: bold; }

        /* Headers dimensiones */
        .dim-ser { background: #f5c6cb; color: #000; }
        .dim-saber { background: #b8daff; color: #000; }
        .dim-hacer { background: #c3e6cb; color: #000; }
        .dim-auto { background: #d4a5d0; color: #000; }
        .dim-header { font-size: 7px; font-weight: bold; }
        .sub-header { background: #f8f9fa; font-size: 5.5px; font-weight: bold; }
        .prom-header { background: #e2e3e5; font-weight: bold; font-size: 5.5px; }

        /* Columnas especiales */
        .col-num { width: 14px; font-weight: bold; }
        .col-nombre { text-align: left !important; padding-left: 3px !important; font-size: 6.5px; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
        .prom-dim { background: #f0f0f0; font-weight: bold; }
        .col-rango { background: #fff3cd; font-size: 5.5px; font-weight: bold; }
        .col-prom-trim { background: #ffeeba; font-weight: bold; font-size: 7.5px; }
        .col-situacion { font-size: 5px; text-align: left !important; padding-left: 2px !important; }
        .aprobado { color: #155724; }
        .reprobado { color: #721c24; font-weight: bold; }

        .footer { position: fixed; bottom: 5mm; left: 5mm; font-size: 5.5px; color: #888; }
    </style>
</head>
<body>
    {{-- Caja trimestre --}}
    <div class="trim-box">
        <div class="trim-label">TRIMESTRE</div>
        <div class="trim-value">{{ $periodo->periodo_numero == 1 ? '1er.' : ($periodo->periodo_numero == 2 ? '2do.' : '3er.') }}</div>
    </div>

    {{-- Cabecera institucional --}}
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

    {{-- Título --}}
    <div class="title-section">
        <h2>REGISTRO DE VALORACIÓN {{ $periodo->periodo_numero }}º TRIMESTRE</h2>
    </div>

    {{-- Info del registro --}}
    <table class="info">
        <tr>
            <td class="lbl" style="width:14%;">UNIDAD EDUCATIVA</td>
            <td class="val" style="width:36%;">INTERANDINO BOLIVIANO</td>
            <td class="lbl" style="width:10%;">ÁREA</td>
            <td class="val" style="width:20%;">{{ mb_strtoupper($asignacion->materia->mat_nombre, 'UTF-8') }}</td>
            <td class="lbl" style="width:10%;">INICIO TRIM.</td>
            <td class="val" style="width:10%;">{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="lbl">NIVEL</td>
            <td class="val">{{ mb_strtoupper($asignacion->curso->cur_nombre, 'UTF-8') }}</td>
            <td class="lbl">DOCENTE</td>
            <td class="val">{{ mb_strtoupper($asignacion->docente->doc_nombres . ' ' . $asignacion->docente->doc_apellidos, 'UTF-8') }}</td>
            <td class="lbl">FIN TRIM.</td>
            <td class="val">{{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="lbl">GRADO</td>
            <td class="val">{{ mb_strtoupper($asignacion->curso->cur_nombre, 'UTF-8') }}</td>
            <td></td><td></td>
            <td class="lbl"><span style="color:#c0392b;">FECHA ACTUAL</span></td>
            <td class="val" style="color:#c0392b;">{{ now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    {{-- Tabla de notas --}}
    @php
        $dimClases = ['SER' => 'dim-ser', 'SABER' => 'dim-saber', 'HACER' => 'dim-hacer', 'AUTOEVALUACION' => 'dim-auto'];
    @endphp

    <table class="main">
        <thead>
            {{-- Fila: EVALUACIÓN DEL MAESTRO --}}
            <tr>
                <th colspan="2" style="background:#ddd;"></th>
                @php $totalSubCols = 0; @endphp
                @foreach($dimensiones as $dim)
                    @php $totalSubCols += $dim->dimension_columnas + 1; @endphp
                @endforeach
                <th colspan="{{ $totalSubCols }}" style="background:#2c3e50;color:#fff;font-size:7px;">EVALUACIÓN DEL MAESTRO</th>
                <th style="background:#fff3cd;font-size:5px;">RANGOS</th>
                <th style="background:#ffeeba;font-size:5.5px;font-weight:bold;">PROM.<br>TRIMEST.</th>
                <th style="background:#ddd;font-size:5px;">SITUACIÓN TRIMESTRAL</th>
            </tr>
            {{-- Fila: Dimensiones --}}
            <tr>
                <th colspan="2" style="background:#ddd;"></th>
                @foreach($dimensiones as $dim)
                    @php
                        $key = mb_strtoupper(trim($dim->dimension_nombre));
                        $cls = $dimClases[$key] ?? 'dim-ser';
                    @endphp
                    <th colspan="{{ $dim->dimension_columnas }}" class="{{ $cls }} dim-header">
                        {{ $dim->dimension_nombre }}/{{ $dim->dimension_valor_max }}
                    </th>
                    <th class="prom-header">PROMEDIO<br>{{ $dim->dimension_nombre }}</th>
                @endforeach
                <th rowspan="2" style="background:#fff3cd;width:16px;font-size:5px;"></th>
                <th rowspan="2" style="background:#ffeeba;width:22px;"></th>
                <th rowspan="2" style="background:#ddd;width:50px;font-size:5px;"></th>
            </tr>
            {{-- Fila: Sub-columnas --}}
            <tr>
                <th class="col-num" style="background:#ddd;">Nº</th>
                <th style="background:#ddd;text-align:left !important;font-size:6px;max-width:120px;">NÓMINA DE ESTUDIANTES</th>
                @foreach($dimensiones as $dim)
                    @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                        <th class="sub-header" style="width:18px;">{{ $c }}</th>
                    @endfor
                    <th class="prom-header" style="width:20px;"></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $fila)
                <tr>
                    <td class="col-num">{{ $fila['numero'] }}</td>
                    <td class="col-nombre">{{ mb_strtoupper($fila['nombre'], 'UTF-8') }}</td>
                    @foreach($dimensiones as $dim)
                        @php $dimData = $fila['dimensiones'][$dim->dimension_id] ?? []; @endphp
                        @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                            @php $val = $dimData['valores'][$c] ?? ''; @endphp
                            <td>{{ $val > 0 ? round($val) : '' }}</td>
                        @endfor
                        <td class="prom-dim">{{ ($dimData['promedio'] ?? 0) > 0 ? round($dimData['promedio']) : '' }}</td>
                    @endforeach
                    <td class="col-rango">{{ $fila['rango'] }}</td>
                    <td class="col-prom-trim">{{ $fila['promedio_trimestral'] > 0 ? round($fila['promedio_trimestral']) : '' }}</td>
                    <td class="col-situacion {{ $fila['promedio_trimestral'] >= 51 ? 'aprobado' : 'reprobado' }}">
                        {{ $fila['promedio_trimestral'] > 0 ? ($fila['promedio_trimestral'] >= 51 ? 'APROBADO' : 'REPROBADO') : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | {{ $asignacion->curso->cur_nombre }} | {{ $asignacion->materia->mat_nombre }} | {{ $periodo->periodo_nombre }} | Gestión {{ $gestion }}
    </div>
</body>
</html>
