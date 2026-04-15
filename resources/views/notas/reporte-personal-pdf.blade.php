<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Boletín - {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8px; padding: 8mm 10mm; }
        .header { display: table; width: 100%; margin-bottom: 5px; }
        .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .logo img { width: 50px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 10px; margin: 0; line-height: 1.2; }
        .header-info p { font-size: 6.5px; margin: 1px 0; }
        .fecha-box { position: absolute; top: 8mm; right: 10mm; background: #c0392b; color: #fff; padding: 4px 10px; border-radius: 10px; font-weight: bold; font-size: 7px; text-align: center; }

        /* Info estudiante */
        .info-table { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 6px; }
        .info-table td { padding: 3px 8px; font-size: 9px; border: none; }
        .info-label { font-weight: bold; font-size: 8px; width: 18%; }
        .info-value { font-weight: bold; font-size: 11px; }
        .nro-lista-cell { text-align: center; width: 15%; vertical-align: middle; border-left: 2px solid #000; }
        .nro-lista-label { font-size: 7px; font-weight: bold; }
        .nro-lista-num { font-size: 32px; font-weight: bold; line-height: 1; }

        /* Título */
        .title { text-align: center; font-size: 11px; font-weight: bold; margin: 6px 0 4px; border-bottom: 2px solid #000; padding-bottom: 3px; }

        /* Tabla notas */
        table.notas { width: 100%; border-collapse: collapse; }
        table.notas th, table.notas td { border: 1px solid #000; padding: 2px 3px; text-align: center; font-size: 7.5px; }
        table.notas th { background: #ddd; font-weight: bold; font-size: 7px; }
        .th-top { background: #bbb; font-size: 8px; }
        .campo-cell { background: #f0f0f0; font-weight: bold; text-align: left !important; font-size: 7px; vertical-align: middle; padding-left: 4px !important; }
        .materia-cell { text-align: left !important; padding-left: 8px !important; font-size: 7.5px; }
        .prom-campo-row td { background: #e8e8e8; font-weight: bold; font-size: 7px; }
        .prom-campo-label { text-align: left !important; padding-left: 8px !important; font-style: italic; }
        .prom-anual { background: #d5d5d5; font-weight: bold; }
        .nota-baja { color: #c0392b; font-weight: bold; }

        /* Secciones inferiores */
        table.extra { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.extra th, table.extra td { border: 1px solid #000; padding: 2px 3px; text-align: center; font-size: 7px; }
        table.extra th { background: #ddd; font-size: 6.5px; }
        .section-label { background: #bbb; font-weight: bold; text-align: left !important; padding-left: 5px !important; vertical-align: middle; font-size: 7.5px; }
        .rotated { font-size: 5.5px; }

        .footer { position: fixed; bottom: 8mm; left: 10mm; right: 10mm; font-size: 6px; color: #888; border-top: 0.5px solid #ccc; padding-top: 2px; }
    </style>
</head>
<body>
    <div class="fecha-box">Fecha<br>{{ now()->format('d/m/Y') }}</div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa<br>INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339 | Teléfonos: 2840320</p>
        </div>
    </div>

    {{-- Info estudiante --}}
    <table class="info-table">
        <tr>
            <td class="info-label">APELLIDOS Y NOMBRES:</td>
            <td class="info-value">{{ mb_strtoupper($estudiante->est_apellidos . ' ' . $estudiante->est_nombres, 'UTF-8') }}</td>
            <td rowspan="2" class="nro-lista-cell">
                <div class="nro-lista-label">Nro. de Lista</div>
                <div class="nro-lista-num">{{ $nroLista }}</div>
            </td>
        </tr>
        <tr>
            <td class="info-label">AÑO DE ESCOLARIDAD:</td>
            <td style="font-weight:bold;font-size:10px;">{{ $curso->cur_nombre }}</td>
        </tr>
    </table>

    {{-- Título --}}
    <div class="title">ÁREAS CURRICULARES INDIVIDUALIZADAS</div>

    {{-- Tabla de notas --}}
    <table class="notas">
        <thead>
            <tr>
                <th rowspan="2" class="th-top" style="width:16%;">CAMPO</th>
                <th rowspan="2" class="th-top" style="width:20%;">ÁREAS CURRICULARES</th>
                <th colspan="{{ $periodos->count() + 1 }}" class="th-top">VALORACIÓN CUANTITATIVA</th>
            </tr>
            <tr>
                @foreach($periodos as $p)
                    <th>{{ $p->periodo_numero == 1 ? '1er.' : ($p->periodo_numero == 2 ? '2do.' : '3er') }}<br>TRIMESTRE</th>
                @endforeach
                <th class="prom-anual">PROMEDIO</th>
            </tr>
        </thead>
        <tbody>
            @php
                $gruposYaMostrados = [];
            @endphp
            @foreach($materiasPorCampo as $campo => $cmds)
                @php
                    // Contar filas: materias + filas de grupo + fila promedio campo
                    $gruposEnCampo = [];
                    foreach($cmds as $cmd) {
                        if(isset($gruposMap[$cmd->mat_codigo])) {
                            $g = $gruposMap[$cmd->mat_codigo];
                            $gruposEnCampo[$g->grupo_id] = $g;
                        }
                    }
                    $filasExtra = count($gruposEnCampo);
                    $materiaCount = $cmds->count();
                    $first = true;
                    $gruposMostradosEnCampo = [];
                @endphp
                @foreach($cmds as $cmd)
                    <tr>
                        @if($first)
                            <td rowspan="{{ $materiaCount + $filasExtra + 1 }}" class="campo-cell">{{ mb_strtoupper($campo, 'UTF-8') }}</td>
                            @php $first = false; @endphp
                        @endif
                        <td class="materia-cell">{{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}</td>
                        @foreach($periodos as $p)
                            @php $val = $notasData[$cmd->mat_codigo]['trimestres'][$p->periodo_numero] ?? 0; @endphp
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}">{{ $val > 0 ? $val : '' }}</td>
                        @endforeach
                        <td class="prom-anual">{{ $notasData[$cmd->mat_codigo]['promedio'] > 0 ? $notasData[$cmd->mat_codigo]['promedio'] : '' }}</td>
                    </tr>
                    {{-- Si esta materia es la última de su grupo, mostrar fila promedio grupo --}}
                    @if(isset($gruposMap[$cmd->mat_codigo]))
                        @php
                            $grp = $gruposMap[$cmd->mat_codigo];
                            $matCodsGrupo = $grp->materias->pluck('mat_codigo')->toArray();
                            $esUltima = true;
                            $found = false;
                            foreach($cmds as $c2) {
                                if($found && in_array($c2->mat_codigo, $matCodsGrupo)) { $esUltima = false; break; }
                                if($c2->mat_codigo === $cmd->mat_codigo) $found = true;
                            }
                        @endphp
                        @if($esUltima && !in_array($grp->grupo_id, $gruposMostradosEnCampo))
                            @php $gruposMostradosEnCampo[] = $grp->grupo_id; @endphp
                            <tr style="background:#e8d5f5;">
                                <td style="text-align:left !important;padding-left:8px !important;font-weight:bold;font-size:6.5px;color:#6c3483;">
                                    ↳ {{ mb_strtoupper($grp->grupo_nombre, 'UTF-8') }}
                                </td>
                                @foreach($periodos as $p)
                                    @php
                                        $sumaG = 0; $cntG = 0;
                                        foreach($matCodsGrupo as $mc) {
                                            $v = $notasData[$mc]['trimestres'][$p->periodo_numero] ?? 0;
                                            $sumaG += $v; $cntG++;
                                        }
                                        $promG = $cntG > 0 ? round($sumaG / $cntG, 0) : 0;
                                    @endphp
                                    <td style="font-weight:bold;color:#6c3483;">{{ $promG > 0 ? $promG : '' }}</td>
                                @endforeach
                                @php
                                    $sumaAnualG = 0; $cntAnualG = 0;
                                    foreach($periodos as $p2) {
                                        $sg = 0; $cg = 0;
                                        foreach($matCodsGrupo as $mc) {
                                            $v = $notasData[$mc]['trimestres'][$p2->periodo_numero] ?? 0;
                                            $sg += $v; $cg++;
                                        }
                                        $pg = $cg > 0 ? round($sg / $cg, 0) : 0;
                                        if($pg > 0) { $sumaAnualG += $pg; $cntAnualG++; }
                                    }
                                    $promAnualG = $cntAnualG > 0 ? round($sumaAnualG / $cntAnualG, 0) : 0;
                                @endphp
                                <td class="prom-anual" style="color:#6c3483;font-size:9px;">{{ $promAnualG > 0 ? $promAnualG : '' }}</td>
                            </tr>
                        @endif
                    @endif
                @endforeach
                {{-- Fila promedio del campo --}}
                <tr class="prom-campo-row">
                    <td class="prom-campo-label">
                        @php
                            $palabras = explode(' ', $campo);
                            $abrev = count($palabras) > 2 ? implode(' ', array_slice($palabras, 0, 2)) : $campo;
                        @endphp
                        PROMEDIO<br>{{ mb_strtoupper($abrev, 'UTF-8') }}
                    </td>
                    @foreach($periodos as $p)
                        @php $v = $promediosCampo[$campo][$p->periodo_numero] ?? 0; @endphp
                        <td style="font-weight:bold;">{{ $v > 0 ? $v : '' }}</td>
                    @endforeach
                    <td class="prom-anual" style="font-size:9px;">{{ ($promediosCampo[$campo]['anual'] ?? 0) > 0 ? $promediosCampo[$campo]['anual'] : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ASISTENCIA --}}
    <table class="extra">
        <tr>
            <td rowspan="2" class="section-label" style="width:14%;">ASISTENCIA</td>
            @foreach($periodos as $p)
                <th colspan="5" style="background:#bbb;">{{ $p->periodo_numero == 1 ? 'PRIMER' : ($p->periodo_numero == 2 ? 'SEGUNDO' : 'TERCER') }} TRIMESTRE</th>
            @endforeach
        </tr>
        <tr>
            @foreach($periodos as $p)
                <th class="rotated">ATRASOS</th>
                <th class="rotated">LICENCIAS</th>
                <th class="rotated">FALTAS</th>
                <th class="rotated">DÍAS<br>TRABAJADOS</th>
                <th class="rotated">TOTAL</th>
            @endforeach
        </tr>
        <tr>
            <td></td>
            @foreach($periodos as $p)
                @php $a = $asistData[$p->periodo_numero] ?? ['ta'=>0,'tl'=>0,'tf'=>0,'dt'=>0,'total'=>0]; @endphp
                <td>{{ $a['ta'] }}</td>
                <td>{{ $a['tl'] }}</td>
                <td>{{ $a['tf'] }}</td>
                <td>{{ $a['dt'] }}</td>
                <td style="font-weight:bold;">{{ $a['total'] }}</td>
            @endforeach
        </tr>
    </table>

    {{-- ENFERMERÍA --}}
    <table class="extra">
        <tr>
            <td rowspan="2" class="section-label" style="width:14%;">ENFERMERÍA</td>
            @foreach($periodos as $p)
                <th>HIGIENE</th>
                <th>ATENCIÓN</th>
            @endforeach
        </tr>
        <tr>
            @foreach($periodos as $p)
                @php $e = $enfData[$p->periodo_numero] ?? ['higiene'=>0,'atencion'=>0]; @endphp
                <td>{{ $e['higiene'] }}</td>
                <td>{{ $e['atencion'] }}</td>
            @endforeach
        </tr>
    </table>

    {{-- CONTROL Y SEGUIMIENTO --}}
    <table class="extra">
        <tr>
            <td rowspan="3" class="section-label" style="width:14%;">CONTROL Y<br>SEGUIMIENTO</td>
            @foreach($periodos as $p)
                <th colspan="2">LLAMADAS</th>
                <th colspan="2">COMPROMISOS</th>
            @endforeach
        </tr>
        <tr>
            @foreach($periodos as $p)
                <th class="rotated">SI</th>
                <th class="rotated">NO</th>
                <th class="rotated">SI</th>
                <th class="rotated">NO</th>
            @endforeach
        </tr>
        <tr>
            @foreach($periodos as $p)
                @php $ps = $psicoData[$p->periodo_numero] ?? ['llamadas_si'=>0,'llamadas_no'=>0,'compromisos_si'=>0,'compromisos_no'=>0]; @endphp
                <td>{{ $ps['llamadas_si'] }}</td>
                <td>{{ $ps['llamadas_no'] }}</td>
                <td>{{ $ps['compromisos_si'] }}</td>
                <td>{{ $ps['compromisos_no'] }}</td>
            @endforeach
        </tr>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | Gestión {{ $gestion }} | Boletín Individual
    </div>
</body>
</html>
