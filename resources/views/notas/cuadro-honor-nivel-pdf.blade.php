<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $titulo }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:22px;color:#000;}
.header-table{width:100%;border-collapse:collapse;margin-bottom:10px;}
.header-table td{border:1px solid #000;padding:6px;vertical-align:middle;}
.h-logo{width:62px;text-align:center;border-right:none !important;}
.h-info{border-left:none !important;text-align:left;padding-left:12px !important;}
.h-info .ue{font-weight:700;font-size:11px;line-height:1.2;}
.h-info .dir{font-size:9.5px;color:#333;}
.h-meta{width:30%;}
.titulo-banda{text-align:center;font-size:17px;font-weight:700;letter-spacing:2px;padding:7px 0;border:1px solid #000;margin-bottom:12px;}

.nivel-banner{
    background:#000;color:#fff;text-align:center;
    padding:6px 0;letter-spacing:1.5px;font-size:13px;font-weight:700;
    margin-top:10px;
}
.nivel-banner:first-child{margin-top:0;}

table.honor{width:100%;border-collapse:collapse;margin-bottom:6px;}
table.honor th, table.honor td{border:1px solid #000;padding:5px 8px;}
table.honor th{background:#f0f0f0;text-align:center;font-weight:700;font-size:11px;}
table.honor td{font-size:11px;}
.pos { text-align:center; width:80px; font-weight:700; }
.curso { text-align:left; font-weight:600; }
.promedio { text-align:center; width:110px; font-weight:700; }
.top1 td { background:#fff7cc; }
.top2 td { background:#e6f0ff; }
.top3 td { background:#e6ffe6; }

.foot{margin-top:10px;font-size:9px;color:#666;text-align:right;}
</style></head>
<body>
@php $sc = $config ?? null; @endphp
<table class="header-table">
    <tr>
        <td class="h-logo">
            @if($sc && !empty($sc->config_logo) && file_exists(public_path('storage/'.$sc->config_logo)))
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" style="height:48px;">
            @elseif(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" style="height:48px;">
            @endif
        </td>
        <td class="h-info">
            <div class="ue">{{ $sc->config_nombre_ue ?? 'U.E. PRIVADA INTERANDINO BOLIVIANO' }}</div>
            <div class="dir">{{ $sc->config_direccion ?? 'Dir. Calle Víctor Gutiérrez N° 3339' }}</div>
            <div class="dir">Teléfono {{ $sc->config_telefono ?? '2840320' }}</div>
        </td>
        <td class="h-meta" style="text-align:center;">
            <div style="font-size:9px;color:#666;letter-spacing:0.5px;">PERÍODO</div>
            <div style="font-size:14px;font-weight:700;margin-top:2px;">{{ mb_strtoupper($trimestreNombre, 'UTF-8') }}</div>
            <div style="font-size:9px;color:#666;margin-top:2px;">Gestión {{ $gestion }}</div>
        </td>
    </tr>
</table>

<div class="titulo-banda">CUADRO DE HONOR POR NIVEL</div>

@forelse($porNivel as $nivel => $cursos)
    <div class="nivel-banner">NIVEL {{ mb_strtoupper($nivel, 'UTF-8') }}</div>
    <table class="honor">
        <thead>
            <tr>
                <th style="width:80px;">POSICIÓN</th>
                <th>CURSO</th>
                <th style="width:110px;">PROMEDIO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cursos as $i => $r)
                @php $cls = $i==0?'top1':($i==1?'top2':($i==2?'top3':'')); @endphp
                <tr class="{{ $cls }}">
                    <td class="pos">{{ $i + 1 }}°</td>
                    <td class="curso">{{ mb_strtoupper($r->cur_nombre, 'UTF-8') }}</td>
                    <td class="promedio">{{ number_format($r->promedio_curso, 1, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <p style="text-align:center;color:#888;">Sin datos para mostrar.</p>
@endforelse

<div class="foot">Generado: {{ date('d/m/Y H:i') }}</div>
</body></html>
