<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Transporte mensual</title>
<style>
body{font-family:Arial,sans-serif;font-size:9px;margin:18px;}
h2{margin:0 0 2px;text-align:center;}
.muted{color:#555;text-align:center;margin-bottom:6px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #555;padding:1px 2px;text-align:center;font-size:7px;}
th{background:#1c4789;color:#fff;}
.name{text-align:left;font-size:8px;white-space:nowrap;}
.i{color:#1c7c1c;font-weight:bold;}
.v{color:#b8530b;font-weight:bold;}
.firma{margin-top:30px;width:100%;}
.firma td{border:none;text-align:center;font-size:10px;padding-top:18px;}
.obs{margin-top:10px;font-size:9px;}
.obs th,.obs td{border:1px solid #555;padding:3px;text-align:left;font-size:8px;}
</style></head><body>
<h2>CONTROL DE ASISTENCIA — TRANSPORTE ESCOLAR</h2>
<div class="muted">
    Ruta/Bus: <strong>{{ $rutaNombre }}</strong> · Conductor: <strong>{{ $chofer->chof_nombres ?? '' }} {{ $chofer->chof_apellidos ?? '' }}</strong>
    · Mes: <strong>{{ $mesNombre }} {{ $anio }}</strong>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" class="name">N°</th>
            <th rowspan="2" class="name">ESTUDIANTE</th>
            <th rowspan="2" class="name">CURSO</th>
            @foreach($dias as $d)<th colspan="2">{{ $d }}</th>@endforeach
        </tr>
        <tr>
            @foreach($dias as $d)<th>I</th><th>V</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($estudiantes as $i => $e)
            <tr>
                <td>{{ $i+1 }}</td>
                <td class="name">{{ $e->est_apellidos }} {{ $e->est_nombres }}</td>
                <td class="name">{{ optional($e->curso)->cur_nombre }}</td>
                @foreach($dias as $d)
                    @php $m = $matriz[$e->est_codigo][$d] ?? []; @endphp
                    <td class="i">{{ !empty($m['ida']) ? '✓' : '' }}</td>
                    <td class="v">{{ !empty($m['vuelta']) ? '✓' : '' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ 3 + (count($dias)*2) }}">Sin estudiantes en la ruta.</td></tr>
        @endforelse
    </tbody>
</table>
<div style="font-size:8px;margin-top:3px;"><span class="i">I</span> = Ida · <span class="v">V</span> = Vuelta · ✓ = registrado</div>

@if(count($observaciones))
<table class="obs">
    <thead><tr><th style="width:60px;">Fecha</th><th style="width:60px;">Tipo</th><th>Observación del conductor</th></tr></thead>
    <tbody>
        @foreach($observaciones as $o)
            <tr><td>{{ $o['fecha'] }}</td><td>{{ $o['tipo'] }}</td><td>{{ $o['obs'] }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="firma">
    <tr>
        <td>_______________________________<br>V° B° CAJA</td>
        <td>_______________________________<br>{{ strtoupper(($chofer->chof_nombres ?? '').' '.($chofer->chof_apellidos ?? '')) }}<br>CONDUCTOR</td>
    </tr>
</table>
</body></html>
