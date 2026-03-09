<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estudiantes en Mora - {{ $mesesNombres[$mesActual] }} {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; padding: 15px; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .logo { display: table-cell; width: 80px; vertical-align: middle; }
        .logo img { width: 70px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header-info h3 { font-size: 11px; margin: 0; line-height: 1.3; }
        .header-info p { font-size: 8px; margin: 2px 0; }
        .fecha-box { position: absolute; top: 15px; right: 15px; background-color: #f39c12; color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 10px; }
        .title-section { text-align: center; margin: 15px 0; }
        .title-section h2 { font-size: 14px; font-weight: bold; margin: 3px 0; }
        .alert-box { background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; font-size: 8px; }
        th { background-color: #f39c12; color: white; font-weight: bold; text-align: center; }
        .numero { text-align: right; }
        .centro { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7px; margin: 1px; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .footer { position: fixed; bottom: 10px; left: 15px; font-size: 8px; color: #666; }
        .resumen { margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; }
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
        <h2>REPORTE DE ESTUDIANTES EN MORA</h2>
        <p>Mes: {{ $mesesNombres[$mesActual] }} {{ $year }}</p>
    </div>

    <div class="alert-box">
        <strong>Total de estudiantes en mora: {{ $estudiantesEnMora->count() }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">N°</th>
                <th width="25%">ESTUDIANTE</th>
                <th width="15%">CURSO</th>
                <th width="12%">MONTO MENSUAL</th>
                <th width="20%">MESES PAGADOS</th>
                <th width="23%">MESES PENDIENTES</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDeuda = 0;
            @endphp
            @foreach($estudiantesEnMora as $index => $estudiante)
                @php
                    $mesesPagados = [];
                    foreach($estudiante->pagos as $pago) {
                        $mesesPagados = array_merge($mesesPagados, $pago->meses_cubiertos);
                    }
                    $mesesPagados = array_unique($mesesPagados);
                    sort($mesesPagados);
                    
                    $mesesPendientes = [];
                    for($m = 2; $m <= $mesActual; $m++) {
                        if(!in_array($m, $mesesPagados)) {
                            $mesesPendientes[] = $m;
                        }
                    }
                    
                    // Calcular monto mensualidad (con o sin inscripción)
                    if ($estudiante->inscripcion) {
                        $montoMensualidad = $estudiante->inscripcion->insc_monto_final / 10;
                    } else {
                        $montoMensualidad = $estudiante->pagos->count() > 0 
                            ? $estudiante->pagos->sum('pagos_precio') / $estudiante->pagos->count() 
                            : 475;
                    }
                    
                    $deudaTotal = $montoMensualidad * count($mesesPendientes);
                    $totalDeuda += $deudaTotal;
                @endphp
                <tr>
                    <td class="centro">{{ $index + 1 }}</td>
                    <td>{{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</td>
                    <td class="centro">{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                    <td class="numero">Bs. {{ number_format($montoMensualidad, 2) }}</td>
                    <td class="centro">
                        @if(count($mesesPagados) > 0)
                            @foreach($mesesPagados as $mes)
                                <span class="badge badge-success">{{ substr($mesesNombres[$mes], 0, 3) }}</span>
                            @endforeach
                        @else
                            Ninguno
                        @endif
                    </td>
                    <td class="centro">
                        @foreach($mesesPendientes as $mes)
                            <span class="badge badge-danger">{{ substr($mesesNombres[$mes], 0, 3) }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="resumen">
        <strong>RESUMEN:</strong><br>
        Total estudiantes en mora: {{ $estudiantesEnMora->count() }}<br>
        Deuda total estimada: Bs. {{ number_format($totalDeuda, 2) }}
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>
        Página 1
    </div>
</body>
</html>
