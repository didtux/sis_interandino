<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Centralizador - {{ $curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 5.5px; padding: 4mm; }
        .header { display: table; width: 100%; margin-bottom: 3px; }
        .logo { display: table-cell; width: 45px; vertical-align: middle; }
        .logo img { width: 38px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 8px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 5.5px; margin: 0; }
        .fecha-box { position: absolute; top: 4mm; right: 4mm; background: #c0392b; color: #fff; padding: 2px 6px; border-radius: 6px; font-weight: bold; font-size: 5.5px; text-align: center; }
        .title-section { text-align: center; margin: 3px 0; border-bottom: 1.5px solid #000; padding-bottom: 2px; }
        .title-section h2 { font-size: 9px; font-weight: bold; }
        .title-section p { font-size: 6.5px; }

        table.main { width: 100%; border-collapse: collapse; }
        table.main th, table.main td { border: 0.5px solid #555; padding: 1px 2px; text-align: center; }
        table.main th { font-weight: bold; }

        .mat-header { background: #f5deb3; color: #000; font-size: 5px; font-weight: bold; }
        .trim-header { background: #ffeaa7; color: #000; font-size: 4.5px; }
        .prom-header { background: #fdcb6e; color: #000; font-size: 4.5px; font-weight: bold; }
        .grupo-header { background: #d2b4de; color: #000; font-size: 4.5px; font-weight: bold; }
        .grupo-sub { background: #e8d5f5; font-size: 4.5px; font-weight: bold; }
        .grupo-val { background: #f3e5f5; font-weight: bold; color: #6c3483; }

        .est-name { text-align: left !important; white-space: nowrap; font-size: 5.5px; padding-left: 2px !important; }
        .prom-col { background: #fff3cd; font-weight: bold; }
        .suma-col { background: #d4edda; font-weight: bold; }
        .prom-final { background: #c3e6cb; font-weight: bold; font-size: 6px; }
        .nota-baja { color: #c0392b; font-weight: bold; }
        .nota-cero { color: #c0392b; font-weight: bold; background: #fce4ec; }

        .asist-header { background: #aed6f1 !important; font-size: 4.5px; }
        .psico-header { background: #d7bde2 !important; font-size: 4px; }
        .enf-header { background: #f5b7b1 !important; font-size: 4.5px; }
        .group-header-extra { font-size: 5px; font-weight: bold; }

        .footer { position: fixed; bottom: 4mm; left: 4mm; font-size: 5px; color: #888; }
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

    <div class="title-section">
        <p style="font-size:7px;font-weight:bold;">{{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }}</p>
        <h2>CENTRALIZADOR — GESTIÓN {{ $gestion }}</h2>
    </div>

    @php
        $materiasList = $asignaciones->values();
        $numPeriodos = $periodos->count();
        $isAnual = $numPeriodos > 1;

        // Determinar qué grupos aplican y cuál es la última materia de cada grupo
        $gruposOrden = [];
        $ultimaMatGrupo = [];
        if(isset($gruposMap)) {
            foreach($materiasList as $idx => $cmd) {
                $grp = $gruposMap[$cmd->mat_codigo] ?? null;
                if($grp) {
                    $gruposOrden[$grp->grupo_id] = $grp;
                    $ultimaMatGrupo[$grp->grupo_id] = $cmd->mat_codigo;
                }
            }
        }
    @endphp

    <table class="main">
        <thead>
            @if($isAnual)
                <tr>
                    <th rowspan="2" style="width:12px;background:#ddd;">N°</th>
                    <th rowspan="2" style="min-width:80px;background:#ddd;">APELLIDOS Y NOMBRES</th>
                    @foreach($materiasList as $cmd)
                        <th colspan="{{ $numPeriodos + 1 }}" class="mat-header">
                            {{ mb_strtoupper(mb_substr($cmd->materia->mat_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}
                        </th>
                        {{-- Columna de grupo después de la última materia del grupo --}}
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            <th colspan="{{ $numPeriodos + 1 }}" class="grupo-header">
                                {{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 15, 'UTF-8'), 'UTF-8') }}
                            </th>
                        @endif
                    @endforeach
                    <th rowspan="2" class="suma-col" style="width:16px;">SUMA<br>ANUAL</th>
                    <th rowspan="2" class="prom-final" style="width:18px;">PROM.<br>ANUAL</th>
                    <th colspan="4" class="group-header-extra asist-header">FALTAS Y<br>ATRASOS</th>
                    <th colspan="4" class="group-header-extra psico-header">CONTROL Y<br>SEGUIM.</th>
                    <th rowspan="2" class="group-header-extra enf-header" style="width:14px;">ENFER<br>MERÍA</th>
                </tr>
                <tr>
                    @foreach($materiasList as $cmd)
                        @foreach($periodos as $p)
                            <th class="trim-header">{{ $p->periodo_numero }}°T</th>
                        @endforeach
                        <th class="prom-header">PROM</th>
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            @foreach($periodos as $p)
                                <th class="grupo-sub">{{ $p->periodo_numero }}°T</th>
                            @endforeach
                            <th class="grupo-sub">PROM</th>
                        @endif
                    @endforeach
                    <th class="asist-header">DT</th>
                    <th class="asist-header">TA</th>
                    <th class="asist-header">TL</th>
                    <th class="asist-header">TF</th>
                    <th class="psico-header">SI</th>
                    <th class="psico-header">NO</th>
                    <th class="psico-header">SI</th>
                    <th class="psico-header">NO</th>
                </tr>
            @else
                <tr>
                    <th style="width:12px;background:#ddd;">N°</th>
                    <th style="min-width:80px;background:#ddd;">APELLIDOS Y NOMBRES</th>
                    @foreach($materiasList as $cmd)
                        <th class="mat-header">{{ mb_strtoupper(mb_substr($cmd->materia->mat_nombre, 0, 10, 'UTF-8'), 'UTF-8') }}</th>
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            <th class="grupo-header">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 12, 'UTF-8'), 'UTF-8') }}</th>
                        @endif
                    @endforeach
                    <th class="prom-final" style="width:18px;">PROM.</th>
                    <th class="suma-col" style="width:16px;">SUMA</th>
                    <th class="prom-final" style="width:18px;">PROM.</th>
                    <th class="asist-header">DT</th>
                    <th class="asist-header">TA</th>
                    <th class="asist-header">TL</th>
                    <th class="asist-header">TF</th>
                    <th class="psico-header">SI</th>
                    <th class="psico-header">NO</th>
                    <th class="psico-header">SI</th>
                    <th class="psico-header">NO</th>
                    <th class="enf-header">ENF</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach($data as $i => $fila)
                @php $est = $fila['estudiante']; @endphp
                <tr>
                    <td>{{ $lista[$est->est_codigo] ?? ($i + 1) }}</td>
                    <td class="est-name">{{ mb_strtoupper($est->est_apellidos . ' ' . $est->est_nombres, 'UTF-8') }}</td>

                    @if($isAnual)
                        @foreach($materiasList as $cmd)
                            @php $matData = $fila['materias'][$cmd->mat_codigo] ?? ['trimestres' => [], 'promedio' => 0]; @endphp
                            @foreach($periodos as $p)
                                @php $val = $matData['trimestres'][$p->periodo_numero] ?? 0; @endphp
                                <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}{{ $val == 0 ? ' nota-cero' : '' }}">{{ $val }}</td>
                            @endforeach
                            <td class="prom-col">{{ $matData['promedio'] }}</td>

                            {{-- Columnas de promedio de grupo --}}
                            @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                            @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                                @php
                                    $matCodsG = $grp->materias->pluck('mat_codigo')->toArray();
                                @endphp
                                @foreach($periodos as $p)
                                    @php
                                        $sg = 0; $cg = 0;
                                        foreach($matCodsG as $mc) {
                                            $v = $fila['materias'][$mc]['trimestres'][$p->periodo_numero] ?? 0;
                                            $sg += $v; $cg++;
                                        }
                                        $promGT = $cg > 0 ? round($sg / $cg, 0) : 0;
                                    @endphp
                                    <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                                @endforeach
                                @php
                                    $sumaPromG = 0; $cntPromG = 0;
                                    foreach($matCodsG as $mc) {
                                        $pm = $fila['materias'][$mc]['promedio'] ?? 0;
                                        $sumaPromG += $pm; $cntPromG++;
                                    }
                                    $promGAnual = $cntPromG > 0 ? round($sumaPromG / $cntPromG, 0) : 0;
                                @endphp
                                <td class="grupo-val" style="font-size:6px;">{{ $promGAnual > 0 ? $promGAnual : '' }}</td>
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
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}{{ $val == 0 ? ' nota-cero' : '' }}">{{ $val }}</td>

                            {{-- Promedio grupo trimestre único --}}
                            @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                            @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                                @php
                                    $matCodsG = $grp->materias->pluck('mat_codigo')->toArray();
                                    $sg = 0; $cg = 0;
                                    foreach($matCodsG as $mc) {
                                        $v = collect($fila['materias'][$mc]['trimestres'] ?? [])->first() ?? 0;
                                        $sg += $v; $cg++;
                                    }
                                    $promGT = $cg > 0 ? round($sg / $cg, 0) : 0;
                                @endphp
                                <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                            @endif
                        @endforeach
                        @php $promT = $countT > 0 ? round($sumaT / $countT, 1) : 0; @endphp
                        <td class="prom-col">{{ $promT }}</td>
                        <td class="suma-col">{{ $sumaT }}</td>
                        <td class="prom-final">{{ $promT }}</td>
                    @endif

                    {{-- Asistencia --}}
                    @php
                        $dtT = 0; $taT = 0; $tlT = 0; $tfT = 0;
                        foreach ($fila['asistencia'] as $a) {
                            $dtT += $a['dt']; $taT += $a['ta']; $tlT += $a['tl']; $tfT += $a['tf'];
                        }
                    @endphp
                    <td>{{ $dtT }}</td>
                    <td>{{ $taT }}</td>
                    <td>{{ $tlT }}</td>
                    <td>{{ $tfT }}</td>

                    {{-- Psicopedagogía --}}
                    @php
                        $lsi = 0; $csi = 0; $cno = 0;
                        foreach ($fila['psico'] as $ps) {
                            $lsi += $ps['llamadas_si']; $csi += $ps['compromisos_si']; $cno += $ps['compromisos_no'];
                        }
                    @endphp
                    <td>{{ $lsi }}</td>
                    <td>0</td>
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
