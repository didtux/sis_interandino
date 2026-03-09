<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Inscripciones</title>
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
        .totales {
            margin-top: 15px;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
        }
        .totales table {
            width: 40%;
            margin-left: auto;
            border: none;
        }
        .totales td {
            border: none;
            padding: 3px 8px;
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
        <h2>REPORTE DE INSCRIPCIONES</h2>
        @if($request->fecha_inicio && $request->fecha_fin)
            <p>PERÍODO: {{ \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y') }}</p>
        @endif
        @if($request->tipo_factura === '1')
            <p>TIPO: SIN FACTURA</p>
        @elseif($request->tipo_factura === '0')
            <p>TIPO: CON FACTURA</p>
        @endif
        @if($request->estado === 'activas')
            <p>ESTADO: ACTIVAS</p>
        @elseif($request->estado === '0')
            <p>ESTADO: ANULADAS</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">CÓDIGO</th>
                <th style="width: 20%;">ESTUDIANTE</th>
                <th style="width: 12%;">CURSO</th>
                <th style="width: 6%;">GESTIÓN</th>
                <th style="width: 8%;">FECHA</th>
                <th style="width: 8%;">DESCUENTO</th>
                <th style="width: 8%;">MONTO FINAL</th>
                <th style="width: 8%;">PAGADO</th>
                <th style="width: 8%;">SALDO</th>
                <th style="width: 8%;">TIPO</th>
                <th style="width: 8%;">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscripciones as $i)
                <tr>
                    <td>{{ $i->insc_codigo }}</td>
                    <td style="text-align: left;">{{ strtoupper($i->estudiante->est_nombres ?? 'N/A') }} {{ strtoupper($i->estudiante->est_apellidos ?? '') }}</td>
                    <td>{{ $i->curso->cur_nombre ?? 'N/A' }}</td>
                    <td>{{ $i->insc_gestion }}</td>
                    <td>{{ $i->insc_fecha->format('d/m/Y') }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_monto_descuento, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_monto_final ?: $i->insc_monto_total, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_monto_pagado, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($i->insc_saldo, 2) }}</td>
                    <td>{{ $i->insc_sin_factura ? 'SIN FACT' : 'CON FACT' }}</td>
                    <td>
                        @if($i->insc_estado == 0)
                            ANULADA
                        @elseif($i->insc_monto_pagado >= 300)
                            PAGADA
                        @elseif($i->insc_monto_pagado < 300)
                            PENDIENTE
                        @else
                            CANCELADA
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">No hay inscripciones registradas</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totales">
        <table>
            <tr>
                <td>TOTAL PAGADO:</td>
                <td style="text-align: right;">Bs. {{ number_format($pagado, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
