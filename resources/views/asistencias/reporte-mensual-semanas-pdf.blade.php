<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asistencia mensual por semanas — {{ $mesNombre }} {{ $gestion }}</title>
    {{--
        Una fila por curso, una columna por semana. Pensado para entrar en UNA
        hoja: el ancho de las columnas se reparte según cuántas semanas tenga el
        mes y table-layout:fixed evita que el nombre del curso empuje la tabla.
    --}}
    @php
        $nSem  = count($semanas);
        $wCurso = 16;                       // % de la columna de curso
        $wTot   = 13;                       // % del bloque TOTAL
        $wSem   = $nSem > 0 ? (100 - $wCurso - 5 - $wTot) / $nSem : 0;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8px; padding: 12px; }
        .header { display: table; width: 100%; margin-bottom: 6px; }
        .logo { display: table-cell; width: 55px; vertical-align: middle; }
        .logo img { width: 48px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 9px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 12px; right: 12px; text-align: right; font-size: 7px; }
        .title-section { text-align: center; margin: 6px 0; }
        .title-section h2 { font-size: 11px; }
        .title-section p { font-size: 8px; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 2px 1px; font-size: 7px; text-align: center; word-wrap: break-word; }
        th { background-color: #1c4789; color: #fff; font-weight: bold; }
        th.sem { background-color: #2d5fa8; }
        th.tot { background-color: #0d2b52; }
        td.curso { text-align: left; font-size: 7.5px; }
        td.total { background-color: #ededed; font-weight: bold; }
        .nivel-row td { background-color: #dfe6ef; font-weight: bold; text-align: left; }
        .gran-total td { background-color: #cfd8e3; font-weight: bold; }
        .leyenda { margin-top: 5px; font-size: 7px; color: #444; }
        .footer { position: fixed; bottom: 6px; left: 12px; font-size: 6.5px; color: #666; }
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
            <h3>UNIDAD EDUCATIVA PRIVADA INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339 — Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>ASISTENCIA MENSUAL POR SEMANAS</h2>
        <p>{{ $mesNombre }} {{ $gestion }} — Turno {{ $turnoNombre }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: {{ $wCurso }}%;">CURSO</th>
                <th rowspan="2" style="width: 5%;">EST.</th>
                @foreach($semanas as $s)
                    <th class="sem" colspan="4" style="width: {{ $wSem }}%;">
                        {{ $s['etiqueta'] }}<br><span style="font-weight:normal;">{{ $s['habiles'] }} días</span>
                    </th>
                @endforeach
                <th class="tot" colspan="4" style="width: {{ $wTot }}%;">TOTAL DEL MES</th>
            </tr>
            <tr>
                @foreach($semanas as $s)
                    <th class="sem">P</th><th class="sem">F</th><th class="sem">A</th><th class="sem">L</th>
                @endforeach
                <th class="tot">P</th><th class="tot">F</th><th class="tot">A</th><th class="tot">L</th>
            </tr>
        </thead>
        <tbody>
            @php $nivelPrevio = null; @endphp
            @forelse($filas as $f)
                @if($f['nivel'] !== $nivelPrevio)
                    @php $nivelPrevio = $f['nivel']; @endphp
                    <tr class="nivel-row">
                        <td colspan="{{ 2 + ($nSem * 4) + 4 }}">{{ $f['nivel'] ?: 'SIN NIVEL' }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="curso">{{ $f['curso'] }}</td>
                    <td>{{ $f['estudiantes'] }}</td>
                    @foreach($semanas as $i => $s)
                        @php $d = $f['semanas'][$i] ?? ['pres'=>0,'fal'=>0,'atr'=>0,'lic'=>0]; @endphp
                        <td>{{ $d['pres'] }}</td>
                        <td>{{ $d['fal'] }}</td>
                        <td>{{ $d['atr'] }}</td>
                        <td>{{ $d['lic'] }}</td>
                    @endforeach
                    <td class="total">{{ $f['total']['pres'] }}</td>
                    <td class="total">{{ $f['total']['fal'] }}</td>
                    <td class="total">{{ $f['total']['atr'] }}</td>
                    <td class="total">{{ $f['total']['lic'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 2 + ($nSem * 4) + 4 }}">No hay cursos con estudiantes para este filtro.</td></tr>
            @endforelse

            <tr class="gran-total">
                <td class="curso">TOTAL GENERAL</td>
                <td>{{ collect($filas)->sum('estudiantes') }}</td>
                @foreach($semanas as $i => $s)
                    <td>{{ $totalesSemana[$i]['pres'] ?? 0 }}</td>
                    <td>{{ $totalesSemana[$i]['fal'] ?? 0 }}</td>
                    <td>{{ $totalesSemana[$i]['atr'] ?? 0 }}</td>
                    <td>{{ $totalesSemana[$i]['lic'] ?? 0 }}</td>
                @endforeach
                <td>{{ $granTotal['pres'] }}</td>
                <td>{{ $granTotal['fal'] }}</td>
                <td>{{ $granTotal['atr'] }}</td>
                <td>{{ $granTotal['lic'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="leyenda">
        <strong>P</strong> presencias · <strong>F</strong> faltas · <strong>A</strong> atrasos · <strong>L</strong> días con licencia.
        Las semanas van de lunes a viernes, recortadas al mes; "días" es la cantidad de días hábiles descontando feriados.
    </div>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
