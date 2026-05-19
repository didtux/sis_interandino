<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Boletín - {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 9px; padding: 8mm 10mm; color:#000; }
        /* Cabecera oficial (B/N con logo color) */
        .header-table { width:100%; border-collapse:collapse; margin-bottom:6px; }
        .header-table td { vertical-align:middle; padding:0; }
        .h-logo-cell { width:80px; text-align:center; }
        .h-logo-cell img { width:64px; height:auto; }
        .h-info-cell { text-align:center; }
        .h-info-cell .ue-nombre { font-weight:700; font-size:14px; letter-spacing:0.5px; }
        .h-info-cell .ue-dir    { font-size:8px; color:#333; line-height:1.3; }
        .h-info-cell .titulo-banda { display:inline-block; margin-top:4px; padding:2px 0; font-weight:700; font-size:13px; letter-spacing:1px; }
        .h-qr-cell { width:90px; text-align:center; }
        .h-qr-cell img { width:75px; height:75px; }
        .h-qr-cell .qr-label { font-size:6px; color:#555; text-transform:uppercase; letter-spacing:0.5px; margin-top:2px; }
        .h-qr-cell .copia-tag { display:inline-block; margin-top:2px; padding:1px 6px; border:1px solid #000; font-size:7px; font-weight:700; }
        .fecha-box { position: absolute; top: 8mm; right: 10mm; border:1.5px solid #000; padding: 3px 9px; font-weight: bold; font-size: 7px; text-align: center; background:#fff; color:#000; }

        /* Info estudiante */
        .info-table { width: 100%; border: none; border-collapse: collapse; margin-bottom: 6px; }
        .info-table td { padding: 3px 8px; font-size: 9px; border: none; }
        .info-label { font-weight: bold; font-size: 8px; width: 18%; }
        .info-value { font-weight: bold; font-size: 11px; }
        .nro-lista-cell { text-align: center; width: 15%; vertical-align: middle; border: none; }
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

        /* Bloque QR al pie */
        .qr-footer-table { width:100%; border-collapse:collapse; margin-top:8px; }
        .qr-footer-table td { vertical-align:middle; border:1px solid #000; padding:6px 8px; }
        .qr-footer-info { font-size:7px; line-height:1.4; }
        .qr-footer-title { font-weight:bold; font-size:8px; letter-spacing:1px; margin-bottom:3px; }
        .qr-footer-copia { display:inline-block; margin-top:3px; padding:1px 6px; border:1px solid #000; font-weight:bold; font-size:7px; }
        .qr-footer-img { width:75px; text-align:center; }
        .qr-footer-img img { width:65px; height:65px; }
    </style>
</head>
<body>
    <div class="fecha-box">Fecha<br>{{ now()->format('d/m/Y') }}</div>

    <table class="header-table">
        <tr>
            <td class="h-logo-cell">
                @php $sc = $config ?? \App\Models\SistemaConfiguracion::actual(); @endphp
                @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
                    <img src="{{ public_path('storage/'.$sc->config_logo) }}" alt="Logo">
                @elseif(file_exists(public_path('img/logo.png')))
                    <img src="{{ public_path('img/logo.png') }}" alt="Logo">
                @endif
            </td>
            <td class="h-info-cell">
                <div class="ue-nombre">UNIDAD EDUCATIVA PRIVADA INTERANDINO BOLIVIANO</div>
                <div class="ue-dir">
                    Calle V. Gutiérrez N° 3339 e/c Álvarez Plata y Catacora — Zona 16 de Julio · El Alto, La Paz, Bolivia<br>
                    Telf.: 2840320 · Fax: 2846479 · Resolución Administrativa RA 311/2006
                </div>
                <div class="titulo-banda">BOLETÍN DE CALIFICACIONES</div>
            </td>
        </tr>
    </table>

    @php
        // Detección "agrupada": forma parte de un grupo con ≥2 promediables.
        $promediablesPorGrupo = [];
        if (isset($gruposMap)) {
            foreach ($gruposMap as $matCod => $grp) {
                $proms = $grp->materiasPromediables->pluck('mat_codigo')->toArray();
                if (count($proms) >= 2) $promediablesPorGrupo[$grp->grupo_id] = $proms;
            }
        }
        $esAgrupada = function ($matCod) use ($gruposMap, $promediablesPorGrupo) {
            $grp = $gruposMap[$matCod] ?? null;
            if (!$grp) return false;
            $proms = $promediablesPorGrupo[$grp->grupo_id] ?? [];
            return in_array($matCod, $proms);
        };
    @endphp

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
                <th rowspan="3" class="th-top" style="width:14%;">CAMPO</th>
                <th rowspan="3" class="th-top" style="width:22%;">ÁREAS CURRICULARES</th>
                <th colspan="{{ $periodos->count() * 2 + 1 }}" class="th-top">VALORACIÓN CUANTITATIVA</th>
            </tr>
            <tr>
                @foreach($periodos as $p)
                    <th colspan="2">{{ $p->periodo_numero == 1 ? '1er.' : ($p->periodo_numero == 2 ? '2do.' : '3er') }}<br>TRIMESTRE</th>
                @endforeach
                <th rowspan="2" class="prom-anual">PROMEDIO</th>
            </tr>
            <tr>
                @foreach($periodos as $p)
                    <th style="font-size:5.5px;line-height:1.05;">Nota<br>Boletín<br>Interno</th>
                    <th style="font-size:5.5px;line-height:1.05;background:#fff8e1;">Nota<br>Ministerio<br>de Educación</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $gruposYaMostrados = [];
            @endphp
            @foreach($materiasPorCampo as $campo => $cmds)
                @php
                    // REORDENAR: promediables primero (preserva matc_orden entre ellas), luego el resto.
                    // Esto coloca la fila "PROMEDIO {GRUPO}" entre las promediables y el resto.
                    $cmds = collect($cmds)->sortBy(function($cmd) use ($esAgrupada) {
                        return $esAgrupada($cmd->mat_codigo) ? 0 : 1;
                    })->values();

                    // Filas extra: una por cada grupo promediable presente en este campo
                    $gruposEnCampo = [];
                    foreach($cmds as $cmd) {
                        if(isset($gruposMap[$cmd->mat_codigo]) && $esAgrupada($cmd->mat_codigo)) {
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
                            <td rowspan="{{ $materiaCount + $filasExtra }}" class="campo-cell">{{ mb_strtoupper($campo, 'UTF-8') }}</td>
                            @php $first = false; @endphp
                        @endif
                        <td class="materia-cell">{{ mb_strtoupper($cmd->materia->mat_nombre, 'UTF-8') }}</td>
                        @php $agrupada = $esAgrupada($cmd->mat_codigo); @endphp
                        @foreach($periodos as $p)
                            @php $val = $notasData[$cmd->mat_codigo]['trimestres'][$p->periodo_numero] ?? 0; @endphp
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}">{{ $val > 0 ? $val : '' }}</td>
                            <td class="{{ $val > 0 && $val < 51 ? 'nota-baja' : '' }}" style="background:{{ $agrupada ? '#fafafa' : '#fffdf0' }};">{{ $agrupada ? '' : ($val > 0 ? $val : '') }}</td>
                        @endforeach
                        <td class="prom-anual">{{ $notasData[$cmd->mat_codigo]['promedio'] > 0 ? $notasData[$cmd->mat_codigo]['promedio'] : '' }}</td>
                    </tr>
                    {{-- Fila PROMEDIO del grupo: emite tras la ÚLTIMA materia PROMEDIABLE del grupo --}}
                    @if(isset($gruposMap[$cmd->mat_codigo]) && $esAgrupada($cmd->mat_codigo))
                        @php
                            $grp = $gruposMap[$cmd->mat_codigo];
                            $matCodsProm = $grp->materiasPromediables->pluck('mat_codigo')->toArray();
                            $esUltimaProm = true;
                            $found = false;
                            foreach($cmds as $c2) {
                                if($found && in_array($c2->mat_codigo, $matCodsProm)) { $esUltimaProm = false; break; }
                                if($c2->mat_codigo === $cmd->mat_codigo) $found = true;
                            }
                        @endphp
                        @if($esUltimaProm && !in_array($grp->grupo_id, $gruposMostradosEnCampo))
                            @php $gruposMostradosEnCampo[] = $grp->grupo_id; @endphp
                            <tr style="background:#e8d5f5;">
                                <td style="text-align:left !important;padding-left:14px !important;font-weight:bold;font-size:6.5px;color:#6c3483;">
                                    {{ mb_strtoupper($grp->grupo_nombre, 'UTF-8') }}
                                </td>
                                @foreach($periodos as $p)
                                    @php
                                        $sumaG = 0; $cntG = 0;
                                        foreach($matCodsProm as $mc) {
                                            $v = $notasData[$mc]['trimestres'][$p->periodo_numero] ?? 0;
                                            // Solo cuenta materias con nota (>0); ignora vacías para no diluir el promedio.
                                            if ($v > 0) { $sumaG += $v; $cntG++; }
                                        }
                                        $promG = $cntG > 0 ? round($sumaG / $cntG, 0) : 0;
                                    @endphp
                                    <td style="font-weight:bold;color:#6c3483;">{{ $promG > 0 ? $promG : '' }}</td>
                                    <td style="font-weight:bold;color:#6c3483;background:#f4e6fa;">{{ $promG > 0 ? $promG : '' }}</td>
                                @endforeach
                                @php
                                    $sumaAnualG = 0; $cntAnualG = 0;
                                    foreach($periodos as $p2) {
                                        $sg = 0; $cg = 0;
                                        foreach($matCodsProm as $mc) {
                                            $v = $notasData[$mc]['trimestres'][$p2->periodo_numero] ?? 0;
                                            if ($v > 0) { $sg += $v; $cg++; }
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
                <th class="rotated">TOTAL<br>DÍAS HÁB.</th>
            @endforeach
        </tr>
        <tr>
            <td></td>
            @foreach($periodos as $p)
                @php
                    $a = $asistData[$p->periodo_numero] ?? ['ta'=>0,'tl'=>0,'tf'=>0,'dt'=>0,'pres'=>0,'total'=>0,'visible'=>true];
                    $visible = $a['visible'] ?? true;
                    // DÍAS TRABAJADOS = Asistencias + Licencias (atrasos cuentan como asistencia).
                    // TOTAL DÍAS HÁB. = días hábiles calendario − feriados (= DT + Faltas).
                    $diasTrab     = $visible ? ($a['pres'] + $a['tl']) : 0;
                    $totalDiasHab = $visible ? $a['total'] : 0;
                @endphp
                <td>{{ $visible ? $a['ta'] : '' }}</td>
                <td>{{ $visible ? $a['tl'] : '' }}</td>
                <td>{{ $visible ? $a['tf'] : '' }}</td>
                <td>{{ $visible ? $diasTrab : '' }}</td>
                <td style="font-weight:bold;">{{ $visible ? $totalDiasHab : '' }}</td>
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

    {{-- Bloque QR de verificación (esquina inferior derecha) --}}
    @if(!empty($qrData) || !empty($token))
    <table class="qr-footer-table">
        <tr>
            <td class="qr-footer-info">
                <div class="qr-footer-title">VALIDACIÓN DE AUTENTICIDAD</div>
                @if(!empty($token))
                    <div>Código de verificación: <strong>{{ strtoupper(substr($token, 0, 12)) }}</strong></div>
                @endif
                @if(!empty($qrUrl))
                    <div>Verificar en: {{ $qrUrl }}</div>
                @endif
                @if(!empty($numeroCopia))
                    <div class="qr-footer-copia">Copia N° {{ $numeroCopia }}{!! !empty($cobrable) ? ' · Reimpresión' : '' !!}</div>
                @endif
            </td>
            <td class="qr-footer-img">
                @if(!empty($qrData))
                    <img src="{{ $qrData }}" alt="QR validación">
                @endif
            </td>
        </tr>
    </table>
    @endif

    <div class="footer">
        Impreso: {{ now()->format('d/m/Y H:i:s') }} | Gestión {{ $gestion }} | Boletín Individual
    </div>
</body>
</html>
