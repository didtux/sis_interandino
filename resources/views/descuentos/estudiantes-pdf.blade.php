<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estudiantes con descuento — {{ $gestion }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 14px; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .logo { display: table-cell; width: 62px; vertical-align: middle; }
        .logo img { width: 54px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 8px; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 14px; right: 14px; text-align: right; font-size: 8px; }
        .title-section { text-align: center; margin: 10px 0; }
        .title-section h2 { font-size: 12px; }
        .title-section p { font-size: 9px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 3px 4px; font-size: 8px; word-wrap: break-word; }
        th { background-color: #1c4789; color: #fff; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        td.centro { text-align: center; }
        .curso-row td { background-color: #e8e8e8; font-weight: bold; }
        .total-row td { background-color: #d9d9d9; font-weight: bold; }
        .footer { position: fixed; bottom: 8px; left: 14px; font-size: 7px; color: #666; }
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
        <h2>ESTUDIANTES CON DESCUENTO</h2>
        <p>Gestión {{ $gestion === 'todas' ? '— todas las gestiones' : $gestion }} · {{ $inscripciones->count() }} estudiante(s)</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">N°</th>
                <th style="width:27%;">ESTUDIANTE</th>
                <th style="width:14%;">CURSO</th>
                <th style="width:22%;">DESCUENTO</th>
                <th style="width:11%;">ANUAL</th>
                <th style="width:11%;">DESCONTADO</th>
                <th style="width:10%;">FINAL</th>
            </tr>
        </thead>
        <tbody>
            @php $cursoPrevio = null; $n = 0; @endphp
            @forelse($inscripciones as $insc)
                @php $cursoNombre = $insc->estudiante->curso->cur_nombre ?? $insc->cur_codigo; @endphp
                @if($cursoNombre !== $cursoPrevio)
                    @php $cursoPrevio = $cursoNombre; @endphp
                    <tr class="curso-row"><td colspan="7">{{ mb_strtoupper($cursoNombre, 'UTF-8') }}</td></tr>
                @endif
                @php $n++; @endphp
                <tr>
                    <td class="centro">{{ $n }}</td>
                    <td>{{ $insc->estudiante->est_apellidos ?? '' }} {{ $insc->estudiante->est_nombres ?? '' }}</td>
                    <td class="centro">{{ $cursoNombre }}</td>
                    <td>{{ $insc->descuentos->map(fn($d) => $d->desc_nombre . ' ' . $d->desc_porcentaje . '%')->implode(', ') }}</td>
                    <td class="num">{{ number_format($insc->insc_monto_total, 2) }}</td>
                    <td class="num">{{ number_format($insc->insc_monto_descuento, 2) }}</td>
                    <td class="num">{{ number_format($insc->insc_monto_final, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="centro">No hay estudiantes con descuento para este filtro.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">TOTAL DESCONTADO</td>
                <td class="num">{{ number_format($totalDescuento, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} — No se cuentan las inscripciones anuladas.
    </div>
</body>
</html>
