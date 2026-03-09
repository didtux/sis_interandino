<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen Anual {{ $year }}</title>
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
        .referencias { font-size: 7px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; font-size: 7px; }
        th { background-color: #d0d0d0; font-weight: bold; }
        .curso-cell { text-align: left; font-weight: bold; background-color: #f0f0f0; }
        .mes-header { background-color: #b0b0b0; }
        .sub-header { font-size: 6px; }
        .total-row { background-color: #e0e0e0; font-weight: bold; }
        .numero { text-align: right; }
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
        <h2>INGRESO POR PENSIONES GESTIÓN {{ $year }}</h2>
        <p>De Febrero a Noviembre</p>
    </div>

    <div class="referencias">
        <strong>REFERENCIAS:</strong> Est = Estudiantes con pagos
    </div>

    @php
        $pagosPorCurso = $pagos->groupBy(function($pago) {
            return $pago->estudiante->cur_codigo ?? 'SIN_CURSO';
        });
        $totalGeneral = 0;
        $totalesPorMes = array_fill(2, 10, ['estudiantes' => [], 'ingreso' => 0]);
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">CURSO</th>
                @foreach(['FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV'] as $mes)
                    <th colspan="2" class="mes-header">{{ $mes }}</th>
                @endforeach
                <th rowspan="2">TOTAL</th>
            </tr>
            <tr>
                @for($i = 0; $i < 10; $i++)
                    <th class="sub-header">Est</th>
                    <th class="sub-header">Ingreso</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($cursos as $curso)
                @php
                    $pagosCurso = $pagosPorCurso->get($curso->cur_codigo, collect());
                    if ($pagosCurso->isEmpty()) continue;
                    
                    $totalCurso = 0;
                    $datosPorMes = [];
                    
                    for ($mes = 2; $mes <= 11; $mes++) {
                        $pagosMes = $pagosCurso->filter(function($pago) use ($mes) {
                            return in_array($mes, $pago->meses_cubiertos);
                        });
                        
                        $estudiantesUnicos = $pagosMes->pluck('est_codigo')->unique();
                        $ingreso = 0;
                        
                        foreach($pagosMes as $pago) {
                            $cantidadMeses = count($pago->meses_cubiertos);
                            $ingreso += $pago->pagos_precio / $cantidadMeses;
                        }
                        
                        $datosPorMes[$mes] = [
                            'estudiantes' => $estudiantesUnicos->count(),
                            'ingreso' => $ingreso
                        ];
                        
                        $totalCurso += $ingreso;
                        $totalesPorMes[$mes]['estudiantes'] = array_merge($totalesPorMes[$mes]['estudiantes'], $estudiantesUnicos->toArray());
                        $totalesPorMes[$mes]['ingreso'] += $ingreso;
                    }
                    
                    $totalGeneral += $totalCurso;
                @endphp
                
                <tr>
                    <td class="curso-cell">{{ $curso->cur_nombre }}</td>
                    @for($mes = 2; $mes <= 11; $mes++)
                        <td>{{ $datosPorMes[$mes]['estudiantes'] > 0 ? $datosPorMes[$mes]['estudiantes'] : '' }}</td>
                        <td class="numero">{{ $datosPorMes[$mes]['ingreso'] > 0 ? number_format($datosPorMes[$mes]['ingreso'], 2) : '' }}</td>
                    @endfor
                    <td class="numero"><strong>{{ number_format($totalCurso, 2) }}</strong></td>
                </tr>
            @endforeach
            
            <tr class="total-row">
                <td>TOTAL</td>
                @for($mes = 2; $mes <= 11; $mes++)
                    @php
                        $estudiantesUnicosMes = count(array_unique($totalesPorMes[$mes]['estudiantes']));
                    @endphp
                    <td>{{ $estudiantesUnicosMes > 0 ? $estudiantesUnicosMes : '' }}</td>
                    <td class="numero">{{ $totalesPorMes[$mes]['ingreso'] > 0 ? number_format($totalesPorMes[$mes]['ingreso'], 2) : '' }}</td>
                @endfor
                <td class="numero"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 10px;">
        <p><strong>RESUMEN DE INGRESOS DE LA GESTIÓN {{ $year }}</strong></p>
    </div>

    @php
        $totalesPorMesConFactura = array_fill(2, 10, 0);
        $totalesPorMesSinFactura = array_fill(2, 10, 0);
        $totalConFactura = 0;
        $totalSinFactura = 0;
        
        foreach($pagos as $pago) {
            $mesesCubiertos = $pago->meses_cubiertos;
            if (!empty($mesesCubiertos)) {
                $montoPorMes = $pago->pagos_precio / count($mesesCubiertos);
                foreach($mesesCubiertos as $mes) {
                    if ($mes >= 2 && $mes <= 11) {
                        if ($pago->pagos_sin_factura == 1) {
                            $totalesPorMesSinFactura[$mes] += $montoPorMes;
                            $totalSinFactura += $montoPorMes;
                        } else {
                            $totalesPorMesConFactura[$mes] += $montoPorMes;
                            $totalConFactura += $montoPorMes;
                        }
                    }
                }
            }
        }
    @endphp

    <table style="margin-top: 10px; width: 80%;">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                @foreach(['FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV'] as $mes)
                    <th>{{ $mes }}</th>
                @endforeach
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: left;">PENSIONES CON FACTURA</td>
                @for($mes = 2; $mes <= 11; $mes++)
                    <td class="numero">{{ $totalesPorMesConFactura[$mes] > 0 ? number_format($totalesPorMesConFactura[$mes], 2) : '' }}</td>
                @endfor
                <td class="numero"><strong>{{ number_format($totalConFactura, 2) }}</strong></td>
            </tr>
            <tr>
                <td style="text-align: left;">PENSIONES SIN FACTURA</td>
                @for($mes = 2; $mes <= 11; $mes++)
                    <td class="numero">{{ $totalesPorMesSinFactura[$mes] > 0 ? number_format($totalesPorMesSinFactura[$mes], 2) : '' }}</td>
                @endfor
                <td class="numero"><strong>{{ number_format($totalSinFactura, 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td style="text-align: left;">TOTAL RECAUDADO</td>
                @for($mes = 2; $mes <= 11; $mes++)
                    <td class="numero">{{ $totalesPorMes[$mes]['ingreso'] > 0 ? number_format($totalesPorMes[$mes]['ingreso'], 2) : '' }}</td>
                @endfor
                <td class="numero"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 10px; font-size: 10px; font-weight: bold;">
        <p>TOTAL GESTIÓN {{ $year }}: Bs. {{ number_format($totalGeneral, 2) }}</p>
        <p>Con Factura: Bs. {{ number_format($totalConFactura, 2) }} | Sin Factura: Bs. {{ number_format($totalSinFactura, 2) }}</p>
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
