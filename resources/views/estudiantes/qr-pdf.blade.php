<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>QR - {{ $curso->cur_nombre }}</title>
<style>
@page { margin: 12mm; }
body{font-family:Arial,sans-serif;font-size:9px;margin:0;}
h2{margin:0 0 4px 0;text-align:center;font-size:13px;}
.sub{text-align:center;margin-bottom:8px;font-size:10px;color:#555;}
table.grid{width:100%;border-collapse:separate;border-spacing:4px;table-layout:fixed;}
table.grid td{
    width:33.33%;
    border:1px solid #444;
    padding:4px 3px;
    text-align:center;
    vertical-align:top;
    page-break-inside:avoid;
}
/* Sin width/height: el PNG se imprime a su tamano nativo. Forzarlo a 90px
   sobre un bitmap de otro tamano hacia un reescalado no entero y los
   modulos del QR quedaban con bordes irregulares (lectura fallida). */
.qr-card img{display:inline-block;}
.qr-card .ph{width:145px;height:145px;border:1px dashed #999;line-height:145px;display:inline-block;font-size:8px;}
.qr-card .name{font-weight:bold;font-size:8.5px;margin-top:3px;line-height:1.15;}
.qr-card .code{color:#555;font-size:8px;}
</style></head><body>
<h2>CÓDIGOS QR — {{ $curso->cur_nombre }}</h2>
<div class="sub">{{ $curso->cur_codigo }} — {{ date('d/m/Y H:i') }} — {{ $estudiantes->count() }} estudiantes</div>

@php
    $cols = 3;
    $filas = $estudiantes->chunk($cols);
    // scale 3 daba un bitmap de ~87px que ademas se reescalaba a 90px en el PDF:
    // modulos de ancho irregular y QR que no leen. Con scale 5 el modulo mide
    // 5px exactos (29 modulos = 145px ~ 38mm impresos, contra los ~24mm de
    // antes) y la hoja sigue entrando en pocas paginas. ECC_Q (25%) aguanta el
    // desgaste de una hoja plastificada o manoseada; con ECC_H el codigo
    // saltaria a version 2 y creceria sin necesidad.
    $qrOpts = new \chillerlan\QRCode\QROptions([
        'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_Q,
        'scale'      => 5,
        'imageBase64'=> true,
    ]);
    $qrInstance = new \chillerlan\QRCode\QRCode($qrOpts);
@endphp

<table class="grid">
@foreach($filas as $fila)
    <tr>
        @foreach($fila as $e)
            <td class="qr-card">
                @php
                    $qrSrc = null;
                    try { $qrSrc = $qrInstance->render($e->est_codigo); } catch (\Throwable $ex) { $qrSrc = null; }
                @endphp
                @if($qrSrc)
                    <img src="{{ $qrSrc }}">
                @else
                    <div class="ph">{{ $e->est_codigo }}</div>
                @endif
                <div class="name">{{ mb_strtoupper($e->est_apellidos.' '.$e->est_nombres, 'UTF-8') }}</div>
                <div class="code">{{ $e->est_codigo }}</div>
            </td>
        @endforeach
        @for($i = $fila->count(); $i < $cols; $i++)
            <td style="border:none;"></td>
        @endfor
    </tr>
@endforeach
</table>
</body></html>
