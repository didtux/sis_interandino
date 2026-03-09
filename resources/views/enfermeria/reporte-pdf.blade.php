<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Enfermería</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 8px;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h3 {
            font-size: 9px;
            margin: 1px 0;
        }
        .header p {
            font-size: 7px;
            margin: 1px 0;
        }
        .title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 10px 0;
        }
        .curso-info {
            font-size: 9px;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 7px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .rotate {
            writing-mode: vertical-rl;
            white-space: nowrap;
        }
        .student-name {
            text-align: left;
            font-size: 7px;
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

    <div class="title">REPORTE DE ENFERMERIA</div>
    <div class="title" style="font-size: 10px; margin-top: 2px;">PRIMER TRIMESTRE </div>

    <div class="curso-info"><strong>Curso:</strong> {{ $curso->cur_nombre ?? 'TODOS LOS CURSOS' }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">N°</th>
                <th rowspan="2" style="width: 200px;">NOMBRE DE ALUMNO</th>
                @foreach($meses as $mes)
                    @php
                        $mesNombre = \Carbon\Carbon::parse($mes.'-01')->locale('es')->translatedFormat('F');
                    @endphp
                    <th colspan="3">{{ strtoupper($mesNombre) }}</th>
                @endforeach
                <th colspan="3">TOTAL</th>
            </tr>
            <tr>
                @foreach($meses as $mes)
                    <th class="rotate">HIGIENE PERSONAL</th>
                    <th class="rotate">ATENCION MEDICA</th>
                    <th class="rotate">DOTACION DE MEDICAMENTOS</th>
                @endforeach
                <th class="rotate">HIGIENE PERSONAL</th>
                <th class="rotate">ATENCION MEDICA</th>
                <th class="rotate">DOTACION DE MEDICAMENTOS</th>
            </tr>
        </thead>
        <tbody>
            @php $contador = 0; @endphp
            @forelse($estudiantes as $data)
                @php $contador++; @endphp
                <tr>
                    <td>{{ $contador }}</td>
                    <td class="student-name">{{ strtoupper($data['estudiante']->est_nombres) }} {{ strtoupper($data['estudiante']->est_apellidos) }}</td>
                    @foreach($meses as $mes)
                        <td>{{ $data['meses'][$mes]['HIGIENE PERSONAL'] ?? 0 }}</td>
                        <td>{{ $data['meses'][$mes]['ATENCIÓN MÉDICA'] ?? 0 }}</td>
                        <td>{{ $data['meses'][$mes]['DOTACIÓN DE MEDICAMENTOS'] ?? 0 }}</td>
                    @endforeach
                    <td>{{ $data['total_higiene'] }}</td>
                    <td>{{ $data['total_atencion'] }}</td>
                    <td>{{ $data['total_medicamentos'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + (count($meses) * 3) + 3 }}">No hay registros</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
