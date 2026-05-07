<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $titulo }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:25px;}
.header{text-align:center;margin-bottom:14px;}
.header h2{margin:4px 0;}
.curso-block{margin-top:12px;page-break-inside:avoid;}
.curso-title{background:#1c4789;color:#fff;padding:6px 10px;font-weight:bold;font-size:12px;}
.curso-title .nivel{float:right;font-weight:normal;font-size:10.5px;opacity:.85;}
table{width:100%;border-collapse:collapse;margin-top:0;}
th,td{border:1px solid #444;padding:5px;}
th{background:#34495e;color:#fff;font-size:10.5px;}
td.pos{text-align:center;font-weight:bold;width:60px;}
td.num{text-align:right;width:80px;}
.top1{background:#fff3cd;}
.top2{background:#e7f1ff;}
.top3{background:#e6ffe6;}
.empty{font-style:italic;color:#888;text-align:center;padding:6px;}
</style></head><body>
@php $sc = $config ?? \App\Models\SistemaConfiguracion::actual(); @endphp
<div class="header">
    @if($sc && $sc->config_logo)<img src="{{ public_path('storage/'.$sc->config_logo) }}" style="height:48px;">@endif
    <h2>{{ $sc->config_nombre_ue ?? 'INTERANDINO BOLIVIANO' }}</h2>
    <div>{{ $sc->config_direccion ?? '' }} — Tel: {{ $sc->config_telefono ?? '' }}</div>
    <h3>{{ $titulo }}</h3>
    <div>Gestión {{ $gestion }} — {{ date('d/m/Y') }}</div>
    <div style="margin-top:6px;font-size:10px;color:#555;">Top 3 mejores estudiantes de cada curso, ordenados según el ranking institucional de cursos.</div>
</div>

@forelse($porCurso as $curCodigo => $bloque)
    <div class="curso-block">
        <div class="curso-title">
            {{ $bloque['cur_nombre'] }}
            <span class="nivel">{{ $bloque['cur_nivel'] ?? '' }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">Pos.</th>
                    <th>Estudiante</th>
                    <th style="width:80px;">Suma</th>
                    <th style="width:90px;">Promedio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bloque['rows'] as $i => $r)
                    @php $cls = $i==0?'top1':($i==1?'top2':'top3'); @endphp
                    <tr class="{{ $cls }}">
                        <td class="pos">{{ $i + 1 }}°</td>
                        <td>{{ $r->nombre }}</td>
                        <td class="num">{{ number_format($r->suma, 2, '.', ',') }}</td>
                        <td class="num">{{ number_format($r->promedio, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Sin notas registradas en este curso.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@empty
    <div class="empty">Sin datos de notas en la institución para esta gestión/trimestre.</div>
@endforelse
</body></html>
