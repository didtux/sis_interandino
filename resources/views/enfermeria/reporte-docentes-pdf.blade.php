<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Enfermería - Docentes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h3 {
            font-size: 11px;
            margin: 1px 0;
        }
        .header p {
            font-size: 8px;
            margin: 1px 0;
        }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 15px 0;
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
        .logo {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 50px;
        }
    </style>
</head>
<body>
    @if(file_exists(public_path('img/logo.png')))
        <img src="{{ public_path('img/logo.png') }}" class="logo" alt="Logo">
    @endif

    <div class="header">
        <h3>U.E. PRIVADA INTERANDINO BOLIVIANO</h3>
        <p>C/ V. GUTIERREZ N° 3339</p>
        <p>TELEFONO 2840320 - 67304340</p>
    </div>

    <div class="title">REPORTE DE ENFERMERÍA - DOCENTES</div>
    <p style="text-align: center; font-size: 10px; margin-bottom: 10px;">PERÍODO: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 10%;">FECHA</th>
                <th style="width: 8%;">HORA</th>
                <th style="width: 25%;">DOCENTE</th>
                <th style="width: 15%;">DX DETALLE</th>
                <th style="width: 20%;">MEDICAMENTOS</th>
                <th style="width: 17%;">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $index => $registro)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $registro->enf_fecha->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($registro->enf_hora)->format('H:i') }}</td>
                    <td style="text-align: left;">{{ strtoupper($registro->docente->doc_nombres ?? 'N/A') }} {{ strtoupper($registro->docente->doc_apellidos ?? '') }}</td>
                    <td>{{ $registro->enf_dx_detalle }}</td>
                    <td style="text-align: left;">{{ \Str::limit($registro->enf_medicamentos, 50) }}</td>
                    <td style="text-align: left;">{{ \Str::limit($registro->enf_observaciones, 40) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay registros</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 8px; color: #666;">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </p>
</body>
</html>
