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
.qr-card img{width:90px;height:90px;}
.qr-card .ph{width:90px;height:90px;border:1px dashed #999;line-height:90px;display:inline-block;font-size:8px;}
.qr-card .name{font-weight:bold;font-size:8.5px;margin-top:3px;line-height:1.15;}
.qr-card .code{color:#555;font-size:8px;}
</style></head><body>
<h2>CÓDIGOS QR — {{ $curso->cur_nombre }}</h2>
<div class="sub">{{ $curso->cur_codigo }} — {{ date('d/m/Y H:i') }} — {{ $estudiantes->count() }} estudiantes</div>

@php
    $cols = 3;
    $filas = $estudiantes->chunk($cols);
    $qrOpts = new \chillerlan\QRCode\QROptions([
        'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_M,
        'scale'      => 3,
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
