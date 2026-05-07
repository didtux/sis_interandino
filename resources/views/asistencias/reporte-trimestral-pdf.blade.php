<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia - Trimestre {{ $trimestre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; padding: 10px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 10px; right: 10px; background-color: #dc3545; color: white; padding: 6px 12px; border-radius: 15px; font-weight: bold; font-size: 8px; text-align: center; }
        .title-section { text-align: center; margin: 10px 0; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .title-section h2 { font-size: 12px; font-weight: bold; margin: 2px 0; }
        .title-section p { font-size: 8px; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; }
        th { background-color: #2c3e50; color: white; font-size: 6px; font-weight: bold; }
        .estudiante { text-align: left; font-size: 6px; }
        .total-col { background-color: #ffeb3b; font-weight: bold; }
        .leyenda { margin-top: 10px; font-size: 8px; }
        .footer { position: fixed; bottom: 10px; left: 10px; font-size: 7px; color: #666; }
        .stats-title { background:#27ae60; color:#fff; font-weight:bold; font-size:11px; padding:6px; text-align:center; margin-top:14px; }
        .stats-table th { background:#3498db; color:#fff; font-size:8px; }
        .stats-table td { font-size:8px; padding:4px; }
        .stats-kpi th { background:#e74c3c; color:#fff; font-size:8px; }
        .stats-kpi td { font-size:8px; padding:4px; }
        .stats-dest th { background:#8e44ad; color:#fff; font-size:8px; }
        .stats-dest td { font-size:8px; padding:4px; }
        .row-atraso { background:#fff3cd; }
        .row-falta  { background:#f8d7da; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="fecha-box">
        Fecha<br>{{ now()->format('d/m/Y') }}
    </div>

    <div class="header">
        <div class="logo">
            @php $logoPath = isset($sistemaConfig) && $sistemaConfig && $sistemaConfig->config_logo ? public_path('storage/'.$sistemaConfig->config_logo) : public_path('img/logo.png'); @endphp
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>{{ $sistemaConfig->config_nombre_ue ?? 'Unidad Educativa INTERANDINO BOLIVIANO' }}</h3>
            <p>{{ $sistemaConfig->config_direccion ?? 'Dir. Calle Victor Gutierrez Nro 3339' }}</p>
            <p>Teléfonos: {{ $sistemaConfig->config_telefono ?? '2840320' }}</p>
        </div>
    </div>

    <div class="title-section">
        <h2>REPORTE DE ASISTENCIA - {{ $trimestre }}{{ $trimestre == 1 ? 'ER' : ($trimestre == 2 ? 'DO' : 'ER') }} TRIMESTRE</h2>
        <p><strong>CURSO:</strong> {{ $curso->cur_nombre }} | <strong>GESTIÓN {{ $year ?? date('Y') }}</strong></p>
        @if(isset($periodo) && $periodo)
            <p>{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }} — {{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</p>
        @endif
    </div>

    @php $numMeses = isset($mesesConfig) ? count($mesesConfig) : 0; @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">ESTUDIANTE</th>
                @foreach(($mesesConfig ?? []) as $key => $m)
                    <th colspan="5">{{ $m['nombre'] }}</th>
                @endforeach
                <th colspan="5">TOTAL TRIMESTRE</th>
            </tr>
            <tr>
                @for($i = 0; $i < $numMeses + 1; $i++)
                    <th>D.T.</th><th>T.L.</th><th>T.F.</th><th>T.A.</th><th>TOTAL</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $index => $estudiante)
                @php
                    $dT = $datosEstudiantes[$estudiante->est_codigo] ?? ['dt'=>0,'tl'=>0,'tf'=>0,'ta'=>0,'total'=>0];
                    $retirado = ($estudiante->est_visible ?? 1) == 0;
                @endphp
                <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ isset($lista) && isset($lista[$estudiante->est_codigo]) ? $lista[$estudiante->est_codigo] : $index + 1 }}</td>
                    <td class="estudiante" style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">
                        {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}
                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 3px;border-radius:2px;font-size:8px;margin-left:3px;">RETIRADO</span>@endif
                    </td>
                    @foreach(($mesesConfig ?? []) as $key => $m)
                        @php $d = $datosMensuales[$estudiante->est_codigo][$key] ?? ['dt'=>0,'tl'=>0,'tf'=>0,'ta'=>0,'total'=>0]; @endphp
                        <td>{{ $d['dt'] }}</td>
                        <td>{{ $d['tl'] }}</td>
                        <td>{{ $d['tf'] }}</td>
                        <td>{{ $d['ta'] }}</td>
                        <td>{{ $d['total'] }}</td>
                    @endforeach
                    <td class="total-col">{{ $dT['dt'] }}</td>
                    <td class="total-col">{{ $dT['tl'] }}</td>
                    <td class="total-col">{{ $dT['tf'] }}</td>
                    <td class="total-col">{{ $dT['ta'] }}</td>
                    <td class="total-col">{{ $dT['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="leyenda">
        <strong>Leyenda:</strong> D.T. = Días Trabajados | T.L. = Total Licencias | T.F. = Total Faltas | T.A. = Total Atrasos
    </div>

    @if(isset($stats))
    <div class="page-break"></div>
    <div class="stats-title">CUADRO ESTADÍSTICO DEL PERIODO</div>

    <table class="stats-table">
        <thead>
            <tr><th colspan="5">RESUMEN GENERAL</th></tr>
            <tr><th>CONCEPTO</th><th>TOTAL</th><th>PORCENTAJE</th><th>PROMEDIO POR ESTUDIANTE</th><th>OBSERVACIONES</th></tr>
        </thead>
        <tbody>
            <tr><td style="text-align:left;">Total Estudiantes</td><td colspan="3"><strong>{{ $stats['estudiantes'] }}</strong></td><td></td></tr>
            <tr><td style="text-align:left;">Total Días Hábiles</td><td colspan="3"><strong>{{ $stats['dias_habiles'] }}</strong></td><td></td></tr>
            <tr style="background:#e6f4ea;"><td style="text-align:left;">Presentes</td><td>{{ $stats['presentes'] }}</td><td>{{ $stats['pct_presentes'] }}%</td><td>{{ $stats['prom_presentes'] }}</td><td>Asistencia regular</td></tr>
            <tr class="row-atraso"><td style="text-align:left;">Atrasos</td><td>{{ $stats['atrasos'] }}</td><td>{{ $stats['pct_atrasos'] }}%</td><td>{{ $stats['prom_atrasos'] }}</td><td>Llegadas tardías</td></tr>
            <tr><td style="text-align:left;">Licencias</td><td>{{ $stats['licencias'] }}</td><td>{{ $stats['pct_licencias'] }}%</td><td>-</td><td>Ausencias justificadas</td></tr>
            <tr class="row-falta"><td style="text-align:left;">Faltas</td><td>{{ $stats['faltas'] }}</td><td>{{ $stats['pct_faltas'] }}%</td><td>{{ $stats['prom_faltas'] }}</td><td>Ausencias sin justificar</td></tr>
        </tbody>
    </table>

    <table class="stats-kpi" style="margin-top:8px;">
        <thead><tr><th colspan="3">INDICADORES CLAVE DE RENDIMIENTO</th></tr></thead>
        <tbody>
            <tr><td style="text-align:left;">Tasa de Asistencia Efectiva</td><td style="background:#f1c40f;font-weight:bold;">{{ $stats['tasa_efectiva'] }}%</td><td style="text-align:left;">{{ $stats['tasa_efectiva'] >= 90 ? 'Excelente' : ($stats['tasa_efectiva'] >= 80 ? 'Regular' : 'Deficiente') }}</td></tr>
            <tr><td style="text-align:left;">Tasa de Ausencias</td><td style="background:#2ecc71;color:#fff;font-weight:bold;">{{ $stats['tasa_ausencia'] }}%</td><td style="text-align:left;">{{ $stats['tasa_ausencia'] <= 10 ? 'Dentro del rango aceptable' : 'Sobre el rango aceptable' }}</td></tr>
            <tr><td style="text-align:left;">Puntualidad del Curso</td><td><strong>{{ $stats['puntualidad'] }}%</strong></td><td style="text-align:left;">{{ $stats['puntualidad'] >= 95 ? 'Muy buena' : ($stats['puntualidad'] >= 85 ? 'Buena' : 'Regular') }}</td></tr>
        </tbody>
    </table>

    <table class="stats-dest" style="margin-top:8px;">
        <thead><tr><th colspan="3">ESTUDIANTES DESTACADOS</th></tr></thead>
        <tbody>
            <tr><td style="text-align:left;width:30%;">Mejor Asistencia</td><td>{{ $stats['mejor_nombre'] }}</td><td><strong>{{ $stats['mejor_dias'] }}/{{ $stats['mejor_total'] }} días</strong></td></tr>
            <tr><td style="text-align:left;">Asistencia Perfecta</td><td colspan="2"><strong>{{ $stats['perfectos'] }} estudiante(s)</strong> con asistencia al 100%</td></tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
