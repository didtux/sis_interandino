@php
    // ── Escala de letra según la cantidad real de columnas ──────────────────
    // Mismo criterio que el centralizador: los NÚMEROS mandan, y el tramo se
    // elige para no desbordar la hoja. El conteo fino ($totalData) se rehace
    // más abajo para el reparto de anchos; acá sólo se necesita la magnitud.
    $nPerG   = $periodos->count();
    $anualG  = $nPerG > 1;

    $gruposConPromG = [];
    if (isset($gruposMap)) {
        foreach ($asignaciones as $cmdX) {
            $g = $gruposMap[$cmdX->mat_codigo] ?? null;
            if (!$g) continue;
            if ($g->materiasPromediables->count() >= 2) $gruposConPromG[$g->grupo_id] = true;
        }
    }

    $colsTotalG = 2
        + $asignaciones->count() * ($anualG ? $nPerG + 1 : 1)
        + count($gruposConPromG) * ($anualG ? $nPerG + 1 : 1)
        + ($anualG ? 2 : 0)
        + $nPerG * 5;

    // Base original: números 6px.
    // TOPE DELIBERADO: 7.5px (+25%). Medido sobre 2doSEC (15 materias,
    // 30 estudiantes) el registro trimestral entra en UNA hoja hasta 7.5px y
    // salta a dos a partir de 8px. Se prioriza no duplicar el papel de cada
    // curso; si se prefieren números más grandes aceptando la segunda hoja,
    // basta subir el primer tramo. El último tramo (anual, ~99 columnas) topa
    // en 6.8px por la misma razón: a 7px el registro anual pasa de 5 a 6 hojas.
    if     ($colsTotalG <= 40) { $fsNum = 7.5; $fsHead = 7;   $fsName = 8;   $fsVert = 5.5; $hVert = 52; }
    elseif ($colsTotalG <= 60) { $fsNum = 7.3; $fsHead = 6.5; $fsName = 7.5; $fsVert = 5.2; $hVert = 51; }
    elseif ($colsTotalG <= 80) { $fsNum = 7.1; $fsHead = 6.2; $fsName = 7;   $fsVert = 5;   $hVert = 50; }
    else                       { $fsNum = 6.8; $fsHead = 6;   $fsName = 6.8; $fsVert = 4.5; $hVert = 50; }
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Registro General - {{ $curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: {{ $fsNum }}px; padding: 3mm; color: #333; }

        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 34px; vertical-align: middle; }
        .logo img { width: 30px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 10.5px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 6.5px; margin: 0; color: #666; }
        .fecha-box { position: absolute; top: 3mm; right: 3mm; font-size: 6.5px; color: #999; }

        .title-bar { background: #2c3e50; color: #fff; text-align: center; padding: 3px 0; margin-bottom: 3px; }
        .title-bar h2 { font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
        .title-bar p { font-size: 8px; opacity: 0.85; }

        table.main { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.main th, table.main td {
            border: 0.5px solid #ccc;
            padding: 0 1px;
            text-align: center;
            font-size: {{ $fsNum }}px;
            overflow: hidden;
        }
        /* Los números de nota son lo que se lee: negrita. El interlineado
           ajustado compensa el aumento de letra para no ganar páginas. */
        table.main td { font-weight: bold; line-height: 1; }
        table.main tbody td { padding-top: 0; padding-bottom: 0; }

        .th-dark { background: #2c3e50; color: #fff; font-size: {{ $fsHead }}px; }
        .th-mat { background: #f8f9fa; color: #333; font-weight: bold; font-size: {{ $fsHead + 1 }}px; border-bottom: 1.5px solid #f39c12; line-height:1.15; word-break:normal; word-wrap:break-word; white-space:normal; padding: 2px 1px; }
        .th-trim { background: #fafafa; font-size: {{ $fsHead }}px; color: #666; }
        .th-prom { background: #fafafa; font-size: {{ $fsHead }}px; color: #333; font-weight: bold; }
        .th-grupo { background: #2c3e50; color: #fff; font-weight: bold; font-size: {{ $fsHead }}px; }
        .th-grupo-sub { background: #34495e; font-size: {{ $fsHead }}px; color: #fff; font-weight: bold; }

        .th-asist-group { background: #eaf4fc; color: #1a5276; font-weight: bold; font-size: {{ $fsHead }}px; border-bottom: 1.5px solid #3498db; }
        .th-vert { vertical-align: bottom; text-align: center; padding: 2px 0 !important; height: {{ $hVert }}px; background: #fafafa; }

        .est-name { text-align: left !important; padding-left: 3px !important; font-size: {{ $fsName }}px; font-weight: normal !important; white-space: normal; word-wrap: break-word; line-height: 1.02; }
        /* Reprobados: celda con fondo rojo claro + texto rojo oscuro negrita */
        .nota-baja { background:#fde0e0 !important; color: #c0392b; font-weight: bold; }
        .prom-col { background: #fffdf0; font-weight: bold; font-size: {{ $fsNum }}px; }
        .grupo-val { color: #6c3483; font-weight: bold; font-size: {{ $fsNum }}px; }
        .suma-col { font-weight: bold; background: #f0faf0; font-size: {{ $fsNum }}px; }
        .prom-final { font-weight: bold; background: #e8f5e9; font-size: {{ $fsNum + 2 }}px; }

        .asist-dt { color: #27ae60; }
        .asist-ta { color: #f39c12; }
        .asist-tl { color: #2980b9; }
        .asist-tf { color: #e74c3c; font-weight: bold; }
        .asist-total { font-weight: bold; background: #f0f7ff; }

        tbody tr:nth-child(even) td { background: #fcfcfc; }

        .footer { position: fixed; bottom: 3mm; left: 3mm; right: 3mm; font-size: 6px; color: #bbb; border-top: 0.5px solid #eee; padding-top: 1px; }
    </style>
</head>
<body>
    <div class="fecha-box">{{ now()->format('d/m/Y') }}</div>

    <div class="header">
        <div class="logo">
            @php $sc = $config ?? \App\Models\SistemaConfiguracion::actual(); @endphp
            @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" alt="Logo">
            @elseif(file_exists(public_path('img/logo.png')))
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
        $materiasListOrig = $asignaciones->values();

        // ── Reordenar dentro de cada campo: promediables primero, luego el resto.
        $campoOrder = [];
        $promPorGrupoTmp = [];
        if (isset($gruposMap)) {
            foreach ($gruposMap as $matCod => $grp) {
                $proms = $grp->materiasPromediables->pluck('mat_codigo')->toArray();
                if (count($proms) >= 2) $promPorGrupoTmp[$grp->grupo_id] = $proms;
            }
        }
        $esAgrupadaFn = function ($matCod) use ($gruposMap, $promPorGrupoTmp) {
            $grp = $gruposMap[$matCod] ?? null;
            if (!$grp) return false;
            $proms = $promPorGrupoTmp[$grp->grupo_id] ?? [];
            return in_array($matCod, $proms);
        };
        foreach ($materiasListOrig as $cmd) {
            $c = $cmd->materia->mat_campo ?? '__sc__';
            if (!isset($campoOrder[$c])) $campoOrder[$c] = count($campoOrder);
        }
        $materiasList = $materiasListOrig->sortBy(function ($cmd) use ($campoOrder, $esAgrupadaFn) {
            $c = $cmd->materia->mat_campo ?? '__sc__';
            return sprintf('%03d_%d', $campoOrder[$c], $esAgrupadaFn($cmd->mat_codigo) ? 0 : 1);
        })->values();

        $numPeriodos = $periodos->count();
        $showTrimCols = $numPeriodos > 1;

        // PROM va inmediatamente después de la última materia PROMEDIABLE del grupo.
        $ultimaMatPromGrupo   = [];
        $promediablesPorGrupo = [];
        foreach ($materiasList as $cmd) {
            $grp = $gruposMap[$cmd->mat_codigo] ?? null;
            if (!$grp) continue;
            $proms = $grp->materiasPromediables->pluck('mat_codigo')->toArray();
            if (count($proms) >= 2 && in_array($cmd->mat_codigo, $proms)) {
                $promediablesPorGrupo[$grp->grupo_id] = $proms;
                $ultimaMatPromGrupo[$grp->grupo_id]   = $cmd->mat_codigo;
            }
        }

        $colsMat = 0;
        foreach ($materiasList as $cmd) {
            $colsMat += $showTrimCols ? ($numPeriodos + 1) : 1;
            $grp = $gruposMap[$cmd->mat_codigo] ?? null;
            if ($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo) {
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
            <tr>
                <th rowspan="2" class="th-dark">N°</th>
                <th rowspan="2" class="th-dark" style="text-align:left !important;padding-left:2px !important;">NÓMINA</th>
                @foreach($materiasList as $cmd)
                    <th colspan="{{ $numPeriodos + 1 }}" class="th-mat">{{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        <th colspan="{{ $numPeriodos + 1 }}" class="th-grupo">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 12, 'UTF-8'), 'UTF-8') }}</th>
                    @endif
                @endforeach
                <th rowspan="2" class="th-dark" style="font-size:{{ $fsHead + 1 }}px;">∑</th>
                <th rowspan="2" class="th-dark" style="font-size:{{ $fsHead + 1 }}px;">x̄</th>
                @foreach($periodos as $p)
                    <th colspan="5" class="th-asist-group">{{ $p->periodo_numero }}° TRIM.</th>
                @endforeach
            </tr>
            <tr>
                @foreach($materiasList as $cmd)
                    @foreach($periodos as $p)
                        <th class="th-trim">{{ $p->periodo_numero }}°</th>
                    @endforeach
                    <th class="th-prom">x̄</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        @foreach($periodos as $p)
                            <th class="th-grupo-sub">{{ $p->periodo_numero }}°</th>
                        @endforeach
                        <th class="th-grupo-sub">x̄</th>
                    @endif
                @endforeach
                @foreach($periodos as $p)
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#f39c12">Atrasos</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#2980b9">Licencias</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#e74c3c">Faltas</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#27ae60">Días Trabajados</text></svg></th>
                    <th class="th-vert" style="background:#eaf4fc;"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#1a5276">Total Días Háb.</text></svg></th>
                @endforeach
            </tr>
        @else
            {{-- ═══ MODO TRIMESTRE ÚNICO ═══ --}}
            <tr>
                <th rowspan="2" class="th-dark">N°</th>
                <th rowspan="2" class="th-dark" style="text-align:left !important;padding-left:2px !important;">NÓMINA</th>
                @foreach($materiasList as $cmd)
                    <th rowspan="2" class="th-mat">{{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}</th>
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        <th rowspan="2" class="th-grupo">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 12, 'UTF-8'), 'UTF-8') }}</th>
                    @endif
                @endforeach
                @foreach($periodos as $p)
                    <th colspan="5" class="th-asist-group">{{ $p->periodo_numero }}° TRIM.</th>
                @endforeach
            </tr>
            <tr>
                @foreach($periodos as $p)
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#f39c12">Atrasos</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#2980b9">Licencias</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#e74c3c">Faltas</text></svg></th>
                    <th class="th-vert"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#27ae60">Días Trabajados</text></svg></th>
                    <th class="th-vert" style="background:#eaf4fc;"><svg width="{{ $fsVert + 5 }}" height="{{ $hVert - 4 }}" xmlns="http://www.w3.org/2000/svg"><text x="{{ $fsVert + 1.5 }}" y="{{ $hVert - 7 }}" transform="rotate(-90,{{ $fsVert + 1.5 }},{{ $hVert - 7 }})" font-family="Arial" font-size="{{ $fsVert }}" font-weight="bold" fill="#1a5276">Total Días Háb.</text></svg></th>
                @endforeach
            </tr>
        @endif
        </thead>
        <tbody>
            @foreach($data as $i => $fila)
                @php
                    $est = $fila['estudiante'];
                    $retirado = isset($est->est_visible) && $est->est_visible == 0;
                @endphp
                <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                    <td style="font-weight:bold;color:{{ $retirado ? '#c0392b' : '#999' }};">{{ $lista[$est->est_codigo] ?? ($i + 1) }}</td>
                    <td class="est-name" style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">
                        {{ mb_strtoupper($est->est_apellidos . ' ' . $est->est_nombres, 'UTF-8') }}
                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 3px;border-radius:2px;font-size:{{ $fsHead - 1 }}px;margin-left:2px;">RET</span>@endif
                    </td>
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
                        @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            @php $matCodsG = $promediablesPorGrupo[$grp->grupo_id]; @endphp
                            @if($showTrimCols)
                                @foreach($periodos as $p)
                                    @php $sg=0;$cg=0; foreach($matCodsG as $mc){$v=$fila['materias'][$mc]['trimestres'][$p->periodo_numero]??0; if($v>0){$sg+=$v;$cg++;}} $promGT=$cg>0?round($sg/$cg,0):0; @endphp
                                    <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                                @endforeach
                                @php $spg=0;$cpg=0; foreach($matCodsG as $mc){$pm=$fila['materias'][$mc]['promedio']??0; if($pm>0){$spg+=$pm;$cpg++;}} $promGA=$cpg>0?round($spg/$cpg,0):0; @endphp
                                <td class="grupo-val">{{ $promGA > 0 ? $promGA : '' }}</td>
                            @else
                                @php $sg=0;$cg=0; foreach($matCodsG as $mc){$v=collect($fila['materias'][$mc]['trimestres']??[])->first()??0; if($v>0){$sg+=$v;$cg++;}} $promGT=$cg>0?round($sg/$cg,0):0; @endphp
                                <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                            @endif
                        @endif
                    @endforeach
                    @if($showTrimCols)
                        <td class="suma-col">{{ $fila['suma'] > 0 ? $fila['suma'] : '' }}</td>
                        <td class="prom-final">{{ $fila['promedio'] > 0 ? $fila['promedio'] : '' }}</td>
                    @endif
                    @foreach($periodos as $p)
                        @php
                            $a = $fila['asistencia'][$p->periodo_numero] ?? ['dt'=>0,'ta'=>0,'tl'=>0,'tf'=>0,'pres'=>0,'total'=>0,'visible'=>true];
                            $visible = $a['visible'] ?? true;
                            // DÍAS TRABAJADOS = Asist + Lic. TOTAL = calendario − feriados (= DT + Faltas).
                            $diasTrab     = $visible ? ($a['pres'] + $a['tl']) : 0;
                            $totalDiasHab = $visible ? $a['total'] : 0;
                        @endphp
                        <td class="asist-ta">{{ $visible ? $a['ta'] : '' }}</td>
                        <td class="asist-tl">{{ $visible ? $a['tl'] : '' }}</td>
                        <td class="asist-tf">{{ $visible ? $a['tf'] : '' }}</td>
                        <td class="asist-dt">{{ $visible ? $diasTrab : '' }}</td>
                        <td class="asist-total"><strong>{{ $visible ? $totalDiasHab : '' }}</strong></td>
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
