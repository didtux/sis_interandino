<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Caja diario {{ $fecha }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:24px;}
h2{margin:0 0 2px;}
.muted{color:#555;}
.cards{width:100%;border-collapse:collapse;margin:10px 0;}
.cards td{border:1px solid #444;padding:6px;text-align:center;}
.cards .lbl{color:#555;font-size:9px;}
.cards .val{font-size:14px;font-weight:bold;}
table.det{width:100%;border-collapse:collapse;margin-top:8px;}
table.det th,table.det td{border:1px solid #444;padding:4px;font-size:9px;}
table.det th{background:#1c4789;color:#fff;}
.r{text-align:right;} .c{text-align:center;}
</style></head><body>
<h2>REPORTE DE CAJA — MENSUALIDADES</h2>
<div class="muted">Fecha: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }} · Impreso: {{ now()->format('d/m/Y H:i') }}</div>

<table class="cards">
    <tr>
        <td><div class="lbl">TOTAL COBRADO</div><div class="val">Bs. {{ number_format($resumen['total'],2) }}</div></td>
        <td><div class="lbl">EFECTIVO</div><div class="val">Bs. {{ number_format($resumen['efectivo'],2) }}</div></td>
        <td><div class="lbl">QR</div><div class="val">Bs. {{ number_format($resumen['qr'],2) }}</div></td>
        <td><div class="lbl">MIXTO (incluye)</div><div class="val">Bs. {{ number_format($resumen['mixto'],2) }}</div></td>
        <td><div class="lbl">ESTUDIANTES COBRADOS</div><div class="val">{{ $resumen['estudiantes'] }}</div></td>
    </tr>
</table>

<table class="det">
    <thead>
        <tr>
            <th>#</th><th>Recibo</th><th>Estudiante</th><th>Curso</th><th>Concepto</th>
            <th class="r">Efectivo</th><th class="r">QR</th><th>Recibo/Factura</th><th>Tel/WhatsApp</th><th class="r">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($detalle as $i => $d)
            <tr>
                <td class="c">{{ $i+1 }}</td>
                <td>{{ $d->recibo }}</td>
                <td>{{ optional($d->estudiante)->est_apellidos }} {{ optional($d->estudiante)->est_nombres }}</td>
                <td>{{ $d->curso }}</td>
                <td>{{ $d->conceptos }}</td>
                <td class="r">{{ $d->efectivo > 0 ? number_format($d->efectivo,2) : '' }}</td>
                <td class="r">{{ $d->qr > 0 ? number_format($d->qr,2) : '' }}</td>
                <td>{{ $d->recibo_nro }}{{ $d->whatsapp && $d->transf ? ' W#'.$d->transf : '' }}</td>
                <td class="c">{{ $d->whatsapp ? 'WhatsApp' : '' }}</td>
                <td class="r">{{ number_format($d->total,2) }}</td>
            </tr>
        @endforeach
        <tr style="font-weight:bold;background:#eee;">
            <td colspan="5" class="r">TOTALES</td>
            <td class="r">{{ number_format($resumen['efectivo'],2) }}</td>
            <td class="r">{{ number_format($resumen['qr'],2) }}</td>
            <td colspan="2"></td>
            <td class="r">{{ number_format($resumen['total'],2) }}</td>
        </tr>
    </tbody>
</table>
</body></html>
