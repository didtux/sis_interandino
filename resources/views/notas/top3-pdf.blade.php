<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Top 3 por Curso</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:22px;}
.header{text-align:center;margin-bottom:14px;}
.header h2{margin:4px 0;}
.curso{margin-top:12px;border:1px solid #1c4789;}
.curso .titulo{background:#1c4789;color:#fff;padding:6px 10px;font-weight:bold;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #444;padding:5px;}
th{background:#f0f0f0;}
.pos{text-align:center;width:60px;font-weight:bold;}
.num{text-align:right;width:90px;}
</style></head><body>
<div class="header">
    @if($config && $config->config_logo)<img src="{{ public_path('storage/'.$config->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $config->config_nombre_ue ?? 'INTERANDINO BOLIVIANO' }}</h2>
    <h3>MEJORES 3 ESTUDIANTES POR CURSO — Gestión {{ $gestion }}</h3>
</div>

@foreach($porCurso as $cod => $g)
    <div class="curso">
        <div class="titulo">{{ $g['nombre'] }}</div>
        <table>
            <thead><tr><th>Posición</th><th>Estudiante</th><th>Suma</th><th>Promedio</th></tr></thead>
            <tbody>
                @foreach($g['rows'] as $i => $r)
                    <tr><td class="pos">{{ $i + 1 }}°</td><td>{{ $r->nombre }}</td><td class="num">{{ number_format($r->suma,1,'.',',') }}</td><td class="num">{{ number_format($r->promedio,1,'.',',') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body></html>
