<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Ingresos de Transporte</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9px;
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
            text-align: center;
        }
        .title-section {
            text-align: center;
            margin: 10px 0;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .title-section h2 {
            font-size: 13px;
            font-weight: bold;
            margin: 3px 0;
        }
        .title-section p {
            font-size: 9px;
            margin: 2px 0;
        }
        .ruta-header {
            background: #2c3e50;
            color: white;
            padding: 6px 8px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 8px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
        }
        th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            font-size: 7px;
        }
        td.text-left { text-align: left; }
        td.text-right { text-align: right; }
        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        .total-general {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }
        .saldo-final {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        .saldo-box {
            border: 2px solid #000;
            display: inline-block;
            padding: 10px 30px;
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 15px;
            font-size: 7px;
            color: #666;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="fecha-box">
        Fecha<br>{{ now()->format('d/m/Y') }}
    </div>

    <div class="header">
        <div class="logo">
            @php
                $logoPath = public_path('img/logo.png');
                $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
            @endphp
            @if($logoBase64)
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa<br>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339</p>
            <p>Teléfonos: 2840320</p>
        </div>
    </div>

    <div class="title-section">
        <h2>INGRESO DE TRANSPORTE ESCOLAR GESTIÓN {{ $gestion }}</h2>
        <p><strong>Responsable:</strong> Sr. {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}</p>
        <p><strong>Periodo:</strong> {{ $meses[$mesInicio] }} - {{ $meses[$mesFin] }}</p>
    </div>

    @php
        $totalGeneral = 0;
        $totalesGeneralesMes = array_fill($mesInicio, $mesFin - $mesInicio + 1, 0);
    @endphp

    @foreach($datosReporte as $index => $dato)
        @if($index > 0 && $index % 3 == 0)
            <div class="page-break"></div>
        @endif

        <div class="ruta-header">
            {{ $dato['ruta'] }}
            @if($dato['bus_numero']) - BUS Nº {{ $dato['bus_numero'] }} @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 25px;">Nº</th>
                    <th style="width: 150px;">CONDUCTOR</th>
                    <th style="width: 80px;">BUS ESCOLAR</th>
                    @for($mes = $mesInicio; $mes <= $mesFin; $mes++)
                        <th style="width: 40px;">{{ strtoupper(substr($meses[$mes], 0, 3)) }}</th>
                    @endfor
                    <th style="width: 55px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">Sr. {{ $dato['chofer'] }}</td>
                    <td>@if($dato['bus_numero']) BUS Nº {{ $dato['bus_numero'] }} @else - @endif</td>
                    @for($mes = $mesInicio; $mes <= $mesFin; $mes++)
                        <td class="text-right">
                            @if(($dato['meses'][$mes] ?? 0) > 0)
                                {{ number_format($dato['meses'][$mes], 2) }}
                            @else
                                0.00
                            @endif
                        </td>
                        @php $totalesGeneralesMes[$mes] += $dato['meses'][$mes] ?? 0; @endphp
                    @endfor
                    <td class="text-right"><strong>{{ number_format($dato['total'], 2) }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL {{ strtoupper($meses[$mesInicio]) }} - {{ strtoupper($meses[$mesFin]) }}</strong></td>
                    @for($mes = $mesInicio; $mes <= $mesFin; $mes++)
                        <td class="text-right"><strong>{{ number_format($dato['meses'][$mes] ?? 0, 2) }}</strong></td>
                    @endfor
                    <td class="text-right"><strong>{{ number_format($dato['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        @php $totalGeneral += $dato['total']; @endphp
    @endforeach

    <table style="margin-top: 15px;">
        <tr class="total-general">
            <td colspan="3" class="text-right" style="padding: 8px;"><strong>TOTAL INGRESOS POR SERVICIO DE TRANSPORTE ESCOLAR {{ $gestion }}</strong></td>
            @for($mes = $mesInicio; $mes <= $mesFin; $mes++)
                <td class="text-right" style="padding: 8px;"><strong>{{ number_format($totalesGeneralesMes[$mes], 2) }}</strong></td>
            @endfor
            <td class="text-right" style="padding: 8px; font-size: 10px;"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
        </tr>
    </table>

    <div class="saldo-final">
        <p><strong>SALDO ENTREGADO A DIR. GRAL.</strong></p>
        <div class="saldo-box">
            {{ number_format($totalGeneral, 2) }}
        </div>
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
