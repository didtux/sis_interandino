<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia Anual</title>
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
        <h2>REPORTE DE ASISTENCIA ANUAL</h2>
        <p><strong>CURSO:</strong> {{ $curso->cur_nombre }} | <strong>TURNO:</strong> {{ strtoupper($turnoNombre ?? 'Mañana') }} | <strong>GESTIÓN {{ $year }}</strong></p>
    </div>

    @if(!empty($turnoNoAplica))
        <div style="background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:6px;text-align:center;font-size:9px;margin-top:6px;">
            <strong>AVISO:</strong> El curso <strong>{{ $curso->cur_nombre }}</strong> no tiene configuración de horario para el turno <strong>{{ strtoupper($turnoNombre ?? '') }}</strong>. Los valores se muestran en cero.
        </div>
    @endif

    @php
        $numTrimestres = count($trimestresConfig);
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">ESTUDIANTE</th>
                @foreach($trimestresConfig as $numTrim => $trimData)
                    <th colspan="5">{{ $trimData['nombre'] ?? "TRIMESTRE $numTrim" }}</th>
                @endforeach
                <th colspan="5">TOTAL ANUAL</th>
            </tr>
            <tr>
                @for($i = 0; $i < $numTrimestres + 1; $i++)
                    <th>D.T.</th>
                    <th>T.L.</th>
                    <th>T.F.</th>
                    <th>T.A.</th>
                    <th>TOTAL</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $index => $estudiante)
                @php
                    $totalAnual = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];
                    $estData = $datosEstudiantes[$estudiante->est_codigo] ?? [];
                    foreach ($trimestresConfig as $numTrim => $trimData) {
                        $d = $estData[$numTrim] ?? ['dt'=>0,'tl'=>0,'tf'=>0,'ta'=>0,'total'=>0];
                        $totalAnual['dt'] += $d['dt'];
                        $totalAnual['tl'] += $d['tl'];
                        $totalAnual['tf'] += $d['tf'];
                        $totalAnual['ta'] += $d['ta'];
                        $totalAnual['total'] += $d['total'];
                    }
                    $retirado = ($estudiante->est_visible ?? 1) == 0;
                @endphp
                <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ isset($lista) && isset($lista[$estudiante->est_codigo]) ? $lista[$estudiante->est_codigo] : $index + 1 }}</td>
                    <td class="estudiante" style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">
                        {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}
                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 3px;border-radius:2px;font-size:8px;margin-left:3px;">RETIRADO</span>@endif
                    </td>

                    @foreach($trimestresConfig as $numTrim => $trimData)
                        @php $d = $estData[$numTrim] ?? ['dt'=>0,'tl'=>0,'tf'=>0,'ta'=>0,'total'=>0]; @endphp
                        <td>{{ $d['dt'] }}</td>
                        <td>{{ $d['tl'] }}</td>
                        <td>{{ $d['tf'] }}</td>
                        <td>{{ $d['ta'] }}</td>
                        <td>{{ $d['total'] }}</td>
                    @endforeach

                    <td class="total-col">{{ $totalAnual['dt'] }}</td>
                    <td class="total-col">{{ $totalAnual['tl'] }}</td>
                    <td class="total-col">{{ $totalAnual['tf'] }}</td>
                    <td class="total-col">{{ $totalAnual['ta'] }}</td>
                    <td class="total-col">{{ $totalAnual['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="leyenda">
        <strong>Leyenda:</strong> D.T. = Días Trabajados | T.L. = Total Licencias | T.F. = Total Faltas | T.A. = Total Atrasos
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>Página 1
    </div>
</body>
</html>
