<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Boletín {{ $estudiante->est_codigo }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:22px;}
.header{text-align:center;margin-bottom:10px;}
.header h2{margin:2px 0;}
.datos{display:flex;justify-content:space-between;margin:8px 0;border:1px solid #888;padding:6px;}
.foto{width:80px;height:90px;object-fit:cover;border:1px solid #888;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #444;padding:5px;}
th{background:#1c4789;color:#fff;}
.num{text-align:center;width:60px;}
.rep{color:#c0392b;font-weight:bold;}
.aprob{color:#2c8c2c;font-weight:bold;}
</style></head><body>
<div class="header">
    @if($config && $config->config_logo)<img src="{{ public_path('storage/'.$config->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $config->config_denominacion ?? 'UNIDAD EDUCATIVA' }} {{ $config->config_nombre_ue ?? '' }}</h2>
    <h3>BOLETÍN INDIVIDUAL — Gestión {{ $gestion }}</h3>
</div>
<div class="datos">
    <div>
        <b>Estudiante:</b> {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}<br>
        <b>Código:</b> {{ $estudiante->est_codigo }} &nbsp; <b>CI:</b> {{ $estudiante->est_ci ?? '-' }}<br>
        <b>Curso:</b> {{ optional($estudiante->curso)->cur_nombre ?? '-' }}<br>
        <b>U.E. de procedencia:</b> {{ $estudiante->est_ueprocedencia ?? '-' }}
    </div>
    @if($estudiante->est_foto)
        <img src="{{ public_path('storage/'.$estudiante->est_foto) }}" class="foto">
    @endif
</div>
<table>
    <thead>
        <tr>
            <th>Materia</th>
            @foreach($periodos as $p)<th class="num">T{{ $p->periodo_numero }}</th>@endforeach
            <th class="num">Final</th>
            <th class="num">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($matriz as $matCod => $m)
            @php
                $vals = []; foreach ($periodos as $p) { if (isset($m['per'][$p->periodo_id])) $vals[] = $m['per'][$p->periodo_id]; }
                $final = count($vals) ? round(array_sum($vals)/count($vals)) : null;
                $aprob = $final !== null && $final >= 51;
            @endphp
            <tr>
                <td>{{ $m['nombre'] }}</td>
                @foreach($periodos as $p)
                    @php $v = $m['per'][$p->periodo_id] ?? null; @endphp
                    <td class="num {{ $v !== null && $v < 51 ? 'rep' : '' }}">{{ $v !== null ? round($v) : '-' }}</td>
                @endforeach
                <td class="num {{ $final !== null && $final < 51 ? 'rep' : '' }}"><b>{{ $final ?? '-' }}</b></td>
                <td class="num {{ $aprob ? 'aprob' : 'rep' }}">{{ $final === null ? '-' : ($aprob ? 'APROBADO' : 'REPROBADO') }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ count($periodos) + 3 }}" style="text-align:center;">Sin notas registradas</td></tr>
        @endforelse
    </tbody>
</table>
</body></html>
