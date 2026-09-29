<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cursos y cantidad de estudiantes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; padding: 18px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 8px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 18px; right: 18px; text-align: right; font-size: 9px; }
        .title-section { text-align: center; margin: 12px 0; }
        .title-section h2 { font-size: 13px; }
        .title-section p { font-size: 9px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px 5px; font-size: 9px; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        td { text-align: center; }
        td.izq { text-align: left; }
        .nivel-row td { background-color: #e8e8e8; font-weight: bold; text-align: left; }
        .total-row td { background-color: #d9d9d9; font-weight: bold; }
        .footer { position: fixed; bottom: 10px; left: 18px; font-size: 7px; color: #666; }
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
            <h3>UNIDAD EDUCATIVA PRIVADA</h3>
            <h3>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>CURSOS Y CANTIDAD DE ESTUDIANTES</h2>
        <p>Gestión {{ $gestion }} — {{ $etiquetaEstado }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:7%;">N°</th>
                <th style="width:15%;">CÓDIGO</th>
                <th style="width:34%;">CURSO</th>
                <th style="width:16%;">NIVEL</th>
                <th style="width:14%;">CUPO</th>
                <th style="width:14%;">ESTUDIANTES</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 0; $totalGeneral = 0; @endphp
            @foreach($porNivel as $nivel => $cursosNivel)
                @php $subtotal = $cursosNivel->sum('estudiantes_count'); $totalGeneral += $subtotal; @endphp
                <tr class="nivel-row">
                    <td colspan="5">{{ $nivel ?: 'SIN NIVEL' }}</td>
                    <td>{{ $subtotal }}</td>
                </tr>
                @foreach($cursosNivel as $curso)
                    @php $n++; @endphp
                    <tr>
                        <td>{{ $n }}</td>
                        <td>{{ $curso->cur_codigo }}</td>
                        <td class="izq">{{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }}</td>
                        <td>{{ $curso->cur_nivel ?: '-' }}</td>
                        <td>{{ $curso->cur_cupo ?: '-' }}</td>
                        <td>{{ $curso->estudiantes_count }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">TOTAL DE ESTUDIANTES</td>
                <td>{{ $totalGeneral }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">TOTAL DE CURSOS</td>
                <td>{{ $n }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} — Sólo se cuentan estudiantes activos (no retirados).
    </div>
</body>
</html>
