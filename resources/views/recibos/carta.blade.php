<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Recibo {{ $r->codigo }}</title>
<style>
    @page { margin: 18px 26px; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #111; }
    .cab { display: table; width: 100%; border-bottom: 2px solid #1c4789; padding-bottom: 4px; }
    .cab > div { display: table-cell; vertical-align: middle; }
    .logo img { height: 44px; }
    .ue { padding-left: 8px; }
    .ue h3 { margin: 0; font-size: 12px; }
    .ue p { margin: 1px 0; font-size: 8px; color: #444; }
    .num { text-align: right; width: 34%; }
    .num .tit { font-size: 11px; font-weight: bold; color: #1c4789; }
    .num .cod { font-size: 15px; font-weight: bold; }
    .egreso .num .tit, .egreso .num .cod { color: #a12a2a; }
    table.datos { width: 100%; margin-top: 6px; }
    table.datos td { padding: 2px 0; font-size: 10px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th { background: #1c4789; color: #fff; font-size: 9px; padding: 3px 5px; text-align: left; }
    table.items td { border-bottom: 1px solid #ddd; padding: 3px 5px; }
    .r { text-align: right; }
    .total td { font-weight: bold; font-size: 12px; border-bottom: none; }
    .literal { margin-top: 4px; font-style: italic; }
    .nota { margin-top: 5px; font-size: 8.5px; color: #333; border: 1px dashed #999; padding: 4px; }
    .firmas { display: table; width: 100%; margin-top: 26px; }
    .firmas div { display: table-cell; width: 50%; text-align: center; font-size: 9px; }
    .firmas span { border-top: 1px solid #000; padding: 2px 30px 0; }
    .anulado { position: absolute; top: 120px; left: 150px; font-size: 64px; color: rgba(200,0,0,0.25); transform: rotate(-20deg); font-weight: bold; }
</style></head>
<body class="{{ $r->egreso ? 'egreso' : '' }}">
@if($r->anulado)<div class="anulado">ANULADO</div>@endif
<div class="cab">
    <div class="logo">
        @if($config && $config->config_logo && file_exists(public_path('storage/' . $config->config_logo)))
            <img src="{{ public_path('storage/' . $config->config_logo) }}">
        @endif
    </div>
    <div class="ue">
        <h3>{{ $config->config_denominacion ?? 'UNIDAD EDUCATIVA PRIVADA' }} {{ $config->config_nombre_ue ?? 'INTERANDINO BOLIVIANO' }}</h3>
        <p>{{ $config->config_direccion ?? '' }}</p>
        <p>{{ $config->config_telefono ? 'Tel. ' . $config->config_telefono : '' }}</p>
    </div>
    <div class="num">
        <div class="tit">{{ $r->titulo }}</div>
        <div class="cod">N° {{ $r->codigo }}</div>
        <div>{{ $r->fecha ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y H:i') : '' }}</div>
    </div>
</div>

<table class="datos">
    <tr><td style="width:18%;"><strong>{{ $r->egreso ? 'Devuelto a:' : 'Recibí de:' }}</strong></td><td>{{ $r->padre ?: '—' }}</td></tr>
    <tr><td><strong>Estudiante:</strong></td><td>{{ $r->estudiante ?: '—' }} @if($r->curso) <span style="color:#555;">({{ $r->curso }})</span>@endif</td></tr>
</table>

<table class="items">
    <thead><tr><th>Concepto</th><th class="r" style="width:20%;">Monto (Bs)</th></tr></thead>
    <tbody>
        @foreach($r->lineas as [$concepto, $monto])
            <tr><td>{{ $concepto }}</td><td class="r">{{ number_format($monto, 2) }}</td></tr>
        @endforeach
        <tr class="total"><td class="r">{{ $r->egreso ? 'TOTAL DEVUELTO' : 'TOTAL' }}</td><td class="r">{{ number_format($r->total, 2) }}</td></tr>
    </tbody>
</table>
<div class="literal">Son: {{ $r->literal }}</div>
@if($r->metodo)
    <div style="margin-top:2px;">Forma de pago: {{ $r->metodo }}
        @if($r->metodo === 'MIXTO') (efectivo Bs {{ number_format($r->efectivo, 2) }} · QR Bs {{ number_format($r->qr, 2) }})@endif
    </div>
@endif
@if($r->nota)<div class="nota">{{ $r->nota }}</div>@endif
@if($r->anulado)<div class="nota" style="color:#a12a2a;">RECIBO ANULADO{{ $r->anulado_motivo ? ': ' . $r->anulado_motivo : '' }}</div>@endif

<div class="firmas">
    <div><span>{{ $r->egreso ? 'RECIBÍ CONFORME' : 'ENTREGUÉ CONFORME' }}</span></div>
    <div><span>{{ $r->egreso ? 'ENTREGUÉ CONFORME' : 'RECIBÍ CONFORME' }}</span><br><small>{{ $r->usuario }}</small></div>
</div>
</body></html>
