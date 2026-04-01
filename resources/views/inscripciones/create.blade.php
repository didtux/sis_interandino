@extends('layouts.app')

@section('content')
@php $mesActualPHP = (int)date('n'); $fueraDePlazo = $mesActualPHP > 2; @endphp
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-file-signature mr-2"></i>Nueva Inscripción</h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            @foreach($errors->all() as $error) {{ $error }}<br> @endforeach
                        </div>
                    @endif

                    <form action="{{ route('inscripciones.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <label>Estudiante <span class="text-danger">*</span></label>
                                <select name="est_codigo" id="selectEstudiante" class="form-control select2" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($estudiantes as $e)
                                        <option value="{{ $e->est_codigo }}">{{ $e->est_codigo }} - {{ $e->est_nombres }} {{ $e->est_apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5" id="divPadre">
                                <label>Padre/Tutor <span class="text-danger">*</span></label>
                                <select name="pfam_codigo" id="selectPadre" class="form-control select2" required>
                                    <option value="">Primero seleccione estudiante...</option>
                                </select>
                            </div>
                            <div class="col-md-5" id="divOtroPadre" style="display:none;">
                                <label>Nombre del Padre/Tutor <span class="text-danger">*</span></label>
                                <input type="text" name="pfam_nombre_nuevo" id="pfam_nombre_nuevo" class="form-control" placeholder="Ingrese nombre completo">
                            </div>
                            <div class="col-md-1 mt-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="checkOtroPadre">
                                    <label class="form-check-label" for="checkOtroPadre">Otro</label>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Curso <span class="text-danger">*</span></label>
                                <select name="cur_codigo" class="form-control select2" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Gestión <span class="text-danger">*</span></label>
                                <input type="text" name="insc_gestion" class="form-control" value="{{ date('Y') }}" required>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Monto Total Anual (10 meses) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="insc_monto_total" id="monto_total" class="form-control" required>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Descuento</label>
                                <select name="desc_id" id="desc_id" class="form-control select2">
                                    <option value="">Sin descuento</option>
                                    @foreach($descuentos as $d)
                                        <option value="{{ $d->desc_id }}" data-porcentaje="{{ $d->desc_porcentaje }}" data-nombre="{{ strtolower($d->desc_nombre) }}">{{ $d->desc_nombre }} ({{ $d->desc_porcentaje }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label>Tipo de Recibo</label>
                                <input type="text" id="tipo_recibo" class="form-control" readonly value="Con Factura (REC)">
                                <input type="hidden" name="insc_sin_factura" id="insc_sin_factura" value="0">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label>Monto Descuento</label>
                                <input type="number" step="0.01" name="insc_monto_descuento" id="monto_descuento" class="form-control" readonly value="0">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label>Monto Final <small class="text-muted" id="labelMontoFinal"></small></label>
                                <input type="number" step="0.01" name="insc_monto_final" id="monto_final" class="form-control" readonly value="0">
                            </div>
                        </div>

                        {{-- Fuera de plazo: forzado --}}
                        @if($fueraDePlazo)
                            <input type="hidden" name="insc_solo_registro" value="1">
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-warning py-2 mb-0">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <strong>Inscripción fuera de periodo (solo registro).</strong>
                                        Los Bs. 500 se registran como primera mensualidad. La mensualidad se mantiene fija, los meses anteriores al seleccionado no se cobran.
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Pago inicial --}}
                        <div class="row mt-3">
                            @if($fueraDePlazo)
                            <div class="col-md-3">
                                <label>Mes destino de la 1ra mensualidad <span class="text-danger">*</span></label>
                                <select name="insc_mes_destino" id="mes_destino" class="form-control" required>
                                    @for($m = 2; $m <= 11; $m++)
                                        <option value="{{ $m }}" {{ $m == $mesActualPHP ? 'selected' : ($m < $mesActualPHP ? 'disabled' : '') }}>
                                            {{ ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre'][$m] }}
                                            @if($m < $mesActualPHP) (Vencido) @endif
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Pago Inicial (1ra Mensualidad)</label>
                                <input type="number" step="0.01" name="insc_monto_pagado" id="monto_pagado" class="form-control" value="500" max="500">
                            </div>
                            @else
                            <div class="col-md-6">
                                <label>Pago Inicial de Inscripción</label>
                                <input type="number" step="0.01" name="insc_monto_pagado" id="monto_pagado" class="form-control" value="500" max="500">
                                <small class="text-muted">Pago de inscripción (Bs. 500)</small>
                            </div>
                            @endif
                        </div>

                        {{-- Panel resumen --}}
                        <div class="row mt-3" id="panelResumen" style="display:none;">
                            <div class="col-md-6">
                                <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:15px;">
                                    <div style="font-size:14px;font-weight:600;color:#495057;margin-bottom:10px;border-bottom:2px solid #007bff;padding-bottom:5px;">
                                        <i class="fas fa-calculator mr-1"></i> Resumen de Cuotas
                                    </div>
                                    <div id="resumenContenido"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:15px;">
                                    <div style="font-size:14px;font-weight:600;color:#495057;margin-bottom:10px;border-bottom:2px solid #28a745;padding-bottom:5px;">
                                        <i class="fas fa-calendar-alt mr-1"></i> Estado de Meses
                                    </div>
                                    <div id="mesesEstado"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label>Concepto</label>
                                <textarea name="insc_concepto" class="form-control" rows="2">Inscripción gestión {{ date('Y') }}</textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar Inscripción</button>
                            <a href="{{ route('inscripciones.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .mes-badge{display:inline-block;padding:3px 8px;border-radius:4px;font-size:11px;margin:2px;font-weight:600}
    .mes-pendiente{background:#fff3cd;color:#856404}
    .mes-vencido{background:#f8d7da;color:#721c24;text-decoration:line-through}
    .mes-febrero-desc{background:#d1ecf1;color:#0c5460}
    .mes-primera{background:#d4edda;color:#155724;border:1px solid #28a745}
    .info-row{display:flex;justify-content:space-between;padding:3px 0;font-size:13px}
    .info-row .label{color:#6c757d}
    .info-row .value{font-weight:600}
</style>
<script>
var mesActual = {{ $mesActualPHP }};
var mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre'];
var fueraDePlazo = {{ $fueraDePlazo ? 'true' : 'false' }};

$('.select2').select2({ theme: 'bootstrap4', width: '100%' });

$('#checkOtroPadre').on('change', function() {
    var esOtro = $(this).is(':checked');
    $('#divPadre').toggle(!esOtro);
    $('#divOtroPadre').toggle(esOtro);
    $('#selectPadre').prop('required', !esOtro).val('').trigger('change');
    $('#pfam_nombre_nuevo').prop('required', esOtro);
    if (!esOtro) $('#pfam_nombre_nuevo').val('');
});

$('#selectEstudiante').on('change', function() {
    var estCodigo = $(this).val();
    if (estCodigo) {
        $.ajax({
            url: '{{ url("/api/estudiantes") }}/' + estCodigo + '/padres',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
                data.forEach(function(p) {
                    $('#selectPadre').append('<option value="' + p.pfam_codigo + '">' + p.pfam_nombres + ' - CI: ' + (p.pfam_ci || 'N/A') + '</option>');
                });
                if (data.length > 0) $('#selectPadre').val(data[0].pfam_codigo).trigger('change');
            }
        });
    } else {
        $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>');
    }
});

function calcularMontos() {
    var montoTotal = parseFloat($('#monto_total').val()) || 0;
    var descId = $('#desc_id').val();
    var porcentaje = 0, sinFactura = false;

    if (descId) {
        porcentaje = parseFloat($('#desc_id option:selected').data('porcentaje')) || 0;
        var nombreDesc = $('#desc_id option:selected').data('nombre') || '';
        sinFactura = nombreDesc.includes('sin factura');
    }

    var montoDescuento = (montoTotal * porcentaje) / 100;
    var montoAnualConDesc = montoTotal - montoDescuento;
    var mensualidadBase = montoAnualConDesc / 10;
    var pagoInicial = parseFloat($('#monto_pagado').val()) || 0;

    $('#monto_descuento').val(montoDescuento.toFixed(2));
    $('#tipo_recibo').val(sinFactura ? 'Sin Factura (TAL)' : 'Con Factura (REC)');
    $('#insc_sin_factura').val(sinFactura ? '1' : '0');

    if (montoTotal <= 0) { $('#panelResumen').hide(); $('#monto_final').val('0'); return; }
    $('#panelResumen').show();

    var resumen = '', mesesHtml = '';

    if (fueraDePlazo) {
        // Mes destino seleccionado por el usuario
        var mesDestino = parseInt($('#mes_destino').val()) || mesActual;
        var mesesDisp = 11 - mesDestino + 1;
        var montoACobrar = mensualidadBase * mesesDisp;
        var saldoRestante = montoACobrar - pagoInicial;

        $('#monto_final').val(montoAnualConDesc.toFixed(2));
        $('#labelMontoFinal').text('(monto anual con descuento)');

        resumen += '<div class="info-row"><span class="label">Monto anual (con desc.):</span><span class="value">Bs. ' + montoAnualConDesc.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Mensualidad fija (anual/10):</span><span class="value">Bs. ' + mensualidadBase.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Meses disponibles:</span><span class="value">' + mesesDisp + ' (' + mesesNombres[mesDestino] + ' - Nov)</span></div>';
        resumen += '<div class="info-row"><span class="label">Monto a cobrar (' + mesesDisp + ' × ' + mensualidadBase.toFixed(0) + '):</span><span class="value text-primary">Bs. ' + montoACobrar.toFixed(2) + '</span></div>';
        resumen += '<hr style="margin:5px 0">';
        resumen += '<div class="info-row"><span class="label">Pago inicial (1ra mensualidad - ' + mesesNombres[mesDestino] + '):</span><span class="value text-success">Bs. ' + pagoInicial.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Saldo pendiente:</span><span class="value text-danger">Bs. ' + Math.max(0, saldoRestante).toFixed(2) + '</span></div>';

        for (var m = 2; m <= 11; m++) {
            if (m < mesDestino) {
                mesesHtml += '<span class="mes-badge mes-vencido">✗ ' + mesesNombres[m] + '</span>';
            } else if (m === mesDestino) {
                mesesHtml += '<span class="mes-badge mes-primera">★ ' + mesesNombres[m] + ' (Bs.' + pagoInicial.toFixed(0) + ')</span>';
            } else {
                mesesHtml += '<span class="mes-badge mes-pendiente">○ ' + mesesNombres[m] + ' (Bs.' + mensualidadBase.toFixed(0) + ')</span>';
            }
        }
        mesesHtml += '<div class="mt-1" style="font-size:11px;"><span class="mes-badge mes-primera">★ 1ra cuota</span> <span class="mes-badge mes-pendiente">○ Pendiente</span> <span class="mes-badge mes-vencido">✗ No aplica</span></div>';

    } else {
        // NORMAL (Ene/Feb)
        var montoFinal = montoAnualConDesc;
        var cuotaFeb = Math.max(0, mensualidadBase - 300);

        $('#monto_final').val(montoFinal.toFixed(2));
        $('#labelMontoFinal').text('(10 meses)');

        resumen += '<div class="info-row"><span class="label">Monto Final Anual:</span><span class="value">Bs. ' + montoFinal.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Pago inscripción:</span><span class="value">Bs. ' + pagoInicial.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Mensualidad (10 cuotas):</span><span class="value">Bs. ' + mensualidadBase.toFixed(2) + '</span></div>';
        resumen += '<div class="info-row"><span class="label">Cuota Feb (con desc. insc.):</span><span class="value text-info">Bs. ' + cuotaFeb.toFixed(2) + '</span></div>';

        for (var m = 2; m <= 11; m++) {
            if (m === 2) {
                mesesHtml += '<span class="mes-badge mes-febrero-desc">○ ' + mesesNombres[m] + ' (Bs.' + cuotaFeb.toFixed(0) + ')</span>';
            } else {
                mesesHtml += '<span class="mes-badge mes-pendiente">○ ' + mesesNombres[m] + ' (Bs.' + mensualidadBase.toFixed(0) + ')</span>';
            }
        }
        mesesHtml += '<div class="mt-1" style="font-size:11px;"><span class="mes-badge mes-febrero-desc">○ Con desc. inscripción</span> <span class="mes-badge mes-pendiente">○ Pendiente</span></div>';
    }

    $('#resumenContenido').html(resumen);
    $('#mesesEstado').html(mesesHtml);
}

$('#monto_total, #desc_id, #mes_destino').on('change', calcularMontos);
$('#monto_pagado').on('input change', function() {
    var v = parseFloat($(this).val()) || 0;
    if (v > 500) $(this).val(500);
    calcularMontos();
});
</script>
@endsection
