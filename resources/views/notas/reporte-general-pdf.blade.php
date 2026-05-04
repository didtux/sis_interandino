<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Registro General - {{ $curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 5px; padding: 3mm; color: #333; }

        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 34px; vertical-align: middle; }
        .logo img { width: 30px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 7.5px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 4.5px; margin: 0; color: #666; }
        .fecha-box { position: absolute; top: 3mm; right: 3mm; font-size: 5px; color: #999; }

        .title-bar { background: #2c3e50; color: #fff; text-align: center; padding: 3px 0; margin-bottom: 3px; }
        .title-bar h2 { font-size: 7px; font-weight: bold; letter-spacing: 0.5px; }
        .title-bar p { font-size: 5px; opacity: 0.85; }

        table.main { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.main th, table.main td {
            border: 0.5px solid #ccc;
            padding: 1px 1px;
            text-align: center;
            font-size: 5px;
            overflow: hidden;
        }

        .th-dark { background: #2c3e50; color: #fff; font-size: 4.5px; }
        .th-mat { background: #f8f9fa; color: #333; font-weight: bold; font-size: 4.5px; border-bottom: 1.5px solid #f39c12; }
        .th-trim { background: #fafafa; font-size: 4px; color: #666; }
        .th-prom { background: #fafafa; font-size: 4px; color: #333; font-weight: bold; }
        .th-grupo { background: #f5f0ff; color: #6c3483; font-weight: bold; font-size: 4px; border-bottom: 1.5px solid #9b59b6; }
        .th-grupo-sub { background: #faf5ff; font-size: 4px; color: #6c3483; font-weight: bold; }

        .th-asist-group { background: #eaf4fc; color: #1a5276; font-weight: bold; font-size: 4.5px; border-bottom: 1.5px solid #3498db; }
        .th-vert { vertical-align: bottom; text-align: center; padding: 2px 0 !important; height: 50px; background: #fafafa; }

        .est-name { text-align: left !important; padding-left: 2px !important; font-size: 5px; white-space: nowrap; overflow: hidden; }
        .nota-baja { color: #e74c3c; }
        .prom-col { background: #fffdf0; font-weight: bold; }
        .grupo-val { color: #6c3483; font-weight: bold; }
        .suma-col { font-weight: bold; background: #f0faf0; }
        .prom-final { font-weight: bold; background: #e8f5e9; }

        .asist-dt { color: #27ae60; }
        .asist-ta { color: #f39c12; }
        .asist-tl { color: #2980b9; }
        .asist-tf { color: #e74c3c; font-weight: bold; }
        .asist-total { font-weight: bold; background: #f0f7ff; }

        tbody tr:nth-child(even) td { background: #fcfcfc; }

        .footer { position: fixed; bottom: 3mm; left: 3mm; right: 3mm; font-size: 4px; color: #bbb; border-top: 0.5px solid #eee; padding-top: 1px; }
    </style>
</head>
<body>
    <div class="fecha-box">{{ now()->format('d/m/Y') }}</div>

    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="header-info">
            <h3>Unidad Educativa INTERANDINO BOLIVIANO</h3>
            <p>Dir. Calle Victor Gutierrez Nro 3339 | Tel: 2840320</p>
        </div>
    </div>

    <div class="title-bar">
        <h2>REGISTRO GENERAL DE NOTAS — {{ mb_strtoupper($periodoNombre, 'UTF-8') }}</h2>
        <p>{{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }} | GESTIÓN {{ $gestion }}</p>
    </div>

    @php
        $materiasList = $asignaciones->values();
        $numPeriodos = $periodos->count();
        $showTrimCols = $numPeriodos > 1;

        $ultimaMatGrupo = [];
        if(isset($gruposMap)) {
            foreach($materiasList as $cmd) {
                $grp = $gruposMap[$cmd->mat_codigo] ?? null;
                if($grp) { $ultimaMatGrupo[$grp->grupo_id] = $cmd->mat_codigo; }
            }
        }

        $colsMat = 0;
        foreach($materiasList as $cmd) {
            $colsMat += $showTrimCols ? ($numPeriodos + 1) : 1;
            $grp = $gruposMap[$cmd->mat_codigo] ?? null;
            if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo) {
                $colsMat += $showTrimCols ? ($numPeriodos + 1) : 1;
            }
        }
        $colsSuma = $showTrimCols ? 2 : 0;
        $colsAsist = $numPeriodos * 5;
        $totalData = $colsMat + $colsSuma + $colsAsist;
        $pct = $totalData > 0 ? round(88 / $totalData, 2) : 2;
    @endphp

    <table class="main">
        <colgroup>
            <col style="width:1.5%;">
            <col style="width:10.5%;">
            @for($x = 0; $x < $totalData; $x++)
                <col style="width:{{ $pct }}%;">
            @endfor
        </colgroup>
        <thead>
        @if($showTrimCols)
            {{-- ═══ MODO ANUAL (múltiples trimestres) ═══ --}}
            {{-- Fila 1: Agrupadores materias + asistencia --}}
            <tr>
                <th rowspan="3" class="th-dark">N°</th>
                <th rowspan="3" class="th-dark" style="text-align:left !important;padding-left:2px !important;">NÓMINA</th>
                @foreach($materiasList as $cmd)
                    <th colspan="{{ $numPeriodos + 1 }}" class="th-mat">{{ mb_strtoupper(mb_substr($cmd->materia->mat_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        <th colspan="{{ $numPeriodos + 1 }}" class="th-grupo">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}</th>
                    @endif
                @endforeach
                <th rowspan="3" class="th-dark" style="font-size:3.5px;">∑</th>
                <th rowspan="3" class="th-dark" style="font-size:3.5px;">x̄</th>
                @foreach($periodos as $p)
                    <th colspan="5" class="th-asist-group">{{ $p->periodo_numero }}° TRIM.</th>
                @endforeach
            </tr>
            {{-- Fila 2: Sub-trimestres + sub-asistencia vertical --}}
            <tr>
                @foreach($materiasList as $cmd)
                    @foreach($periodos as $p)
                        <th class="th-trim">{{ $p->periodo_numero }}°</th>
                    @endforeach
                    <th class="th-prom">x̄</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        @foreach($periodos as $p)
                            <th class="th-grupo-sub">{{ $p->periodo_numero }}°</th>
                        @endforeach
                        <th class="th-grupo-sub">x̄</th>
                    @endif
                @endforeach
                @foreach($periodos as $p)
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#27ae60">Presentes</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#f39c12">Atrasos</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#2980b9">Licencias</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#e74c3c">Faltas</text></svg></th>
                    <th class="th-vert" style="background:#eaf4fc;"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#1a5276">Total días</text></svg></th>
                @endforeach
            </tr>
        @else
            {{-- ═══ MODO TRIMESTRE ÚNICO ═══ --}}
            {{-- Fila 1: Materias (rowspan=2) + asistencia agrupada --}}
            <tr>
                <th rowspan="2" class="th-dark">N°</th>
                <th rowspan="2" class="th-dark" style="text-align:left !important;padding-left:2px !important;">NÓMINA</th>
                @foreach($materiasList as $cmd)
                    <th rowspan="2" class="th-mat">{{ mb_strtoupper(mb_substr($cmd->materia->mat_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        <th rowspan="2" class="th-grupo">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}</th>
                    @endif
                @endforeach
                @foreach($periodos as $p)
                    <th colspan="5" class="th-asist-group">{{ $p->periodo_numero }}° TRIM.</th>
                @endforeach
            </tr>
            {{-- Fila 2: Solo sub-headers asistencia vertical --}}
            <tr>
                @foreach($periodos as $p)
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#27ae60">Presentes</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#f39c12">Atrasos</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#2980b9">Licencias</text></svg></th>
                    <th class="th-vert"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#e74c3c">Faltas</text></svg></th>
                    <th class="th-vert" style="background:#eaf4fc;"><svg width="9" height="45" xmlns="http://www.w3.org/2000/svg"><text x="6" y="43" transform="rotate(-90,6,43)" font-family="Arial" font-size="4.5" font-weight="bold" fill="#1a5276">Total días</text></svg></th>
                @endforeach
            </tr>
        @endif
        </thead>
        <tbody>
            @foreach($data as $i => $fila)
                @php $est = $fila['estudiante']; @endphp
                <tr>
                    <td style="font-weight:bold;color:#999;">{{ $lista[$est->est_codigo] ?? ($i + 1) }}</td>
                    <td class="est-name">{{ mb_strtoupper($est->est_apellidos . ' ' . $est->est_nombres, 'UTF-8') }}</td>
                    @foreach($materiasList as $cmd)
                        @php $matData = $fila['materias'][$cmd->mat_codigo] ?? ['trimestres' => [], 'promedio' => 0]; @endphp
                        @if($showTrimCols)
                            @foreach($periodos as $p)
                                @php $val = $matData['trimestres'][$p->periodo_numero] ?? 0; @endphp
                                <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}">{{ $val > 0 ? $val : '' }}</td>
                            @endforeach
                            <td class="prom-col">{{ $matData['promedio'] > 0 ? $matData['promedio'] : '' }}</td>
                        @else
                            @php $val = collect($matData['trimestres'])->first() ?? 0; @endphp
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}">{{ $val > 0 ? $val : '' }}</td>
                        @endif
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            @php $matCodsG = $grp->materias->pluck('mat_codigo')->toArray(); @endphp
                            @if($showTrimCols)
                                @foreach($periodos as $p)
                                    @php $sg=0;$cg=0; foreach($matCodsG as $mc){$v=$fila['materias'][$mc]['trimestres'][$p->periodo_numero]??0;$sg+=$v;$cg++;} $promGT=$cg>0?round($sg/$cg,0):0; @endphp
                                    <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                                @endforeach
                                @php $spg=0;$cpg=0; foreach($matCodsG as $mc){$pm=$fila['materias'][$mc]['promedio']??0;$spg+=$pm;$cpg++;} $promGA=$cpg>0?round($spg/$cpg,0):0; @endphp
                                <td class="grupo-val">{{ $promGA > 0 ? $promGA : '' }}</td>
                            @else
                                @php $sg=0;$cg=0; foreach($matCodsG as $mc){$v=collect($fila['materias'][$mc]['trimestres']??[])->first()??0;$sg+=$v;$cg++;} $promGT=$cg>0?round($sg/$cg,0):0; @endphp
                                <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                            @endif
                        @endif
                    @endforeach
                    @if($showTrimCols)
                        <td class="suma-col">{{ $fila['suma'] > 0 ? $fila['suma'] : '' }}</td>
                        <td class="prom-final">{{ $fila['promedio'] > 0 ? $fila['promedio'] : '' }}</td>
                    @endif
                    @foreach($periodos as $p)
                        @php $a = $fila['asistencia'][$p->periodo_numero] ?? ['dt'=>0,'ta'=>0,'tl'=>0,'tf'=>0,'total'=>0]; @endphp
                        <td class="asist-dt">{{ $a['dt'] ?: '' }}</td>
                        <td class="asist-ta">{{ $a['ta'] ?: '' }}</td>
                        <td class="asist-tl">{{ $a['tl'] ?: '' }}</td>
                        <td class="asist-tf">{{ $a['tf'] ?: '' }}</td>
                        <td class="asist-total">{{ $a['total'] ?: '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $curso->cur_nombre }} | {{ $periodoNombre }} | Gestión {{ $gestion }} | Impreso: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
