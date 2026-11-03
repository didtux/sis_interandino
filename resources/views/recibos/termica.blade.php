<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Recibo {{ $r->codigo }}</title>
<style>
    @page { margin: 6px; }
    body { font-family: 'Courier New', monospace; font-size: 9px; width: 212px; color: #000; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { font-size: 8.5px; padding: 1px 0; vertical-align: top; }
</style></head>
<body>
<div class="c b">{{ $config->config_nombre_ue ?? 'INTERANDINO BOLIVIANO' }}</div>
<div class="c">{{ $config->config_telefono ? 'Tel. ' . $config->config_telefono : '' }}</div>
<hr>
<div class="c b">{{ $r->titulo }}</div>
<div class="c b">N° {{ $r->codigo }}</div>
<div class="c">{{ $r->fecha ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y H:i') : '' }}</div>
@if($r->anulado)<div class="c b">*** ANULADO ***</div>@endif
<hr>
<div>{{ $r->egreso ? 'Devuelto a' : 'Recibí de' }}: {{ $r->padre ?: '—' }}</div>
<div>Estudiante: {{ $r->estudiante ?: '—' }}</div>
@if($r->curso)<div>Curso: {{ $r->curso }}</div>@endif
<hr>
<table>
    @foreach($r->lineas as [$concepto, $monto])
        <tr><td>{{ $concepto }}</td><td class="r">{{ number_format($monto, 2) }}</td></tr>
    @endforeach
</table>
<hr>
<table><tr><td class="b">{{ $r->egreso ? 'TOTAL DEVUELTO' : 'TOTAL' }} Bs</td><td class="r b">{{ number_format($r->total, 2) }}</td></tr></table>
<div>Son: {{ $r->literal }}</div>
@if($r->metodo)
    <div>Pago: {{ $r->metodo }}@if($r->metodo === 'MIXTO') (Ef. {{ number_format($r->efectivo, 2) }} / QR {{ number_format($r->qr, 2) }})@endif</div>
@endif
@if($r->nota)<hr><div style="font-size:7.5px;">{{ $r->nota }}</div>@endif
<hr>
<div class="c">Cajero: {{ $r->usuario }}</div>
<div class="c">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</body></html>
