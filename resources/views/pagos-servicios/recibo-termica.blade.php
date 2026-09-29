<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo - {{ $pago->pserv_codigo }}</title>
    {{--
        Comprobante para impresora térmica de 80 mm (226.77 pt de ancho de papel).
        El recibo normal de servicios es media carta y no sirve para la térmica
        de caja; sigue el mismo patrón que ventas/recibo-pdf.blade.php.
    --}}
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 10px; width: 200px; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 8px; margin-bottom: 8px; font-size: 8px; line-height: 1.3; }
        .header h1 { font-size: 11px; margin: 2px 0; }
        .info-row { margin: 3px 0; }
        .detalle { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 8px 0; margin: 8px 0; }
        .total { text-align: right; font-size: 12px; font-weight: bold; margin-top: 6px; }
        .firma { margin-top: 28px; text-align: center; font-size: 8px; }
        .firma .l { border-top: 1px solid #000; display: inline-block; min-width: 150px; padding-top: 2px; }
        .pie { text-align: center; font-size: 8px; margin-top: 10px; border-top: 2px dashed #000; padding-top: 6px; }
        .anulado { text-align: center; color: #000; font-weight: bold; border: 2px solid #000; padding: 3px; margin: 6px 0; }
    </style>
</head>
<body>
    @php $sc = \App\Models\SistemaConfiguracion::actual(); @endphp

    <div class="header">
        <h1>{{ mb_strtoupper($sc->config_nombre_ue ?? 'UNIDAD EDUCATIVA INTERANDINO BOLIVIANO', 'UTF-8') }}</h1>
        <div>{{ $sc->config_direccion ?? '' }}</div>
        @if(!empty($sc->config_telefono))<div>Tel: {{ $sc->config_telefono }}</div>@endif
        <div style="margin-top:4px;font-weight:bold;">RECIBO DE SERVICIO</div>
    </div>

    @if($pago->pserv_estado == 0)
        <div class="anulado">** ANULADO **</div>
    @endif

    <div class="info-row"><strong>Recibo:</strong> {{ $pago->pserv_codigo }}</div>
    <div class="info-row"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($pago->pserv_fecha)->format('d/m/Y H:i') }}</div>
    <div class="info-row"><strong>Estudiante:</strong><br>{{ $pago->estudiante->est_apellidos ?? '' }} {{ $pago->estudiante->est_nombres ?? 'N/A' }}</div>
    <div class="info-row"><strong>Curso:</strong> {{ $pago->estudiante->curso->cur_nombre ?? 'N/A' }}</div>
    @if($pago->padreFamilia)
        <div class="info-row"><strong>Entregado por:</strong> {{ $pago->padreFamilia->pfam_nombres }}</div>
    @endif

    <div class="detalle">
        <div><strong>{{ $pago->servicio->serv_nombre ?? 'Servicio' }}</strong></div>
        @if($pago->pserv_observacion)
            <div style="font-size:9px;">{{ $pago->pserv_observacion }}</div>
        @endif
        <div class="info-row">Monto: Bs. {{ number_format($pago->pserv_monto, 2) }}</div>
        @if($pago->pserv_descuento > 0)
            <div class="info-row">Descuento: -Bs. {{ number_format($pago->pserv_descuento, 2) }}</div>
        @endif
    </div>

    <div class="total">TOTAL Bs. {{ number_format($pago->pserv_total, 2) }}</div>

    <div class="firma"><span class="l">Recibí conforme</span></div>

    <div class="pie">
        Impreso: {{ now()->format('d/m/Y H:i') }}<br>
        Conserve este comprobante
    </div>
</body>
</html>
