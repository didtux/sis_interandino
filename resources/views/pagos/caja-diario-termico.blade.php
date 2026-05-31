<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Caja {{ $fecha }}</title>
<style>
body{font-family:'Courier New',monospace;font-size:10px;margin:6px;width:212px;}
.c{text-align:center;} .r{text-align:right;}
hr{border:none;border-top:1px dashed #000;margin:4px 0;}
table{width:100%;border-collapse:collapse;}
td{font-size:9px;padding:1px 0;}
.b{font-weight:bold;}
</style></head><body>
<div class="c b">CIERRE DE CAJA</div>
<div class="c">Mensualidades</div>
<div class="c">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</div>
<hr>
<table>
    <tr><td>Total cobrado:</td><td class="r b">Bs. {{ number_format($resumen['total'],2) }}</td></tr>
    <tr><td>(-) QR:</td><td class="r">Bs. {{ number_format($resumen['qr'],2) }}</td></tr>
    <tr><td>Efectivo en caja:</td><td class="r b">Bs. {{ number_format($resumen['efectivo'],2) }}</td></tr>
    <tr><td>Estudiantes cobrados:</td><td class="r">{{ $resumen['estudiantes'] }}</td></tr>
</table>
<hr>
<div class="b">DETALLE</div>
<table>
    @foreach($detalle as $d)
        <tr><td colspan="2">{{ $d->recibo }} · {{ optional($d->estudiante)->est_apellidos }} {{ optional($d->estudiante)->est_nombres }}</td></tr>
        <tr><td>{{ $d->metodo }}</td><td class="r">Bs. {{ number_format($d->total,2) }}</td></tr>
    @endforeach
</table>
<hr>
<div class="c">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</body></html>
