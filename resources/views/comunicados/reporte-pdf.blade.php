<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Comunicado {{ $comunicado->com_id }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:24px;}
h2{margin:0 0 4px;} .muted{color:#555;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #444;padding:5px;font-size:10px;}
th{background:#1c4789;color:#fff;}
.b{font-weight:bold;}
.s-success{color:#2c8c2c;font-weight:bold;} .s-warning{color:#b8860b;font-weight:bold;}
.s-danger{color:#c0392b;font-weight:bold;} .s-secondary{color:#555;}
</style></head><body>
<h2>REPORTE DE COMUNICADO / DOCUMENTACIÓN</h2>
<div class="muted">
    <strong>{{ $comunicado->com_titulo }}</strong><br>
    {{ $comunicado->com_descripcion }}<br>
    Destinatarios: {{ $comunicado->com_para_todos ? 'Todos los docentes' : 'Seleccionados' }} ·
    Fecha límite: {{ $comunicado->com_fecha_limite ? $comunicado->com_fecha_limite->format('d/m/Y') : 'sin fecha' }} ·
    Creado: {{ $comunicado->com_fecha->format('d/m/Y') }} por {{ $comunicado->com_creado_por_nombre }}
    @if(!$comunicado->com_estado) · <span class="s-danger">ANULADO: {{ $comunicado->com_motivo_anulacion }}</span>@endif
</div>

@php
    $cnt = ['EN FECHA'=>0,'FUERA DE FECHA'=>0,'NO ENTREGÓ'=>0,'PENDIENTE'=>0];
    foreach ($filas as $f) { $cnt[$f->estado_entrega] = ($cnt[$f->estado_entrega] ?? 0) + 1; }
@endphp
<p style="margin-top:8px;">
    <span class="s-success">En fecha: {{ $cnt['EN FECHA'] }}</span> ·
    <span class="s-warning">Fuera de fecha: {{ $cnt['FUERA DE FECHA'] }}</span> ·
    <span class="s-danger">No entregó: {{ $cnt['NO ENTREGÓ'] }}</span> ·
    <span class="s-secondary">Pendiente: {{ $cnt['PENDIENTE'] }}</span>
</p>

<table>
    <thead><tr><th>#</th><th>Docente</th><th>Estado</th><th>Fecha de entrega</th><th>Observación</th></tr></thead>
    <tbody>
        @foreach($filas as $i => $f)
            @php $cls = ['EN FECHA'=>'s-success','FUERA DE FECHA'=>'s-warning','NO ENTREGÓ'=>'s-danger','PENDIENTE'=>'s-secondary'][$f->estado_entrega] ?? 's-secondary'; @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($f->docente)->doc_apellidos }} {{ optional($f->docente)->doc_nombres }}</td>
                <td class="{{ $cls }}">{{ $f->estado_entrega }}</td>
                <td>{{ $f->cd_fecha_entrega ? $f->cd_fecha_entrega->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $f->cd_observacion }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div style="margin-top:14px;font-size:9px;color:#777;">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</body></html>
