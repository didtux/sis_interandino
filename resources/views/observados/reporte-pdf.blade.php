<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Estudiantes Observados {{ $gestion }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;margin:18px;}
h2{text-align:center;margin-bottom:6px;}
.sub{text-align:center;color:#666;margin-bottom:14px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #000;padding:5px 7px;font-size:10px;}
th{background:#000;color:#fff;text-align:left;}
.tipo-tag{display:inline-block;background:#2c3e50;color:#fff;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:bold;}
.foot{margin-top:18px;font-size:8px;text-align:right;color:#666;}
</style></head>
<body>
<h2>LISTA DE ESTUDIANTES OBSERVADOS — Inscripciones {{ $gestion }}</h2>
<div class="sub">U.E. Privada Interandino Boliviano · Generado: {{ now()->format('d/m/Y H:i') }}</div>

<table>
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th>Estudiante</th>
            <th style="width:90px;">Curso</th>
            <th style="width:80px;">Tipo</th>
            <th>Motivo</th>
            <th style="width:90px;">Registrado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($observados as $i => $o)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ mb_strtoupper(($o->estudiante->est_apellidos ?? '').' '.($o->estudiante->est_nombres ?? ''), 'UTF-8') }}</strong></td>
                <td>{{ optional($o->estudiante->curso)->cur_nombre ?? '-' }}</td>
                <td><span class="tipo-tag">{{ $o->obs_motivo_tipo }}</span></td>
                <td>{{ $o->obs_motivo }}</td>
                <td>{{ $o->obs_fecha_registro ? $o->obs_fecha_registro->format('d/m/Y') : '-' }}<br><small>{{ $o->obs_registrado_por_nombre }}</small></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#888;">Sin estudiantes observados activos.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="foot">Total: {{ $observados->count() }} estudiante(s) bloqueados para inscripción.</div>
</body></html>
