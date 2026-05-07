<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Centralizador Anual</title>
<style>
body{font-family:Arial,sans-serif;font-size:9px;margin:18px;}
.header{text-align:center;margin-bottom:8px;}
.header h2{margin:2px 0;font-size:13px;}
.sub{font-size:10px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #444;padding:3px;text-align:center;}
th{background:#1c4789;color:#fff;font-size:9px;}
.nombre{text-align:left;}
.rep{color:#c0392b;font-weight:bold;}
.ret{background:#ffe6e6;}
</style></head><body>
<div class="header">
    @if($config && $config->config_logo)<img src="{{ public_path('storage/'.$config->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $config->config_denominacion ?? 'UNIDAD EDUCATIVA' }} {{ $config->config_nombre_ue ?? '' }}</h2>
    <div class="sub">CENTRALIZADOR ANUAL — {{ $curso->cur_nombre }} — Gestión {{ $gestion }}</div>
    <div class="sub">Fecha: {{ date('d/m/Y H:i') }}</div>
</div>
<table>
    <thead>
        <tr>
            <th rowspan="2" style="width:20px;">#</th>
            <th rowspan="2" class="nombre">Apellidos y Nombres</th>
            @foreach($materias as $m)
                <th colspan="{{ count($periodos) + 1 }}">{{ $m->mat_abreviatura ?: $m->mat_nombre }}</th>
            @endforeach
            <th rowspan="2">Prom. Anual</th>
        </tr>
        <tr>
            @foreach($materias as $m)
                @foreach($periodos as $p)<th>T{{ $p->periodo_numero }}</th>@endforeach
                <th>P</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($estudiantes as $e)
            @php
                $promsAnuales = [];
                foreach ($materias as $m) {
                    $vals = [];
                    foreach ($periodos as $p) {
                        $v = $matriz[$e->est_codigo][$m->mat_codigo][$p->periodo_id] ?? null;
                        if ($v !== null) $vals[] = $v;
                    }
                    if (count($vals)) $promsAnuales[$m->mat_codigo] = round(array_sum($vals)/count($vals));
                }
                $promFinal = count($promsAnuales) ? round(array_sum($promsAnuales)/count($promsAnuales)) : null;
            @endphp
            <tr class="{{ $e->est_visible == 0 ? 'ret' : '' }}">
                <td>{{ $e->lista_numero ?? '-' }}</td>
                <td class="nombre">{{ $e->est_apellidos }} {{ $e->est_nombres }} @if($e->est_visible==0)<b>(RETIRADO)</b>@endif</td>
                @foreach($materias as $m)
                    @foreach($periodos as $p)
                        @php $v = $matriz[$e->est_codigo][$m->mat_codigo][$p->periodo_id] ?? null; $vR = $v !== null ? round($v) : null; @endphp
                        <td class="{{ $vR !== null && $vR < 51 ? 'rep' : '' }}">{{ $vR ?? '-' }}</td>
                    @endforeach
                    <td class="{{ ($promsAnuales[$m->mat_codigo] ?? 100) < 51 ? 'rep' : '' }}"><b>{{ $promsAnuales[$m->mat_codigo] ?? '-' }}</b></td>
                @endforeach
                <td class="{{ $promFinal !== null && $promFinal < 51 ? 'rep' : '' }}"><b>{{ $promFinal ?? '-' }}</b></td>
            </tr>
        @endforeach
    </tbody>
</table>
</body></html>
