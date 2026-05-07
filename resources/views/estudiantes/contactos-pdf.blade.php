<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Contactos - {{ $curso->cur_nombre }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:20px;}
h2{margin:0 0 8px 0;text-align:center;}
.sub{text-align:center;margin-bottom:14px;font-size:12px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #444;padding:5px 6px;}
th{background:#1c4789;color:#fff;font-size:11px;}
tr:nth-child(even) td{background:#f5f7fa;}
</style></head><body>
<h2>LISTA DE CONTACTOS</h2>
<div class="sub">{{ $curso->cur_nombre }} ({{ $curso->cur_codigo }}) — {{ date('d/m/Y H:i') }}</div>
<table>
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th>Estudiante</th>
            <th>Padre / Tutor</th>
            <th>Parentesco</th>
            <th style="width:110px;">Teléfono</th>
        </tr>
    </thead>
    <tbody>
        @php $i=0; $last=null; @endphp
        @foreach($rows as $r)
            @if($last !== $r->est_codigo) @php $i++; $last = $r->est_codigo; @endphp @endif
            <tr>
                <td>{{ $i }}</td>
                <td>{{ $r->est_apellidos }} {{ $r->est_nombres }}</td>
                <td>{{ $r->pfam_nombres ?: '-' }}</td>
                <td>{{ $r->pfam_parentesco ?: '-' }}</td>
                <td>{{ $r->pfam_telefono ?: '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body></html>
