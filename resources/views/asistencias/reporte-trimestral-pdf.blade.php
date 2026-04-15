<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia - Trimestre {{ $trimestre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; padding: 10px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .logo img { width: 60px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 7px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 10px; right: 10px; background-color: #dc3545; color: white; padding: 6px 12px; border-radius: 15px; font-weight: bold; font-size: 8px; text-align: center; }
        .title-section { text-align: center; margin: 10px 0; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .title-section h2 { font-size: 12px; font-weight: bold; margin: 2px 0; }
        .title-section p { font-size: 8px; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 2px; text-align: center; }
        th { background-color: #2c3e50; color: white; font-size: 6px; font-weight: bold; }
        .estudiante { text-align: left; font-size: 6px; }
        .total-col { background-color: #ffeb3b; font-weight: bold; }
        .leyenda { margin-top: 10px; font-size: 8px; }
        .periodo-info { font-size: 7px; margin-top: 2px; color: #555; }
        .footer { position: fixed; bottom: 10px; left: 10px; font-size: 7px; color: #666; }
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
        <h2>REPORTE DE ASISTENCIA - {{ $trimestre }}{{ $trimestre == 1 ? 'ER' : ($trimestre == 2 ? 'DO' : 'ER') }} TRIMESTRE</h2>
        <p><strong>CURSO:</strong> {{ $curso->cur_nombre }} | <strong>GESTIÓN {{ $year ?? date('Y') }}</strong></p>
        @if(isset($periodo) && $periodo)
            <p class="periodo-info">{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }} — {{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</p>
        @endif
    </div>

    @php
        $mesesNum = $rango['mesesNum'] ?? [];
        $mesesNombres = $rango['meses'] ?? [];
        $yearVal = $year ?? date('Y');
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">ESTUDIANTE</th>
                @foreach($mesesNombres as $mes)
                    <th colspan="5">{{ strtoupper($mes) }}</th>
                @endforeach
                <th colspan="5">TOTAL TRIMESTRE</th>
            </tr>
            <tr>
                @for($i = 0; $i < count($mesesNombres) + 1; $i++)
                    <th>D.T.</th>
                    <th>T.L.</th>
                    <th>T.F.</th>
                    <th>T.A.</th>
                    <th>TOTAL</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $index => $estudiante)
                @php
                    $totalTrimestre = ['dt' => 0, 'tl' => 0, 'tf' => 0, 'ta' => 0, 'total' => 0];
                @endphp
                <tr>
                    <td>{{ isset($lista) && isset($lista[$estudiante->est_codigo]) ? $lista[$estudiante->est_codigo] : $index + 1 }}</td>
                    <td class="estudiante">{{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</td>

                    @foreach($mesesNum as $mesNum)
                        @php
                            $diasMes = cal_days_in_month(CAL_GREGORIAN, $mesNum, $yearVal);

                            $asistencias = \App\Models\Asistencia::where('estud_codigo', $estudiante->est_codigo)
                                ->whereYear('asis_fecha', $yearVal)->whereMonth('asis_fecha', $mesNum)->count();

                            $permisos = \App\Models\Permiso::where('estud_codigo', $estudiante->est_codigo)
                                ->where('permiso_estado', 1)
                                ->whereYear('permiso_fecha_inicio', $yearVal)->whereMonth('permiso_fecha_inicio', $mesNum)->count();

                            $asistenciasMes = \App\Models\Asistencia::where('estud_codigo', $estudiante->est_codigo)
                                ->whereYear('asis_fecha', $yearVal)->whereMonth('asis_fecha', $mesNum)->get();

                            $permisosEstudiante = \App\Models\Permiso::where('estud_codigo', $estudiante->est_codigo)
                                ->where('permiso_estado', 1)
                                ->whereYear('permiso_fecha_inicio', $yearVal)->whereMonth('permiso_fecha_inicio', $mesNum)->get();

                            $configs = \App\Models\ConfiguracionAsistencia::activo()
                                ->where(function($q) use ($estudiante) {
                                    $q->whereHas('cursos', function($subQ) use ($estudiante) {
                                        $subQ->where('colegio_cursos.cur_codigo', $estudiante->cur_codigo);
                                    })->orWhereDoesntHave('cursos');
                                })->get();

                            $atrasos = 0;
                            foreach($asistenciasMes as $asis) {
                                $tienePermiso = $permisosEstudiante->where('permiso_fecha_inicio', '<=', $asis->asis_fecha->format('Y-m-d'))
                                    ->where('permiso_fecha_fin', '>=', $asis->asis_fecha->format('Y-m-d'))->first();
                                if ($tienePermiso) continue;
                                if ($configs->isEmpty()) continue;

                                $horaPartes = explode(':', substr($asis->asis_hora, 0, 5));
                                $minutosLlegada = ((int)$horaPartes[0] * 60) + (int)$horaPartes[1];

                                $config = null; $menorDif = PHP_INT_MAX;
                                foreach ($configs as $conf) {
                                    $horaEnt = strlen($conf->hora_entrada) > 8 ? substr($conf->hora_entrada, 11, 5) : substr($conf->hora_entrada, 0, 5);
                                    $horaSal = strlen($conf->hora_salida) > 8 ? substr($conf->hora_salida, 11, 5) : substr($conf->hora_salida, 0, 5);
                                    $minutosEnt = ((int)explode(':', $horaEnt)[0] * 60) + (int)explode(':', $horaEnt)[1];
                                    $minutosSal = ((int)explode(':', $horaSal)[0] * 60) + (int)explode(':', $horaSal)[1];
                                    if ($minutosLlegada >= ($minutosEnt - 120) && $minutosLlegada <= ($minutosSal + 120)) {
                                        $dif = abs($minutosLlegada - $minutosEnt);
                                        if ($dif < $menorDif) { $menorDif = $dif; $config = $conf; }
                                    }
                                }
                                if (!$config) $config = $configs->first();

                                $tol = strlen($config->tolerancia_atraso) > 8 ? substr($config->tolerancia_atraso, 11, 5) : substr($config->tolerancia_atraso, 0, 5);
                                $minutosLim = ((int)explode(':', $tol)[0] * 60) + (int)explode(':', $tol)[1];
                                if ($minutosLlegada > $minutosLim) $atrasos++;
                            }

                            $festivos = \App\Models\FechaFestiva::activo()->where('festivo_tipo', 1)
                                ->whereYear('festivo_fecha', $yearVal)->whereMonth('festivo_fecha', $mesNum)->count();

                            $faltas = $diasMes - $asistencias - $permisos - $festivos;
                            $totalTrimestre['dt'] += $asistencias; $totalTrimestre['tl'] += $permisos;
                            $totalTrimestre['tf'] += $faltas; $totalTrimestre['ta'] += $atrasos;
                            $totalTrimestre['total'] += $diasMes;
                        @endphp
                        <td>{{ $asistencias }}</td>
                        <td>{{ $permisos }}</td>
                        <td>{{ $faltas }}</td>
                        <td>{{ $atrasos }}</td>
                        <td>{{ $diasMes }}</td>
                    @endforeach

                    <td class="total-col">{{ $totalTrimestre['dt'] }}</td>
                    <td class="total-col">{{ $totalTrimestre['tl'] }}</td>
                    <td class="total-col">{{ $totalTrimestre['tf'] }}</td>
                    <td class="total-col">{{ $totalTrimestre['ta'] }}</td>
                    <td class="total-col">{{ $totalTrimestre['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="leyenda">
        <strong>Leyenda:</strong> D.T. = Días Trabajados | T.L. = Total Licencias | T.F. = Total Faltas | T.A. = Total Atrasos
    </div>

    <div class="footer">
        Fecha y hora de impresión: {{ now()->format('d/m/Y H:i:s') }}<br>Página 1
    </div>
</body>
</html>
