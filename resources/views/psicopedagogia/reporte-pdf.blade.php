<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Psicopedagogía</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px;
            padding: 15px;
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
            padding: 6px;
            text-align: center;
            font-size: 9px;
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
        <h2>REPORTE DE PSICOPEDAGOGÍA</h2>
        <h3>{{ strtoupper($curso->cur_nombre ?? 'TODOS LOS CURSOS') }}</h3>
        <p>PERÍODO: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 8%;">FECHA</th>
                <th style="width: 18%;">ESTUDIANTE</th>
                <th style="width: 10%;">CURSO</th>
                <th style="width: 20%;">CASO</th>
                <th style="width: 15%;">SOLUCIÓN</th>
                <th style="width: 12%;">KARDEX</th>
                <th style="width: 8%;">TIPO</th>
                <th style="width: 6%;">CONTACTO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($casos as $index => $caso)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $caso->psico_fecha->format('d/m/Y') }}</td>
                    <td style="text-align: left;">{{ strtoupper($caso->estudiante->est_nombres ?? 'N/A') }} {{ strtoupper($caso->estudiante->est_apellidos ?? '') }}</td>
                    <td>{{ $caso->estudiante->curso->cur_nombre ?? '-' }}</td>
                    <td style="text-align: left;">{{ \Str::limit($caso->psico_caso, 50) }}</td>
                    <td style="text-align: left;">{{ \Str::limit($caso->psico_solucion, 40) }}</td>
                    <td style="text-align: left;">{{ \Str::limit($caso->psico_acuerdo, 30) }}</td>
                    <td>{{ $caso->psico_tipo_acuerdo }}</td>
                    <td>{{ $caso->estudiante->padres->first()->pfam_nombres ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No hay casos registrados</td>
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
