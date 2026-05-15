<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $titulo }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:22px;color:#222;}
.header-table{width:100%;border-collapse:collapse;margin-bottom:8px;}
.header-table td{border:1px solid #444;padding:6px;vertical-align:middle;}
.h-logo{width:62px;text-align:center;border-right:none !important;}
.h-info{border-left:none !important;text-align:center;}
.h-info .ue{font-weight:700;font-size:11px;line-height:1.2;}
.h-info .dir{font-size:9.5px;color:#444;}
.h-info .titulo{font-weight:700;font-size:15px;letter-spacing:1px;margin-top:4px;}
.h-info .sub{font-weight:700;font-size:12px;}
.h-meta{width:42%;padding:0 !important;}
.meta-tbl{width:100%;border-collapse:collapse;}
.meta-tbl td{border:1px solid #444;padding:4px 6px;text-align:center;font-size:10px;}
.meta-tbl .meta-trim{background:#1c4789;color:#fff;font-weight:700;letter-spacing:1px;}
.meta-tbl .meta-label{font-size:8.5px;font-weight:700;color:#444;background:#f0f0f0;}
.meta-tbl .meta-val{font-size:14px;font-weight:700;}
.meta-tbl .meta-foot{font-size:8px;color:#666;}

table.honor{width:100%;border-collapse:collapse;margin-top:6px;}
table.honor th,table.honor td{border:1px solid #444;padding:5px;}
table.honor th{background:#1c4789;color:#fff;text-align:center;}
td.pos{text-align:center;font-weight:bold;width:60px;}
td.num{text-align:right;width:80px;}
.top1{background:#fff3cd;}
.top2{background:#e7f1ff;}
.top3{background:#e6ffe6;}
</style></head><body>
@php
    $sc = $config ?? \App\Models\SistemaConfiguracion::actual();

    // Posición institucional (entre todos los cursos)
    $posCurso = null; $totalCursos = 0; $promedioCursoActual = null;
    // Posición dentro del nivel
    $posNivel = null; $totalNivel = 0;

    if ($tipo === 'curso' && !empty($rankingCursos) && $cursoActual) {
        $totalCursos = count($rankingCursos);
        foreach ($rankingCursos as $idx => $rc) {
            if ($rc->cur_codigo === $cursoCod) {
                $posCurso = $idx + 1;
                $promedioCursoActual = $rc->promedio_curso;
                break;
            }
        }
        // ranking solo del nivel
        $rankingNivel = array_values(array_filter($rankingCursos, fn($r) => $r->cur_nivel === $cursoActual->cur_nivel));
        $totalNivel = count($rankingNivel);
        foreach ($rankingNivel as $idx => $rc) {
            if ($rc->cur_codigo === $cursoCod) { $posNivel = $idx + 1; break; }
        }
    }
@endphp

<table class="header-table">
    <tr>
        <td class="h-logo">
            @if($sc && $sc->config_logo)
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" style="height:48px;">
            @endif
        </td>
        <td class="h-info">
            <div class="ue">{{ $sc->config_nombre_ue ?? 'U.E. PRIVADA INTERANDINO BOLIVIANO' }}</div>
            <div class="dir">{{ $sc->config_direccion ?? '' }} @if(!empty($sc->config_telefono)) — Teléfono {{ $sc->config_telefono }}@endif</div>
            <div class="titulo">CUADRO DE HONOR</div>
            <div class="sub">
                @if($cursoActual)
                    {{ mb_strtoupper($cursoActual->cur_nombre, 'UTF-8') }}
                    @if(!empty($cursoActual->cur_nivel)) DE {{ mb_strtoupper($cursoActual->cur_nivel, 'UTF-8') }}@endif
                @endif
            </div>
        </td>
        <td class="h-meta">
            <table class="meta-tbl">
                <tr>
                    <td colspan="3" class="meta-trim">{{ mb_strtoupper($trimestreNombre ?? '', 'UTF-8') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">NIVEL @if($cursoActual && $cursoActual->cur_nivel){{ mb_strtoupper($cursoActual->cur_nivel,'UTF-8') }}@else—@endif</td>
                    <td class="meta-label">UNIDAD<br>EDUCATIVA</td>
                    <td class="meta-label">PROMEDIO<br>DEL CURSO</td>
                </tr>
                <tr>
                    <td class="meta-val">{{ $posNivel ? $posNivel.'°' : '—' }}</td>
                    <td class="meta-val">{{ $posCurso ? $posCurso.'°' : '—' }}</td>
                    <td class="meta-val">{{ $promedioCursoActual !== null ? number_format($promedioCursoActual, 1, '.', ',') : '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-foot">{{ $totalNivel ? 'de '.$totalNivel.' cursos' : 'LUGAR' }}</td>
                    <td class="meta-foot">{{ $totalCursos ? 'de '.$totalCursos.' cursos' : 'LUGAR' }}</td>
                    <td class="meta-foot">/100</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="honor">
    <thead>
        <tr>
            <th style="width:70px;">POSICIÓN</th>
            <th>NOMBRE DE ALUMNO</th>
            @if($tipo !== 'curso')<th style="width:140px;">CURSO</th>@endif
            <th style="width:90px;">SUMA</th>
            <th style="width:90px;">PROMEDIO</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $r)
            @php $cls = $i==0?'top1':($i==1?'top2':($i==2?'top3':'')); @endphp
            <tr class="{{ $cls }}">
                <td class="pos">{{ $i + 1 }}°</td>
                <td>{{ mb_strtoupper($r->nombre, 'UTF-8') }}</td>
                @if($tipo !== 'curso')<td>{{ $r->cur_nombre }}</td>@endif
                <td class="num">{{ number_format($r->suma, 0, '.', ',') }}</td>
                <td class="num">{{ number_format($r->promedio, 1, '.', ',') }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $tipo !== 'curso' ? 5 : 4 }}" style="text-align:center;">Sin registros</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:10px;font-size:9px;color:#666;text-align:right;">
    Gestión {{ $gestion }} — Generado: {{ date('d/m/Y H:i') }}
</div>
</body></html>
