<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Centralizador - {{ $curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 7px; padding: 4mm; }
        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 45px; vertical-align: middle; }
        .logo img { width: 40px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 9px; margin: 0; line-height: 1.1; font-weight:bold; }
        .header-info p { font-size: 6px; margin: 0; }
        .fecha-box { position: absolute; top: 4mm; right: 4mm; border: 1px solid #000; padding: 2px 6px; font-weight: bold; font-size: 6px; text-align: center; background:#fff; }
        .title-section { text-align: center; margin: 3px 0; border-bottom: 1.5px solid #000; padding-bottom: 2px; }
        .title-section h2 { font-size: 10px; font-weight: bold; }
        .title-section p { font-size: 7px; }

        table.main { width: 100%; border-collapse: collapse; }
        table.main th, table.main td { border: 0.5px solid #555; padding: 1px 2px; text-align: center; }
        table.main th { font-weight: bold; }

        /* Cabeceras de materia: nombre completo con wrap (dompdf-friendly) */
        .mat-header {
            background: #e8e8e8; color: #000; font-size: 5.5px; font-weight: bold;
            line-height: 1.1; padding: 2px 1px;
            vertical-align: middle;
            word-break: break-word;
        }
        .mat-header-anual { background:#e8e8e8; color:#000; font-size:6.5px; font-weight:bold; padding:2px 1px !important; }
        .trim-header { background: #f5f5f5; color: #000; font-size: 5.5px; }
        .prom-header { background: #d5d5d5; color: #000; font-size: 5.5px; font-weight: bold; }
        /* Promedio por campo — bien diferenciado al lado de las materias */
        .grupo-header { background: #2c3e50; color: #fff; font-size: 5.5px; font-weight: bold; }
        .grupo-sub    { background: #34495e; color: #fff; font-size: 5.5px; font-weight: bold; }
        .grupo-val    { background: #ecf0f1; font-weight: bold; color: #2c3e50; }

        .est-name { text-align: left !important; white-space: normal; word-wrap: break-word; font-size: 6.5px; padding-left: 3px !important; line-height: 1.15; }
        .prom-col { background: #fff3cd; font-weight: bold; }
        .suma-col { background: #d4edda; font-weight: bold; }
        .prom-final { background: #c3e6cb; font-weight: bold; font-size: 7px; }
        /* Reprobados: celda con fondo rojo claro + texto rojo oscuro negrita */
        .nota-baja { background:#fde0e0 !important; color: #c0392b; font-weight: bold; }
        .nota-cero { color: #c0392b; font-weight: bold; background: #fce4ec; }

        .asist-header { background: #d6eaf8 !important; font-size: 5.5px; }
        .psico-header { background: #ebd6f3 !important; font-size: 5.5px; }
        .enf-header { background: #fadbd8 !important; font-size: 5.5px; }
        .group-header-extra { font-size: 6px; font-weight: bold; }

        .footer { position: fixed; bottom: 4mm; left: 4mm; font-size: 5px; color: #888; }
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

    <div class="title-section">
        <p style="font-size:7px;font-weight:bold;">{{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }}</p>
        <h2>CENTRALIZADOR — GESTIÓN {{ $gestion }}</h2>
    </div>

    @php
        $materiasListOrig = $asignaciones->values();

        // ── Reordenar: dentro de cada campo, promediables primero, luego el resto.
        // Preserva orden de campos por primera aparición; orden interno: promediables 0, resto 1; luego matc_orden.
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
        $isAnual = $numPeriodos > 1;

        // PROM va inmediatamente después de la ÚLTIMA materia PROMEDIABLE del grupo.
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
    @endphp

    <table class="main">
        <thead>
            @if($isAnual)
                <tr>
                    <th rowspan="2" style="width:14px;background:#ddd;">N°</th>
                    <th rowspan="2" style="min-width:100px;background:#ddd;">APELLIDOS Y NOMBRES</th>
                    @foreach($materiasList as $cmd)
                        <th colspan="{{ $numPeriodos + 1 }}" class="mat-header" title="{{ $cmd->materia->mat_nombre }}">
                            {{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}
                        </th>
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            <th colspan="{{ $numPeriodos + 1 }}" class="grupo-header" title="Promedio del campo">
                                PROM.<br>{{ mb_strtoupper($grp->grupo_nombre, 'UTF-8') }}
                            </th>
                        @endif
                    @endforeach
                    <th rowspan="2" class="suma-col" style="width:16px;">SUMA<br>ANUAL</th>
                    <th rowspan="2" class="prom-final" style="width:18px;">PROM.<br>ANUAL</th>
                    <th colspan="5" class="group-header-extra asist-header" title="Días Trab. / Total Asist. / Atrasos / Licencias / Faltas (Anual)">ASISTENCIA<br>(ANUAL)</th>
                    <th colspan="3" class="group-header-extra psico-header">CONTROL Y<br>SEGUIM.</th>
                    <th rowspan="2" class="group-header-extra enf-header" style="width:14px;">ENFER<br>MERÍA</th>
                </tr>
                <tr>
                    @foreach($materiasList as $cmd)
                        @foreach($periodos as $p)
                            <th class="trim-header">{{ $p->periodo_numero }}°T</th>
                        @endforeach
                        <th class="prom-header">PROM</th>
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            @foreach($periodos as $p)
                                <th class="grupo-sub">{{ $p->periodo_numero }}°T</th>
                            @endforeach
                            <th class="grupo-sub">PROM</th>
                        @endif
                    @endforeach
                    <th class="asist-header" title="Atrasos">ATR</th>
                    <th class="asist-header" title="Total Licencias (días)">TL</th>
                    <th class="asist-header" title="Total Faltas">TF</th>
                    <th class="asist-header" title="Días Trabajados (asistencias)">DT</th>
                    <th class="asist-header" title="Total Días Hábiles = Asist + Lic">TOT</th>
                    <th class="psico-header">LLAMA<br>DAS</th>
                    <th class="psico-header">COMPR<br>SÍ</th>
                    <th class="psico-header">COMPR<br>NO</th>
                </tr>
            @else
                <tr>
                    <th style="width:14px;background:#ddd;">N°</th>
                    <th style="min-width:100px;background:#ddd;">APELLIDOS Y NOMBRES</th>
                    @foreach($materiasList as $cmd)
                        <th class="mat-header" title="{{ $cmd->materia->mat_nombre }}">
                            {{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}
                        </th>
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            <th class="grupo-header" title="Promedio del campo">
                                PROM.<br>{{ mb_strtoupper($grp->grupo_nombre, 'UTF-8') }}
                            </th>
                        @endif
                    @endforeach
                    <th class="prom-final" style="width:18px;">PROM.</th>
                    <th class="suma-col" style="width:16px;">SUMA</th>
                    <th class="prom-final" style="width:18px;">PROM.</th>
                    <th class="asist-header" title="Atrasos">ATR</th>
                    <th class="asist-header" title="Licencias (días)">TL</th>
                    <th class="asist-header" title="Faltas">TF</th>
                    <th class="asist-header" title="Días Trabajados (asistencias)">DT</th>
                    <th class="asist-header" title="Total Días Hábiles = Asist + Lic">TOT</th>
                    <th class="psico-header">LLAMA<br>DAS</th>
                    <th class="psico-header">COMPR<br>SÍ</th>
                    <th class="psico-header">COMPR<br>NO</th>
                    <th class="enf-header">ENF</th>
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
                    <td style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">{{ $lista[$est->est_codigo] ?? ($i + 1) }}</td>
                    <td class="est-name" style="{{ $retirado ? 'color:#c0392b;font-weight:700;' : '' }}">
                        {{ mb_strtoupper($est->est_apellidos . ' ' . $est->est_nombres, 'UTF-8') }}
                        @if($retirado)<span style="background:#c0392b;color:#fff;padding:0 3px;border-radius:2px;font-size:5px;margin-left:2px;">RETIRADO</span>@endif
                    </td>

                    @if($isAnual)
                        @foreach($materiasList as $cmd)
                            @php $matData = $fila['materias'][$cmd->mat_codigo] ?? ['trimestres' => [], 'promedio' => 0]; @endphp
                            @foreach($periodos as $p)
                                @php $val = $matData['trimestres'][$p->periodo_numero] ?? 0; @endphp
                                <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}{{ $val == 0 ? ' nota-cero' : '' }}">{{ $val ?: '' }}</td>
                            @endforeach
                            <td class="prom-col">{{ $matData['promedio'] }}</td>

                            {{-- Promedio del grupo justo después de la última materia PROMEDIABLE --}}
                            @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                            @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                                @php $matCodsG = $promediablesPorGrupo[$grp->grupo_id]; @endphp
                                @foreach($periodos as $p)
                                    @php
                                        $sg = 0; $cg = 0;
                                        foreach ($matCodsG as $mc) {
                                            $v = $fila['materias'][$mc]['trimestres'][$p->periodo_numero] ?? 0;
                                            if ($v > 0) { $sg += $v; $cg++; }
                                        }
                                        $promGT = $cg > 0 ? round($sg / $cg, 0) : 0;
                                    @endphp
                                    <td class="grupo-val">{{ $promGT ?: '' }}</td>
                                @endforeach
                                @php
                                    $sp = 0; $cp = 0;
                                    foreach ($matCodsG as $mc) {
                                        $pm = $fila['materias'][$mc]['promedio'] ?? 0;
                                        if ($pm > 0) { $sp += $pm; $cp++; }
                                    }
                                    $promGAnual = $cp > 0 ? round($sp / $cp, 0) : 0;
                                @endphp
                                <td class="grupo-val" style="font-size:6px;">{{ $promGAnual ?: '' }}</td>
                            @endif
                        @endforeach
                        <td class="suma-col">{{ $fila['suma'] }}</td>
                        <td class="prom-final">{{ $fila['promedio'] }}</td>
                    @else
                        @php $sumaT = 0; $countT = 0; @endphp
                        @foreach($materiasList as $cmd)
                            @php
                                $matData = $fila['materias'][$cmd->mat_codigo] ?? ['trimestres' => [], 'promedio' => 0];
                                $val = collect($matData['trimestres'])->first() ?? 0;
                                if ($val > 0) { $sumaT += $val; $countT++; }
                            @endphp
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}{{ $val == 0 ? ' nota-cero' : '' }}">{{ $val ?: '' }}</td>

                            @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                            @if($grp && ($ultimaMatPromGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                                @php
                                    $matCodsG = $promediablesPorGrupo[$grp->grupo_id];
                                    $sg = 0; $cg = 0;
                                    foreach ($matCodsG as $mc) {
                                        $v = collect($fila['materias'][$mc]['trimestres'] ?? [])->first() ?? 0;
                                        if ($v > 0) { $sg += $v; $cg++; }
                                    }
                                    $promGT = $cg > 0 ? round($sg / $cg, 0) : 0;
                                @endphp
                                <td class="grupo-val">{{ $promGT ?: '' }}</td>
                            @endif
                        @endforeach
                        @php $promT = $countT > 0 ? round($sumaT / $countT, 1) : 0; @endphp
                        <td class="prom-col">{{ $promT }}</td>
                        <td class="suma-col">{{ $sumaT }}</td>
                        <td class="prom-final">{{ $promT }}</td>
                    @endif

                    {{-- Asistencia --}}
                    @php
                        $atrT = 0; $tlT = 0; $tfT = 0; $presT = 0; $totT = 0;
                        foreach ($fila['asistencia'] as $a) {
                            if (!($a['visible'] ?? true)) continue;
                            $atrT  += $a['ta'];
                            $tlT   += $a['tl'];
                            $tfT   += $a['tf'];
                            $presT += $a['pres'] ?? 0;
                            $totT  += $a['total'] ?? 0;
                        }
                        // DT = Asist + Lic. TOTAL = días hábiles calendario − feriados (= DT + Faltas).
                        $dtT = $presT + $tlT;
                    @endphp
                    <td>{{ $atrT }}</td>
                    <td>{{ $tlT }}</td>
                    <td>{{ $tfT }}</td>
                    <td>{{ $dtT }}</td>
                    <td><strong>{{ $totT }}</strong></td>

                    {{-- Psicopedagogía: total llamadas, compromisos sí, compromisos no --}}
                    @php
                        $lsi = 0; $csi = 0; $cno = 0;
                        foreach ($fila['psico'] as $ps) {
                            $lsi += $ps['llamadas_si'] ?? 0;
                            $csi += $ps['compromisos_si'] ?? 0;
                            $cno += $ps['compromisos_no'] ?? 0;
                        }
                    @endphp
                    <td>{{ $lsi }}</td>
                    <td>{{ $csi }}</td>
                    <td>{{ $cno }}</td>

                    {{-- Enfermería --}}
                    <td>{{ $fila['enfermeria'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | Centralizador {{ $curso->cur_nombre }} | Gestión {{ $gestion }}
    </div>
</body>
</html>
