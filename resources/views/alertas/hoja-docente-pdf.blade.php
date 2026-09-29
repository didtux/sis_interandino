<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Reporte de Estudiantes — Advertencia Parcial</title>
<style>
    body { font-family:Arial,Helvetica,sans-serif; font-size:10px; margin:24px 28px; color:#000; }
    .head { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:6px; margin-bottom:6px; }
    .head .l { display:table-cell; width:60px; vertical-align:middle; }
    .head .l img { width:52px; }
    .head .r { display:table-cell; vertical-align:middle; text-align:center; }
    .head .r h3 { margin:0; font-size:13px; }
    .head .r p { margin:1px 0; font-size:8px; }
    .titulo { text-align:center; font-weight:bold; font-size:14px; margin:8px 0 2px; }
    .codigo { float:right; border:1px solid #000; padding:6px 14px; font-size:18px; font-weight:bold; }
    .meta { margin:6px 0; font-size:11px; }
    .nota-ind { font-size:9px; margin:6px 0 10px; }
    table.sec { width:100%; border-collapse:collapse; margin-bottom:8px; }
    table.sec td { border:1px solid #000; height:22px; text-align:center; font-size:10px; }
    td.lbl { width:90px; font-weight:bold; background:#dbe5f1; font-size:9px; line-height:1.1; }
    /* Naranja = el docente reporto a ese estudiante en la advertencia parcial,
       mismo color que usa la matriz en pantalla y el PDF de lote. */
    td.marc { background:#f6a623; color:#000; font-weight:bold; }

    .cierre { width:100%; margin-top:45px; border-collapse:collapse; }
    .cierre td { width:33.33%; text-align:center; vertical-align:bottom; padding:0 10px; border:none; }
    .cierre .linea { border-top:1px solid #000; margin-top:34px; }
    .cierre .cap { font-size:9px; padding-top:3px; }
    .cierre .recuadro-sello { border:1px dashed #999; color:#aaa; font-size:9px;
        height:70px; line-height:70px; }
</style>
</head>
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
        <h3>UNIDAD EDUCATIVA PRIVADA “INTERANDINO BOLIVIANO”</h3>
        <p>RESOLUCIÓN ADMINISTRATIVA No. 311/2006</p>
        <p>{{ $sc->config_direccion ?? 'Calle V. Gutiérrez No. 3339 Zona “16 de Julio”' }} · Telf. {{ $sc->config_telefono ?? '2840320' }} · El Alto, La Paz - Bolivia</p>
    </div>
</div>

<div class="titulo">REPORTE DE ESTUDIANTES<br>(ADVERTENCIA PARCIAL)</div>

<div class="meta">
    <strong>MAESTRA (O):</strong> {{ mb_strtoupper(trim(($docente->doc_nombres ?? '').' '.($docente->doc_apellidos ?? '')) ?: '________________________', 'UTF-8') }}<br>
    <strong>ÁREA:</strong> {{ mb_strtoupper($areas ?: '________________________', 'UTF-8') }}
    @if($periodo) &nbsp;·&nbsp; <strong>PERIODO:</strong> {{ mb_strtoupper($periodo->periodo_nombre, 'UTF-8') }} @endif
</div>

<div class="nota-ind">
    Señor(a) maestro(a) REGISTRE CUIDADOSAMENTE LOS ESTUDIANTES CON OBSERVACIONES Y DIFICULTADES,
    CONSIDERANDO LA NOTA GLOBAL DE LAS CUATRO DIMENSIONES (<strong>SER, SABER, HACER Y DECIDIR</strong>).
    Marque el número del estudiante, el cual debe ser el mismo que se consigna en las listas oficiales internas.
</div>

@php $maxCols = 20; @endphp
@forelse($filas as $fila)
    @php
        $cant = max(1, (int) $fila['cantidad']);
        $chunks = array_chunk(range(1, $cant), $maxCols);
    @endphp
    <table class="sec">
        @foreach($chunks as $ci => $chunk)
            <tr>
                @if($ci === 0)
                    <td class="lbl" rowspan="{{ count($chunks) }}">{{ mb_strtoupper($fila['curso'], 'UTF-8') }}</td>
                @endif
                @foreach($chunk as $n)<td class="{{ in_array($n, $fila['marcados'] ?? []) ? 'marc' : '' }}">{{ $n }}</td>@endforeach
                @for($k = count($chunk); $k < $maxCols; $k++)<td></td>@endfor
            </tr>
        @endforeach
    </table>
@empty
    <p style="text-align:center;color:#777;">El docente no tiene cursos asignados.</p>
@endforelse

{{-- Firma y sello separados: antes compartian una sola linea y no habia
     lugar para estampar el sello. --}}
<table class="cierre">
    <tr>
        <td><div class="linea"></div><div class="cap">FIRMA MAESTRA(O)</div></td>
        <td><div class="recuadro-sello">SELLO</div></td>
        <td><div class="linea"></div><div class="cap">RECIBIDO — DIRECCIÓN</div></td>
    </tr>
</table>
</body>
</html>
