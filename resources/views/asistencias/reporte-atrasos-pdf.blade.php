<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Atrasos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }
        .header-table { width: 100%; margin-bottom: 15px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 60px; }
        .titulo { text-align: center; }
        .titulo h3 { margin: 0; font-size: 12px; }
        .titulo p { margin: 2px 0; font-size: 9px; }
        .fecha-badge { float: right; background-color: #ff6b6b; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .curso-titulo { background-color: #000; color: white; padding: 8px; text-align: center; font-size: 11px; font-weight: bold; margin: 15px 0 10px 0; }
        .fecha-reporte { text-align: center; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f0f0f0; font-weight: bold; font-size: 9px; }
        td { font-size: 9px; }
        .numero { width: 30px; }
        .fecha-col { width: 80px; }
        .hora-col { width: 70px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                <img src="{{ public_path('img/logo.png') }}" alt="Logo" class="logo">
            </td>
            <td class="titulo">
                <h3>Unidad Educativa</h3>
                <h3>INTERANDINO BOLIVIANO</h3>
                <p>Dir. Calle Victor Gutierrez Nro 3339</p>
                <p>Teléfonos: 2840320</p>
            </td>
            <td style="width: 150px; text-align: right;">
                <div class="fecha-badge">
                    Fecha<br>{{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 5px;">
        REPORTE DE ATRASOS
    </div>

    <div class="curso-titulo">{{ $curso->cur_nombre }}</div>
    
    @if($fecha)
        <div class="fecha-reporte">FECHA: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="numero">#</th>
                <th class="fecha-col">FECHA</th>
                <th class="hora-col">INGRESO</th>
                <th>ESTUDIANTE</th>
                <th>CONTACTO</th>
                <th>TELEFONO</th>
                <th>OBSERVACION</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atrasos as $index => $atraso)
                <tr>
                    <td class="numero">{{ isset($lista) && $atraso->estudiante && isset($lista[$atraso->estudiante->est_codigo]) ? $lista[$atraso->estudiante->est_codigo] : $index + 1 }}</td>
                    <td class="fecha-col">{{ \Carbon\Carbon::parse($atraso->atraso_fecha)->format('d/m/Y') }}</td>
                    <td class="hora-col">{{ $atraso->atraso_hora }}</td>
                    <td style="text-align: left;">{{ $atraso->estudiante->est_nombres ?? '' }} {{ $atraso->estudiante->est_apellidos ?? '' }}</td>
                    <td>{{ $atraso->estudiante->padres->first()->padre_nombre ?? '' }}</td>
                    <td>{{ $atraso->estudiante->est_celular ?? '' }}</td>
                    <td>{{ $atraso->minutos_atraso }} min</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay atrasos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 8px; text-align: right;">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
