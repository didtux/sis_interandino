@extends('layouts.app')

@section('content')
<style>
    .info-panel { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    .info-panel .panel-title { font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 10px; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; }
    .info-panel .info-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; }
    .info-panel .info-row .label { color: #6c757d; }
    .info-panel .info-row .value { font-weight: 600; }
    .mes-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin: 2px; font-weight: 600; }
    .mes-pagado { background: #d4edda; color: #155724; }
    .mes-pendiente { background: #fff3cd; color: #856404; }
    .mes-mora { background: #f8d7da; color: #721c24; }
    .mes-suspendido { background: #e2e3e5; color: #6c757d; text-decoration: line-through; }
    .mes-mora-badge { background: #e74c3c; color: #fff; font-size: 9px; padding: 1px 5px; border-radius: 3px; margin-left: 3px; }
    .mes-susp-badge { background: #6c757d; color: #fff; font-size: 9px; padding: 1px 5px; border-radius: 3px; margin-left: 3px; }
    .historial-table th { background: #e9ecef; font-size: 11px; text-transform: uppercase; }
    .student-detail-panel { display: none; border-left: 3px solid #17a2b8; background: #fff; padding: 15px; margin: 10px 0; border-radius: 0 8px 8px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-bus mr-2"></i>Nuevo Pago de Transporte</h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            @foreach($errors->all() as $error) {{ $error }}<br> @endforeach
                        </div>
                    @endif

                    <form action="{{ route('pagos-transporte.store') }}" method="POST" id="formPago">
                        @csrf

                        {{-- PASO 1: Seleccionar padre --}}
                        <div class="row">
                            <div class="col-md-5" id="divPadreSelect">
                                <div class="form-group">
                                    <label>Padre de Familia <span class="text-danger">*</span></label>
                                    <select id="padre-select" class="form-control select2">
                                        <option value="">Seleccione un padre...</option>
                                        @foreach($padres as $padre)
                                            <option value="{{ $padre->pfam_codigo }}">
                                                {{ $padre->pfam_nombres }} - CI: {{ $padre->pfam_ci ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5" id="divOtroPadre" style="display:none;">
                                <div class="form-group">
                                    <label>Nombre del Padre/Tutor <span class="text-danger">*</span></label>
                                    <input type="text" name="pfam_nombre_nuevo" id="pfam_nombre_nuevo" class="form-control" placeholder="Ingrese nombre completo">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="checkOtroPadre">
                                        <label class="form-check-label" for="checkOtroPadre">Otro</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha de Pago *</label>
                                    <input type="date" name="tpago_fecha_pago" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Selector de estudiantes para "Otro" --}}
                        <div id="divAgregarEstudiante" style="display:none;">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Agregar Estudiante</label>
                                        <select id="est-select-otro" class="form-control select2-est">
                                            <option value="">Buscar estudiante...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-success btn-block" id="btnAgregarEst">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- PASO 2: Tabla de estudiantes --}}
                        <div id="hijos-container" style="display:none;">
                            <hr>
                            <h5><i class="fas fa-users mr-2"></i>Estudiantes</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-hijos">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width:40px"><input type="checkbox" id="checkAll"></th>
                                            <th>Estudiante</th>
                                            <th>Curso</th>
                                            <th>Ruta</th>
                                            <th>Meses a Pagar</th>
                                            <th>Monto Mensual</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hijos-tbody"></tbody>
                                </table>
                            </div>

                            <div id="paneles-detalle"></div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-success">
                                        <div class="row">
                                            <div class="col-md-8"><div id="detalle-pago"></div></div>
                                            <div class="col-md-4 text-right">
                                                <h4>TOTAL: Bs. <span id="total-pagar">0.00</span></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg" id="btnGuardar" disabled>
                                    <i class="fas fa-save"></i> Registrar Pago
                                </button>
                                <a href="{{ route('pagos-transporte.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var estudiantesData = @json($estudiantesData);
var mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];
var mesActual = {{ (int)date('n') }}; // 1-12
var idxGlobal = 0;
var estudiantesAgregados = [];

// Obtener meses pagables (no pagados, no suspendidos)
function getMesesPagables(est) {
    var pagables = [];
    for (var m = 2; m <= 11; m++) {
        if (est.meses_pagados.includes(m)) continue;
        // Si está suspendido, no incluir meses desde la suspensión
        if (est.suspendido && est.suspendido_desde && m >= est.suspendido_desde) continue;
        pagables.push(m);
    }
    return pagables;
}

function esMora(est, m) {
    if (est.meses_pagados.includes(m)) return false;
    // Si está suspendido desde antes de este mes, no es mora
    if (est.suspendido && est.suspendido_desde && m >= est.suspendido_desde) return false;
    return m < mesActual;
}

function esSuspendido(est, m) {
    return est.suspendido && est.suspendido_desde && m >= est.suspendido_desde && !est.meses_pagados.includes(m);
}

$(document).ready(function() {
    $('#padre-select').select2({ placeholder: 'Buscar padre de familia...', allowClear: true, width: '100%', theme: 'bootstrap4' });

    $('#checkOtroPadre').on('change', function() {
        var esOtro = $(this).is(':checked');
        $('#divPadreSelect').toggle(!esOtro);
        $('#divOtroPadre').toggle(esOtro);
        $('#divAgregarEstudiante').toggle(esOtro);
        if (esOtro) {
            $('#padre-select').val('').trigger('change');
            limpiarTabla();
            cargarSelectEstudiantes();
            $('#hijos-container').show();
        } else {
            $('#pfam_nombre_nuevo').val('');
            limpiarTabla();
            $('#hijos-container').hide();
        }
    });

    $('#padre-select').on('change', function() {
        limpiarTabla();
        var pfamCodigo = $(this).val();
        if (pfamCodigo) {
            cargarHijosDePadre(pfamCodigo);
        } else {
            $('#hijos-container').hide();
        }
    });

    $('#btnAgregarEst').on('click', function() {
        var estCodigo = $('#est-select-otro').val();
        if (!estCodigo) return;
        if (estudiantesAgregados.includes(estCodigo)) { alert('Ya fue agregado'); return; }
        var est = estudiantesData[estCodigo];
        if (!est) return;
        agregarFilaEstudiante(est);
        $('#est-select-otro').val('').trigger('change');
    });

    $('#checkAll').on('change', function() {
        $('.check-estudiante').prop('checked', $(this).is(':checked')).trigger('change');
    });
});

function limpiarTabla() {
    $('#hijos-tbody').empty();
    $('#paneles-detalle').empty();
    idxGlobal = 0;
    estudiantesAgregados = [];
    recalcular();
}

function cargarSelectEstudiantes() {
    var sel = $('#est-select-otro');
    sel.empty().append('<option value="">Buscar estudiante...</option>');
    for (var key in estudiantesData) {
        var est = estudiantesData[key];
        sel.append('<option value="' + est.est_codigo + '">' + est.nombre + ' - ' + est.curso + ' - ' + est.ruta + '</option>');
    }
    if (sel.hasClass('select2-hidden-accessible')) sel.select2('destroy');
    sel.select2({ placeholder: 'Buscar estudiante...', allowClear: true, width: '100%', theme: 'bootstrap4' });
}

function cargarHijosDePadre(pfamCodigo) {
    var hijos = [];
    for (var key in estudiantesData) {
        var est = estudiantesData[key];
        if (est.padres && est.padres.includes(pfamCodigo)) hijos.push(est);
    }
    if (hijos.length === 0) {
        $('#hijos-tbody').append('<tr><td colspan="7" class="text-center text-muted">Este padre no tiene estudiantes con transporte</td></tr>');
        $('#hijos-container').show();
        return;
    }
    hijos.forEach(function(est) { agregarFilaEstudiante(est); });
    $('#hijos-container').show();
}

function agregarFilaEstudiante(est) {
    var idx = idxGlobal++;
    estudiantesAgregados.push(est.est_codigo);

    var mesesPagables = getMesesPagables(est);
    var maxCuotas = mesesPagables.length;

    var esOtro = $('#checkOtroPadre').is(':checked');

    var row = '<tr data-est="' + est.est_codigo + '" data-idx="' + idx + '">' +
        '<td><input type="checkbox" class="check-estudiante" data-idx="' + idx + '" data-est="' + est.est_codigo + '"></td>' +
        '<td><strong>' + est.nombre + '</strong>' +
            '<input type="hidden" name="estudiantes[' + idx + '][est_codigo]" value="' + est.est_codigo + '" disabled>' +
            '<input type="hidden" name="estudiantes[' + idx + '][ultima_vigencia]" value="' + (est.ultima_vigencia || '') + '" disabled>' +
        '</td>' +
        '<td>' + est.curso + '</td>' +
        '<td><small>' + est.ruta + (est.ruta === 'Sin asignar' ? ' <span class="badge badge-warning">Pendiente</span>' : '') + '</small></td>' +
        '<td><select name="estudiantes[' + idx + '][meses_pagar]" class="form-control form-control-sm meses-select" data-idx="' + idx + '" disabled>';

    for (var i = 1; i <= 10; i++) {
        var dis = i > maxCuotas ? 'disabled' : '';
        var label = i + (i === 1 ? ' mes' : ' meses');
        row += '<option value="' + i + '" ' + dis + '>' + label + '</option>';
    }

    var mesesVencidos = 0;
    for (var mv = 2; mv < mesActual; mv++) {
        if (!est.meses_pagados.includes(mv)) mesesVencidos++;
    }
    var infoDisp = maxCuotas + ' disponible' + (maxCuotas !== 1 ? 's' : '');
    if (mesesVencidos > 0) infoDisp += ' <span class="text-danger">(' + mesesVencidos + ' en mora)</span>';
    row += '</select><small class="text-muted">' + infoDisp + '</small></td>' +
        '<td><input type="number" name="estudiantes[' + idx + '][monto_mensual]" class="form-control form-control-sm monto-mensual" data-idx="' + idx + '" step="0.01" min="0" value="0" disabled></td>' +
        '<td class="subtotal-cell"><strong>Bs. 0.00</strong></td>' +
        '</tr>';

    $('#hijos-tbody').append(row);
    $('#paneles-detalle').append(crearPanelDetalle(est, idx));

    var $row = $('#hijos-tbody tr:last');
    $row.find('.check-estudiante').on('change', function() {
        var tr = $(this).closest('tr');
        var checked = $(this).is(':checked');
        var estCode = $(this).data('est');
        tr.find('select, input[type="number"], input[type="hidden"]').prop('disabled', !checked);
        if (!checked) tr.find('.subtotal-cell strong').text('Bs. 0.00');
        $('#panel-' + estCode).slideToggle(200, function() {
            $(this).css('display', checked ? 'block' : 'none');
        });
        recalcular();
    });
    $row.find('.meses-select, .monto-mensual').on('change input', function() { recalcular(); });

    if (esOtro) {
        $row.find('.check-estudiante').prop('checked', true).trigger('change');
    }
}

function crearPanelDetalle(est, idx) {
    var mesesHtml = '';
    for (var m = 2; m <= 11; m++) {
        var clase, icono;
        if (est.meses_pagados.includes(m)) {
            clase = 'mes-pagado'; icono = '✓';
        } else if (esSuspendido(est, m)) {
            clase = 'mes-suspendido'; icono = '‖';
        } else if (esMora(est, m)) {
            clase = 'mes-mora'; icono = '⚠';
        } else {
            clase = 'mes-pendiente'; icono = '○';
        }
        var extra = '';
        if (esSuspendido(est, m)) extra = ' <span class="mes-susp-badge">SUSP.</span>';
        else if (esMora(est, m)) extra = ' <span class="mes-mora-badge">MORA</span>';
        mesesHtml += '<span class="mes-badge ' + clase + '">' + icono + ' ' + mesesNombres[m] + extra + '</span>';
    }
    mesesHtml += '<div class="mt-1" style="font-size:11px;"><span class="mes-badge mes-pagado">✓ Pagado</span> <span class="mes-badge mes-pendiente">○ Pendiente</span> <span class="mes-badge mes-mora">⚠ Mora</span> <span class="mes-badge mes-suspendido">‖ Suspendido</span></div>';

    var historialHtml = '';
    if (est.historial && est.historial.length > 0) {
        historialHtml = '<table class="table table-sm table-bordered historial-table mt-2 mb-0">' +
            '<thead><tr><th>Código</th><th>Fecha</th><th>Tipo</th><th>Monto</th><th>Vigencia</th></tr></thead><tbody>';
        var totalHist = 0;
        est.historial.forEach(function(h) {
            totalHist += h.monto;
            historialHtml += '<tr><td>' + h.codigo + '</td><td>' + h.fecha + '</td><td>' + h.tipo + '</td><td class="text-right">Bs. ' + h.monto.toFixed(2) + '</td><td>' + h.vigencia + '</td></tr>';
        });
        historialHtml += '<tr class="font-weight-bold"><td colspan="3" class="text-right">Total pagado:</td><td class="text-right">Bs. ' + totalHist.toFixed(2) + '</td><td></td></tr>';
        historialHtml += '</tbody></table>';
    } else {
        historialHtml = '<p class="text-muted mb-0" style="font-size:12px;">No hay pagos registrados en esta gestión</p>';
    }

    return '<div class="student-detail-panel" id="panel-' + est.est_codigo + '">' +
        '<div class="row">' +
            '<div class="col-md-4">' +
                '<div class="info-panel">' +
                    '<div class="panel-title"><i class="fas fa-bus mr-1"></i> Info Transporte</div>' +
                    '<div class="info-row"><span class="label">Estudiante:</span><span class="value">' + est.nombre + '</span></div>' +
                    '<div class="info-row"><span class="label">Curso:</span><span class="value">' + est.curso + '</span></div>' +
                    '<div class="info-row"><span class="label">Ruta:</span><span class="value">' + est.ruta + (est.ruta === 'Sin asignar' ? ' <span class="badge badge-warning">Pendiente</span>' : '') + '</span></div>' +
                    '<div class="info-row"><span class="label">Vehículo:</span><span class="value">' + est.vehiculo + '</span></div>' +
                    '<div class="info-row"><span class="label">Chofer:</span><span class="value">' + est.chofer + '</span></div>' +
                    '<hr style="margin:5px 0">' +
                    '<div class="info-row"><span class="label">Total Pagado:</span><span class="value text-success">Bs. ' + est.total_pagado.toFixed(2) + '</span></div>' +
                    '<div class="info-row"><span class="label">Meses Pagados:</span><span class="value">' + est.meses_pagados.length + ' / 10</span></div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-8">' +
                '<div class="info-panel">' +
                    '<div class="panel-title"><i class="fas fa-calendar-check mr-1"></i> Estado de Meses</div>' +
                    '<div>' + mesesHtml + '</div>' +
                '</div>' +
                '<div class="info-panel">' +
                    '<div class="panel-title"><i class="fas fa-history mr-1"></i> Historial de Pagos</div>' +
                    historialHtml +
                '</div>' +
            '</div>' +
        '</div>' +
    '</div>';
}

function recalcular() {
    var total = 0;
    var detalle = '';
    var haySeleccionados = false;

    $('#hijos-tbody tr').each(function() {
        var row = $(this);
        var checked = row.find('.check-estudiante').is(':checked');
        if (!checked) return;

        haySeleccionados = true;
        var estCodigo = row.find('input[name$="[est_codigo]"]').val();
        var est = estudiantesData[estCodigo];
        if (!est) return;

        var meses = parseInt(row.find('.meses-select').val()) || 1;
        var montoMensual = parseFloat(row.find('.monto-mensual').val()) || 0;
        var subtotal = meses * montoMensual;

        row.find('.subtotal-cell strong').text('Bs. ' + subtotal.toFixed(2));
        total += subtotal;

        // Calcular meses que se pagarán (solo desde mes actual)
        var mesesPagablesEst = getMesesPagables(est);
        var mesesList = [];
        for (var mi = 0; mi < mesesPagablesEst.length && mesesList.length < meses; mi++) {
            mesesList.push(mesesNombres[mesesPagablesEst[mi]]);
        }

        detalle += '<div><strong>' + est.nombre + ':</strong> ' + mesesList.join(', ') + ' = Bs. ' + subtotal.toFixed(2) + '</div>';
    });

    $('#total-pagar').text(total.toFixed(2));
    $('#detalle-pago').html(detalle || '<span class="text-muted">Seleccione estudiantes</span>');
    $('#btnGuardar').prop('disabled', !haySeleccionados);
}
</script>
@endsection
