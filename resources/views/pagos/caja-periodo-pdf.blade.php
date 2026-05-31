<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Caja período</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:24px;}
h2{margin:0 0 2px;}
.muted{color:#555;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #444;padding:5px;font-size:10px;}
th{background:#1c4789;color:#fff;}
.r{text-align:right;} .c{text-align:center;}
</style></head><body>
<h2>REPORTE DE CAJA POR PERÍODO — MENSUALIDADES</h2>
<div class="muted">
    Del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
    · Impreso: {{ now()->format('d/m/Y H:i') }}
</div>

<table>
    <thead>
        <tr><th>Fecha</th><th class="r">Efectivo</th><th class="r">QR</th><th class="c">#Estud.</th><th>Recibos (de–hasta)</th><th class="r">Total</th></tr>
    </thead>
    <tbody>
        @foreach($porDia as $d)
            <tr>
                <td>{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                <td class="r">{{ number_format($d->efectivo,2) }}</td>
                <td class="r">{{ number_format($d->qr,2) }}</td>
                <td class="c">{{ $d->estudiantes }}</td>
                <td>{{ $d->recibo_min }} – {{ $d->recibo_max }}</td>
                <td class="r">{{ number_format($d->total,2) }}</td>
            </tr>
        @endforeach
        <tr style="font-weight:bold;background:#eee;">
            <td class="r">TOTAL</td>
            <td class="r">{{ number_format($resumen['efectivo'],2) }}</td>
            <td class="r">{{ number_format($resumen['qr'],2) }}</td>
            <td class="c">{{ $resumen['estudiantes'] }}</td>
            <td></td>
            <td class="r">{{ number_format($resumen['total'],2) }}</td>
        </tr>
    </tbody>
</table>
</body></html>
