<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Kardex Estudiante</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;margin:18px;}
h2{margin:0 0 4px;}
.sub{color:#666;margin-bottom:14px;}
.section-title{background:#000;color:#fff;padding:5px 8px;font-weight:bold;font-size:11px;}
table{width:100%;border-collapse:collapse;margin-top:6px;}
th,td{border:1px solid #000;padding:3px 5px;font-size:9px;}
th{background:#e8e8e8;}
.tag{display:inline-block;padding:1px 5px;background:#34495e;color:#fff;border-radius:2px;font-size:8px;font-weight:bold;}
</style></head>
<body>
<h2>KARDEX DE ESTUDIANTE@if($estudiante) — {{ mb_strtoupper($estudiante->est_apellido_paterno.' '.$estudiante->est_nombres, 'UTF-8') }}@endif</h2>
<div class="sub">
    @if($curso)Curso: {{ $curso->cur_nombre }} · @endif
    Generado: {{ now()->format('d/m/Y H:i') }}
</div>

<div class="section-title">ANOTACIONES ({{ $registros->count() }})</div>
<table>
    <thead>
        <tr>
            <th style="width:70px;">Fecha</th>
            @if(!$estudiante)<th>Estudiante</th>@endif
            <th style="width:80px;">Tipo</th>
            <th style="width:70px;">Categoría</th>
            <th>Título / Descripción</th>
            <th>Acuerdo</th>
            <th style="width:90px;">Docente</th>
        </tr>
    </thead>
    <tbody>
        @forelse($registros as $r)
            <tr>
                <td>{{ $r->ek_fecha->format('d/m/Y') }}</td>
                @if(!$estudiante)<td>{{ optional($r->estudiante)->est_apellido_paterno }} {{ optional($r->estudiante)->est_nombres }}</td>@endif
                <td><span class="tag">{{ $r->ek_tipo }}</span></td>
                <td>{{ $r->ek_categoria ?? '—' }}</td>
                <td><strong>{{ $r->ek_titulo }}</strong>@if($r->ek_descripcion)<br><span style="color:#555;">{{ $r->ek_descripcion }}</span>@endif</td>
                <td>{{ $r->ek_acuerdo }}</td>
                <td>{{ optional($r->docente)->doc_apellidos }} {{ optional($r->docente)->doc_nombres }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#888;">Sin anotaciones.</td></tr>
        @endforelse
    </tbody>
</table>
</body></html>
