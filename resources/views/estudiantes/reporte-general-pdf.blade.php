<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Estudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 60px; }
        .titulo { text-align: center; }
        .titulo h2 { margin: 0; font-size: 14px; }
        .titulo p { margin: 2px 0; font-size: 10px; }
        .curso-titulo { background-color: #4472C4; color: white; padding: 8px; text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; font-size: 8px; }
        td { font-size: 8px; }
        .numero { text-align: center; width: 30px; }
        .fecha { text-align: center; width: 70px; }
        .sexo { text-align: center; width: 40px; }
        .footer { margin-top: 15px; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                <img src="{{ public_path('img/logo.png') }}" alt="Logo" class="logo">
            </td>
            <td class="titulo">
                <h2>Unidad Educativa</h2>
                <h2>INTERANDINO BOLIVIANO</h2>
                <p>Dir. Calle Victor Gutierrez Nro 3339</p>
                <p>Teléfonos: 2840320</p>
            </td>
            <td style="width: 150px; text-align: right;">
                <p><strong>Fecha:</strong> {{ now()->format('d/m/Y') }}</p>
                <p><strong>Control-cole</strong></p>
            </td>
        </tr>
    </table>

    @if($curso)
        <div class="curso-titulo">{{ $curso->cur_nombre }}</div>
    @else
        <div class="curso-titulo">TODOS LOS CURSOS</div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="numero">#</th>
                <th>Paterno</th>
                <th>Materno</th>
                <th>Nombre</th>
                <th>CI</th>
                <th class="fecha">Fecha<br>Inscripción</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $index => $est)
                @php $retirado = ($est->est_visible ?? 1) == 0; @endphp
                <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                    <td class="numero" style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ $index + 1 }}</td>
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ strtoupper(explode(' ', $est->est_apellidos)[0] ?? '') }}</td>
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ strtoupper(explode(' ', $est->est_apellidos)[1] ?? '') }}</td>
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">
                        {{ strtoupper($est->est_nombres) }}
                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 4px;border-radius:2px;font-size:8px;margin-left:3px;">RETIRADO</span>@endif
                    </td>
                    <td style="{{ $retirado ? 'color:#c0392b;' : '' }}">{{ $est->est_ci ?? '' }}</td>
                    <td class="fecha" style="{{ $retirado ? 'color:#c0392b;' : '' }}">{{ $est->est_fecha ? $est->est_fecha->format('Y-m-d') : ($est->created_at ? $est->created_at->format('Y-m-d') : date('Y-m-d')) }}</td>
                    <td>{{ $retirado ? 'RETIRADO' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
