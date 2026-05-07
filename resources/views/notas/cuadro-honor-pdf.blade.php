<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $titulo }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:25px;}
.header{text-align:center;margin-bottom:14px;}
.header h2{margin:4px 0;}
table{width:100%;border-collapse:collapse;margin-top:8px;}
th,td{border:1px solid #444;padding:6px;}
th{background:#1c4789;color:#fff;}
td.pos{text-align:center;font-weight:bold;width:60px;}
td.num{text-align:right;width:80px;}
.top1{background:#fff3cd;}
.top2{background:#e7f1ff;}
.top3{background:#e6ffe6;}
.section-title{margin-top:16px;background:#2c3e50;color:#fff;padding:6px 8px;font-weight:bold;font-size:12px;}
.this-curso{background:#fff3cd!important;font-weight:700;}
</style></head><body>
@php
    $sc = $config ?? \App\Models\SistemaConfiguracion::actual();
    $posCurso = null; $totalCursos = 0; $promedioCursoActual = null;
    if ($tipo === 'curso' && !empty($rankingCursos)) {
        $totalCursos = count($rankingCursos);
        foreach ($rankingCursos as $idx => $rc) {
            if ($rc->cur_codigo === $cursoCod) {
                $posCurso = $idx + 1;
                $promedioCursoActual = $rc->promedio_curso;
                break;
            }
        }
    }
@endphp
<div class="header">
    @if($sc && $sc->config_logo)<img src="{{ public_path('storage/'.$sc->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $sc->config_nombre_ue ?? 'INTERANDINO BOLIVIANO' }}</h2>
    <div>{{ $sc->config_direccion ?? '' }} — Tel: {{ $sc->config_telefono ?? '' }}</div>
    <h3>{{ $titulo }}</h3>
    <div>Gestión {{ $gestion }} — {{ date('d/m/Y') }}</div>
    @if($posCurso)
        <div style="margin-top:6px;display:inline-block;background:#1c4789;color:#fff;padding:4px 10px;border-radius:4px;font-weight:bold;font-size:12px;">
            POSICIÓN INSTITUCIONAL: {{ $posCurso }}° de {{ $totalCursos }}
            @if($promedioCursoActual !== null)
                <span style="opacity:.85;font-weight:normal;font-size:10.5px;margin-left:6px;">(promedio del curso: {{ number_format($promedioCursoActual, 2, '.', ',') }})</span>
            @endif
        </div>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>Posición</th>
            <th>Estudiante</th>
            @if($tipo !== 'curso')<th>Curso</th>@endif
            <th>Suma</th>
            <th>Promedio</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $r)
            @php $cls = $i==0?'top1':($i==1?'top2':($i==2?'top3':'')); @endphp
            <tr class="{{ $cls }}">
                <td class="pos">{{ $i + 1 }}°</td>
                <td>{{ $r->nombre }}</td>
                @if($tipo !== 'curso')<td>{{ $r->cur_nombre }}</td>@endif
                <td class="num">{{ number_format($r->suma, 2, '.', ',') }}</td>
                <td class="num">{{ number_format($r->promedio, 2, '.', ',') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;">Sin registros</td></tr>
        @endforelse
    </tbody>
</table>

</body></html>
