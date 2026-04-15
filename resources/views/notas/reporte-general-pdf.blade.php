<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Registro General - {{ $curso->cur_nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 6px; padding: 5mm; }
        .header { display: table; width: 100%; margin-bottom: 4px; }
        .logo { display: table-cell; width: 50px; vertical-align: middle; }
        .logo img { width: 40px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; text-align: center; }
        .header-info h3 { font-size: 9px; margin: 0; line-height: 1.1; }
        .header-info p { font-size: 6px; margin: 0; }
        .fecha-box { position: absolute; top: 5mm; right: 5mm; background: #dc3545; color: #fff; padding: 3px 8px; border-radius: 8px; font-weight: bold; font-size: 6px; text-align: center; }
        .title-section { text-align: center; margin: 4px 0; border-bottom: 2px solid #000; padding-bottom: 3px; }
        .title-section h2 { font-size: 10px; font-weight: bold; }
        .title-section p { font-size: 7px; }
        table.main { width: 100%; border-collapse: collapse; }
        table.main th, table.main td { border: 1px solid #333; padding: 1px 2px; text-align: center; font-size: 5.5px; }
        table.main th { background: #2c3e50; color: #fff; font-size: 5px; }
        .est-name { text-align: left !important; white-space: nowrap; font-size: 5.5px; padding-left: 3px !important; }
        .mat-group { background: #f39c12 !important; color: #000 !important; font-weight: bold; font-size: 5px; }
        .trim-sub { background: #ffeaa7 !important; color: #000 !important; font-size: 4.5px; }
        .prom-sub { background: #fdcb6e !important; color: #000 !important; font-weight: bold; font-size: 4.5px; }
        .grupo-header { background: #d2b4de !important; color: #000 !important; font-weight: bold; font-size: 4.5px; }
        .grupo-sub { background: #e8d5f5 !important; color: #000 !important; font-size: 4.5px; font-weight: bold; }
        .grupo-val { background: #f3e5f5; font-weight: bold; color: #6c3483; }
        .prom-col { background: #fff3cd; font-weight: bold; }
        .suma-col { background: #d4edda; font-weight: bold; }
        .prom-final { background: #c3e6cb; font-weight: bold; }
        .nota-baja { color: #e74c3c; font-weight: bold; }
        .asist-group { background: #17a2b8 !important; color: #fff !important; }
        .asist-sub { background: #b8daff !important; color: #000 !important; font-size: 4.5px; }
        .footer { position: fixed; bottom: 5mm; left: 5mm; font-size: 5.5px; color: #666; }
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
        <h2>REGISTRO GENERAL DE NOTAS — {{ mb_strtoupper($periodoNombre, 'UTF-8') }}</h2>
        <p>AÑO DE ESCOLARIDAD: {{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }} | GESTIÓN {{ $gestion }}</p>
    </div>

    @php
        $materiasList = $asignaciones->values();
        $numPeriodos = $periodos->count();
        $showTrimCols = $numPeriodos > 1;

        // Determinar última materia de cada grupo
        $ultimaMatGrupo = [];
        if(isset($gruposMap)) {
            foreach($materiasList as $cmd) {
                $grp = $gruposMap[$cmd->mat_codigo] ?? null;
                if($grp) {
                    $ultimaMatGrupo[$grp->grupo_id] = $cmd->mat_codigo;
                }
            }
        }
    @endphp

    <table class="main">
        <thead>
            <tr>
                <th rowspan="{{ $showTrimCols ? 2 : 1 }}" style="width:15px;">N°</th>
                <th rowspan="{{ $showTrimCols ? 2 : 1 }}" style="min-width:100px;">APELLIDOS Y NOMBRES</th>
                @foreach($materiasList as $cmd)
                    @if($showTrimCols)
                        <th colspan="{{ $numPeriodos + 1 }}" class="mat-group">
                            {{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}
                        </th>
                    @else
                        <th class="mat-group">{{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}</th>
                    @endif
                    {{-- Header grupo --}}
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        @if($showTrimCols)
                            <th colspan="{{ $numPeriodos + 1 }}" class="grupo-header">
                                {{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 15, 'UTF-8'), 'UTF-8') }}
                            </th>
                        @else
                            <th class="grupo-header">{{ mb_strtoupper(mb_substr($grp->grupo_nombre, 0, 12, 'UTF-8'), 'UTF-8') }}</th>
                        @endif
                    @endif
                @endforeach
                @if($showTrimCols)
                    <th rowspan="2" style="background:#28a745;color:#fff;width:20px;">SUMA<br>ANUAL</th>
                    <th rowspan="2" style="background:#28a745;color:#fff;width:22px;">PROM.<br>ANUAL</th>
                @endif
                @foreach($periodos as $p)
                    <th colspan="4" class="asist-group" {{ $showTrimCols ? '' : '' }}>ASIST. {{ $p->periodo_numero }}°T</th>
                @endforeach
            </tr>
            @if($showTrimCols)
            <tr>
                @foreach($materiasList as $cmd)
                    @foreach($periodos as $p)
                        <th class="trim-sub">{{ $p->periodo_numero }}°T</th>
                    @endforeach
                    <th class="prom-sub">PROM</th>
                    {{-- Sub-headers grupo --}}
                    @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                    @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                        @foreach($periodos as $p)
                            <th class="grupo-sub">{{ $p->periodo_numero }}°T</th>
                        @endforeach
                        <th class="grupo-sub">PROM</th>
                    @endif
                @endforeach
                @foreach($periodos as $p)
                    <th class="asist-sub">DT</th>
                    <th class="asist-sub">TA</th>
                    <th class="asist-sub">TL</th>
                    <th class="asist-sub">TF</th>
                @endforeach
            </tr>
            @endif
        </thead>
        <tbody>
            @foreach($data as $i => $fila)
                @php $est = $fila['estudiante']; @endphp
                <tr>
                    <td>{{ $lista[$est->est_codigo] ?? ($i + 1) }}</td>
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

                        {{-- Datos grupo --}}
                        @php $grp = $gruposMap[$cmd->mat_codigo] ?? null; @endphp
                        @if($grp && ($ultimaMatGrupo[$grp->grupo_id] ?? '') === $cmd->mat_codigo)
                            @php $matCodsG = $grp->materias->pluck('mat_codigo')->toArray(); @endphp
                            @if($showTrimCols)
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
                                    $spg = 0; $cpg = 0;
                                    foreach($matCodsG as $mc) {
                                        $pm = $fila['materias'][$mc]['promedio'] ?? 0;
                                        $spg += $pm; $cpg++;
                                    }
                                    $promGA = $cpg > 0 ? round($spg / $cpg, 0) : 0;
                                @endphp
                                <td class="grupo-val" style="font-size:6px;">{{ $promGA > 0 ? $promGA : '' }}</td>
                            @else
                                @php
                                    $sg = 0; $cg = 0;
                                    foreach($matCodsG as $mc) {
                                        $v = collect($fila['materias'][$mc]['trimestres'] ?? [])->first() ?? 0;
                                        $sg += $v; $cg++;
                                    }
                                    $promGT = $cg > 0 ? round($sg / $cg, 0) : 0;
                                @endphp
                                <td class="grupo-val">{{ $promGT > 0 ? $promGT : '' }}</td>
                            @endif
                        @endif
                    @endforeach
                    @if($showTrimCols)
                        <td class="suma-col">{{ $fila['suma'] > 0 ? $fila['suma'] : '' }}</td>
                        <td class="prom-final">{{ $fila['promedio'] > 0 ? $fila['promedio'] : '' }}</td>
                    @endif
                    {{-- Asistencia --}}
                    @foreach($periodos as $p)
                        @php $a = $fila['asistencia'][$p->periodo_numero] ?? ['dt'=>0,'ta'=>0,'tl'=>0,'tf'=>0]; @endphp
                        <td>{{ $a['dt'] }}</td>
                        <td>{{ $a['ta'] }}</td>
                        <td>{{ $a['tl'] }}</td>
                        <td>{{ $a['tf'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | Registro General {{ $curso->cur_nombre }} | {{ $periodoNombre }} | Gestión {{ $gestion }}
    </div>
</body>
</html>
