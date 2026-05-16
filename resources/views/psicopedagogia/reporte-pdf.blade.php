<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Psicopedagogía</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            padding: 12px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        .logo img {
            width: 70px;
            height: auto;
        }
        .header-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 10px;
        }
        .header-info h3 {
            font-size: 11px;
            margin: 0;
            line-height: 1.3;
        }
        .header-info p {
            font-size: 8px;
            margin: 2px 0;
        }
        .fecha-box {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #ff6b6b;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10px;
        }
        .title-section {
            text-align: center;
            margin: 15px 0;
        }
        .title-section h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 3px 0;
        }
        .title-section h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }
        .title-section p {
            font-size: 10px;
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            font-size: 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        td.txt {
            text-align: left;
            font-size: 7.5px;
            line-height: 1.25;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 15px;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="fecha-box">
        Fecha<br>{{ now()->format('d/m/Y') }}
    </div>

    <div class="header">
        <div class="logo">
            @php $sc = \App\Models\SistemaConfiguracion::actual(); @endphp
            @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" alt="Logo">
            @elseif(file_exists(public_path('img/logo.png')))
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
        <h2>REPORTE DE PSICOPEDAGOGÍA</h2>
        <h3>{{ strtoupper($curso->cur_nombre ?? 'TODOS LOS CURSOS') }}</h3>
        <p>PERÍODO: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 7%;">FECHA</th>
                <th style="width: 12%;">ESTUDIANTE</th>
                <th style="width: 7%;">CURSO</th>
                <th style="width: 18%;">CASO</th>
                <th style="width: 14%;">SOLUCIÓN</th>
                <th style="width: 15%;">KARDEX</th>
                <th style="width: 12%;">OBSERVACIONES</th>
                <th style="width: 5%;">TIPO</th>
                <th style="width: 7%;">CONTACTO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($casos as $index => $caso)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $caso->psico_fecha->format('d/m/Y') }}</td>
                    <td class="txt">{{ strtoupper($caso->estudiante->est_nombres ?? 'N/A') }} {{ strtoupper($caso->estudiante->est_apellidos ?? '') }}</td>
                    <td>{{ $caso->estudiante->curso->cur_nombre ?? '-' }}</td>
                    <td class="txt">{{ $caso->psico_caso }}</td>
                    <td class="txt">{{ $caso->psico_solucion }}</td>
                    <td class="txt">{{ $caso->psico_acuerdo }}</td>
                    <td class="txt">{{ $caso->psico_observaciones }}</td>
                    <td>{{ $caso->psico_tipo_acuerdo }}</td>
                    <td class="txt">{{ $caso->estudiante->padres->first()->pfam_nombres ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No hay casos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
