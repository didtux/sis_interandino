<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Mensualidades</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8px; padding: 15px; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.3; }
        .header-info p { font-size: 8px; margin: 2px 0; }
        .fecha-box { position: absolute; top: 15px; right: 15px; background-color: #2196F3; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 10px; }
        .title-section { text-align: center; margin: 15px 0; }
        .title-section h2 { font-size: 14px; font-weight: bold; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; font-size: 7px; }
        th { background-color: #d0d0d0; font-weight: bold; }
        .curso-header { background-color: #4CAF50; color: white; font-weight: bold; text-align: left; padding: 5px; font-size: 9px; }
        .estudiante-cell { text-align: left; font-weight: bold; }
        .numero { text-align: right; }
        .total-row { background-color: #e0e0e0; font-weight: bold; }
        .footer { position: fixed; bottom: 10px; left: 15px; font-size: 8px; color: #666; }
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
        <h2>REPORTE DE MENSUALIDADES</h2>
        <p>Gestión {{ date('Y') }}</p>
    </div>

    @php
        $pagosPorCurso = $pagos->groupBy(function($pago) {
            return $pago->estudiante->cur_codigo ?? 'SIN_CURSO';
        });
        $totalGeneral = 0;
    @endphp

    @foreach($pagosPorCurso as $curCodigo => $pagosCurso)
        @php
            $curso = $pagosCurso->first()->estudiante->curso ?? null;
            if (!$curso) continue;
            
            $pagosPorEstudiante = $pagosCurso->groupBy('est_codigo');
        @endphp
        
        <div class="curso-header">{{ $curso->cur_nombre }}</div>
        
        <table>
            <thead>
                <tr>
                    <th rowspan="2" width="15%">ESTUDIANTE</th>
                    @foreach(['FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV'] as $mes)
                        <th colspan="2">{{ $mes }}</th>
                    @endforeach
                    <th rowspan="2">TOTAL</th>
                </tr>
                <tr>
                    @for($i = 0; $i < 10; $i++)
                        <th style="font-size: 6px;">Código</th>
                        <th style="font-size: 6px;">Bs</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach($pagosPorEstudiante as $estCodigo => $pagosEst)
                    @php
                        $estudiante = $pagosEst->first()->estudiante;
                        $totalEstudiante = 0;
                        $pagosPorMes = [];
                        
                        foreach($pagosEst as $pago) {
                            $mesesCubiertos = $pago->meses_cubiertos;
                            if (!empty($mesesCubiertos)) {
                                $montoPorMes = $pago->pagos_precio / count($mesesCubiertos);
                                foreach($mesesCubiertos as $mes) {
                                    if ($mes >= 2 && $mes <= 11) {
                                        if (!isset($pagosPorMes[$mes])) {
                                            $pagosPorMes[$mes] = ['codigo' => $pago->pagos_codigo, 'monto' => 0];
                                        }
                                        $pagosPorMes[$mes]['monto'] += $montoPorMes;
                                    }
                                }
                            }
                        }
                    @endphp
                    
                    <tr>
                        <td class="estudiante-cell">{{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</td>
                        @for($mes = 2; $mes <= 11; $mes++)
                            @if(isset($pagosPorMes[$mes]))
                                @php
                                    $totalEstudiante += $pagosPorMes[$mes]['monto'];
                                @endphp
                                <td style="font-size: 6px;">{{ $pagosPorMes[$mes]['codigo'] }}</td>
                                <td class="numero">{{ number_format($pagosPorMes[$mes]['monto'], 2) }}</td>
                            @else
                                <td>-</td>
                                <td>-</td>
                            @endif
                        @endfor
                        <td class="numero"><strong>{{ number_format($totalEstudiante, 2) }}</strong></td>
                    </tr>
                    @php $totalGeneral += $totalEstudiante; @endphp
                @endforeach
            </tbody>
        </table>
        <br>
    @endforeach

    <div style="margin-top: 15px; font-size: 12px; font-weight: bold; text-align: right;">
        TOTAL GENERAL: Bs. {{ number_format($totalGeneral, 2) }}
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
