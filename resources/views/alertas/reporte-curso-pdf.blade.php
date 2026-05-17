<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Advertencia — {{ $curso->cur_nombre }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:9px;margin:14px;}
.head{display:table;width:100%;margin-bottom:8px;}
.head .l{display:table-cell;width:60px;}
.head .l img{width:48px;}
.head .r{display:table-cell;vertical-align:middle;text-align:left;padding-left:8px;}
.titulo-banda{text-align:center;font-size:14px;font-weight:700;letter-spacing:2px;padding:6px 0;border:1px solid #000;margin-bottom:10px;}
.curso-tag{display:inline-block;background:#000;color:#fff;padding:2px 10px;font-weight:700;margin-bottom:6px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #000;padding:3px 5px;font-size:9px;}
th{background:#e8e8e8;}
.nombre{text-align:left;font-weight:600;}
.tag-doc{background:#f39c12;color:#fff;padding:1px 4px;border-radius:2px;font-size:8px;font-weight:bold;margin-right:2px;}
.tag-dir{background:#e91e63;color:#fff;padding:1px 4px;border-radius:2px;font-size:8px;font-weight:bold;margin-right:2px;}
.felicit{color:#27ae60;font-weight:bold;font-style:italic;}
.foot{margin-top:10px;font-size:7.5px;color:#666;text-align:right;}
.legend{font-size:8px;margin-bottom:6px;}
.legend span{padding:1px 5px;border-radius:2px;color:#fff;font-weight:bold;margin-right:6px;}
</style></head>
<body>
@php $sc = \App\Models\SistemaConfiguracion::actual(); @endphp
<div class="head">
    <div class="l">
        @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
            <img src="{{ public_path('storage/'.$sc->config_logo) }}">
        @elseif(file_exists(public_path('img/logo.png')))
            <img src="{{ public_path('img/logo.png') }}">
        @endif
    </div>
    <div class="r">
        <strong>U.E. PRIVADA INTERANDINO BOLIVIANO</strong><br>
        <small>{{ $sc->config_direccion ?? 'Dir. Calle Víctor Gutiérrez N° 3339' }} · Tel: {{ $sc->config_telefono ?? '2840320' }}</small>
    </div>
</div>

<div class="titulo-banda">ADVERTENCIA — PARCIAL {{ mb_strtoupper($periodo->periodo_nombre ?? '', 'UTF-8') }}</div>

<div class="curso-tag">{{ mb_strtoupper($curso->cur_nombre, 'UTF-8') }}</div>

<div class="legend">
    <span style="background:#f39c12;">NARANJA = Docente</span>
    <span style="background:#e91e63;">ROSA = Dirección (oficial al padre)</span>
    <span class="felicit" style="background:#27ae60;">FELICITACIONES = Sin observación</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:30px;">N°</th>
            <th>Estudiante</th>
            <th>Materias con observación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($estudiantes as $i => $est)
            @php $mats = $porEst[$est->est_codigo] ?? []; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="nombre">{{ mb_strtoupper($est->est_apellidos.' '.$est->est_nombres, 'UTF-8') }}</td>
                <td>
                    @if(empty($mats))
                        <span class="felicit">FELICITACIONES</span>
                    @else
                        @foreach($mats as $matCod => $info)
                            @if($info['director'])
                                <span class="tag-dir">{{ $info['nombre'] ?? $matCod }}</span>
                            @elseif($info['docente'])
                                <span class="tag-doc">{{ $info['nombre'] ?? $matCod }}</span>
                            @endif
                        @endforeach
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="foot">Generado: {{ now()->format('d/m/Y H:i') }} · Gestión {{ $gestion }}</div>
</body></html>
