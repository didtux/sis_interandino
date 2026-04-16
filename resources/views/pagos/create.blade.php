@extends('layouts.app')

@section('content')
<style>
    .info-panel { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    .info-panel .panel-title { font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 10px; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    .info-panel .info-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 13px; }
    .info-panel .info-row .label { color: #6c757d; }
    .info-panel .info-row .value { font-weight: 600; }
    .mes-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin: 2px; font-weight: 600; }
    .mes-pagado { background: #d4edda; color: #155724; }
    .mes-pendiente { background: #fff3cd; color: #856404; }
    .mes-mora { background: #f8d7da; color: #721c24; }
    .mes-mora-badge { background: #e74c3c; color: #fff; font-size: 9px; padding: 1px 5px; border-radius: 3px; margin-left: 3px; }
    .historial-table { font-size: 12px; }
    .historial-table th { background: #e9ecef; font-size: 11px; text-transform: uppercase; }
    .student-detail-panel { display: none; border-left: 3px solid #007bff; background: #fff; padding: 15px; margin: 10px 0; border-radius: 0 8px 8px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-money-bill-wave mr-2"></i>Registrar Mensualidad</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            @foreach($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('pagos.store') }}" method="POST" id="form-mensualidad">
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
                        </div>

                        {{-- Selector de estudiantes para "Otro padre" --}}
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

                        <input type="hidden" name="pfam_codigo" id="pfam_codigo_hidden" value="">

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
                                            <th>Mensualidad</th>
                                            <th>Mes Desde</th>
                                            <th>Cant. Cuotas</th>
                                            <th>Monto Cuota</th>
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
                                <a href="{{ route('pagos.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
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
var mesActual = {{ (int)date('n') }};
var idxGlobal = 0;
var estudiantesAgregados = [];

// Meses pagables: todos los no pagados (incluyendo en mora)
function getMesesPagables(est) {
    var pagables = [];
    for (var m = 2; m <= 11; m++) {
        if (!est.meses_pagados.includes(m)) pagables.push(m);
    }
    return pagables;
}

function esMora(est, m) {
    var mesLimite = Math.max(mesActual, est.primer_mes || mesActual);
    return m < mesLimite && !est.meses_pagados.includes(m);
}

function getMontoCuota(est, mes) {
    // Si es solo registro, todas las cuotas son iguales (no hay descuento de inscripción)
    if (est.solo_registro) return est.mensualidad;
    // Descuento de inscripción solo aplica a febrero
    return mes === 2 ? est.cuota_febrero : est.mensualidad;
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
            $('#pfam_codigo_hidden').val('');
            $('#pfam_nombre_nuevo').prop('required', true);
            limpiarTabla();
            cargarSelectEstudiantes();
            $('#hijos-container').show();
        } else {
            $('#divAgregarEstudiante').hide();
            $('#pfam_nombre_nuevo').prop('required', false).val('');
            limpiarTabla();
            $('#hijos-container').hide();
        }
    });

    $('#padre-select').on('change', function() {
        var pfamCodigo = $(this).val();
        $('#pfam_codigo_hidden').val(pfamCodigo);
        limpiarTabla();
        if (pfamCodigo) {
            cargarHijosDePadre(pfamCodigo);
        } else {
            $('#hijos-container').hide();
        }
    });

    $('#btnAgregarEst').on('click', function() {
        var estCodigo = $('#est-select-otro').val();
        if (!estCodigo) return;
        if (estudiantesAgregados.includes(estCodigo)) { alert('Este estudiante ya fue agregado'); return; }
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
        sel.append('<option value="' + est.est_codigo + '">' + est.nombre + ' - ' + est.curso + '</option>');
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
        $('#hijos-tbody').append('<tr><td colspan="8" class="text-center text-muted">Este padre no tiene estudiantes inscritos</td></tr>');
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
    var primerMesDisponible = mesesPagables.length > 0 ? mesesPagables[0] : 11;

    // Contar meses vencidos
    var mesLimiteEst = Math.max(mesActual, est.primer_mes || mesActual);
    var mesesVencidos = 0;
    for (var mv = 2; mv < mesLimiteEst; mv++) {
        if (!est.meses_pagados.includes(mv)) mesesVencidos++;
    }

    var mesesOpts = '';
    for (var m = 2; m <= 11; m++) {
        var disabled = '';
        var label = mesesNombres[m];
        if (est.meses_pagados.includes(m)) {
            disabled = 'disabled';
            label += ' (Pagado)';
        } else if (esMora(est, m)) {
            label += ' ⚠ MORA';
        }
        var selected = (m === primerMesDisponible) ? 'selected' : '';
        mesesOpts += '<option value="' + m + '" ' + disabled + ' ' + selected + '>' + label + '</option>';
    }

    var infoDisp = maxCuotas + ' disponible' + (maxCuotas !== 1 ? 's' : '');
    if (mesesVencidos > 0) infoDisp += ' <span class="text-danger">(' + mesesVencidos + ' en mora)</span>';

    var montoPrimerCuota = getMontoCuota(est, primerMesDisponible);
    var esOtro = $('#checkOtroPadre').is(':checked');

    var row = '<tr data-est="' + est.est_codigo + '" data-idx="' + idx + '">' +
        '<td><input type="checkbox" class="check-estudiante" data-idx="' + idx + '" data-est="' + est.est_codigo + '"></td>' +
        '<td><strong>' + est.nombre + '</strong>' +
            '<input type="hidden" name="estudiantes[' + idx + '][est_codigo]" value="' + est.est_codigo + '" disabled>' +
            '<input type="hidden" name="estudiantes[' + idx + '][sin_factura]" value="' + est.sin_factura + '" disabled>' +
        '</td>' +
        '<td>' + est.curso + '</td>' +
        '<td>Bs. ' + est.mensualidad.toFixed(2) + '</td>' +
        '<td><select name="estudiantes[' + idx + '][mes]" class="form-control form-control-sm mes-select" data-idx="' + idx + '" disabled>' + mesesOpts + '</select>' +
            '<small class="text-muted">' + infoDisp + '</small></td>' +
        '<td><input type="number" name="estudiantes[' + idx + '][cantidad_cuotas]" class="form-control form-control-sm cant-cuotas" data-idx="' + idx + '" min="1" max="' + maxCuotas + '" value="1" disabled></td>' +
        '<td><input type="number" name="estudiantes[' + idx + '][pagos_precio]" class="form-control form-control-sm monto-cuota" data-idx="' + idx + '" step="0.01" value="' + montoPrimerCuota.toFixed(2) + '" readonly disabled></td>' +
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
    $row.find('.mes-select').on('change', function() {
        // Actualizar monto cuota según mes seleccionado
        var estCode = $(this).closest('tr').data('est');
        var est2 = estudiantesData[estCode];
        var mesSeleccionado = parseInt($(this).val());
        var monto = getMontoCuota(est2, mesSeleccionado);
        $(this).closest('tr').find('.monto-cuota').val(monto.toFixed(2));
        recalcular();
    });
    $row.find('.cant-cuotas').on('change input', function() { recalcular(); });

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
        } else if (esMora(est, m)) {
            clase = 'mes-mora'; icono = '⚠';
        } else {
            clase = 'mes-pendiente'; icono = '○';
        }
        mesesHtml += '<span class="mes-badge ' + clase + '">' + icono + ' ' + mesesNombres[m] + (esMora(est, m) ? ' <span class="mes-mora-badge">MORA</span>' : '') + '</span>';
    }
    mesesHtml += '<div class="mt-1" style="font-size:11px;"><span class="mes-badge mes-pagado">✓ Pagado</span> <span class="mes-badge mes-pendiente">○ Pendiente</span> <span class="mes-badge mes-mora">⚠ En Mora</span></div>';

    var historialHtml = '';
    if (est.historial && est.historial.length > 0) {
        historialHtml = '<table class="table table-sm table-bordered historial-table mt-2 mb-0">' +
            '<thead><tr><th>Código</th><th>Fecha</th><th>Concepto</th><th>Monto</th></tr></thead><tbody>';
        var totalHist = 0;
        est.historial.forEach(function(h) {
            totalHist += h.monto;
            historialHtml += '<tr><td>' + h.codigo + '</td><td>' + h.fecha + '</td><td>' + h.concepto + '</td><td class="text-right">Bs. ' + h.monto.toFixed(2) + '</td></tr>';
        });
        historialHtml += '<tr class="font-weight-bold"><td colspan="3" class="text-right">Total pagado:</td><td class="text-right">Bs. ' + totalHist.toFixed(2) + '</td></tr>';
        historialHtml += '</tbody></table>';
    } else {
        historialHtml = '<p class="text-muted mb-0" style="font-size:12px;">No hay pagos registrados en esta gestión</p>';
    }

    var descHtml = '';
    if (est.descuentos && est.descuentos.length > 0) {
        descHtml = '<div class="info-row"><span class="label">Descuentos:</span><span class="value text-success">' + est.descuentos.join(', ') + '</span></div>';
    }

    return '<div class="student-detail-panel" id="panel-' + est.est_codigo + '">' +
        '<div class="row">' +
            '<div class="col-md-4">' +
                '<div class="info-panel">' +
                    '<div class="panel-title"><i class="fas fa-file-invoice mr-1"></i> Inscripción ' + new Date().getFullYear() + (est.solo_registro ? ' <span class="badge badge-warning" style="font-size:10px;">Solo registro</span>' : '') + '</div>' +
                    '<div class="info-row"><span class="label">Estudiante:</span><span class="value">' + est.nombre + '</span></div>' +
                    '<div class="info-row"><span class="label">Curso:</span><span class="value">' + est.curso + '</span></div>' +
                    '<div class="info-row"><span class="label">Monto Anual:</span><span class="value">Bs. ' + est.monto_final.toFixed(2) + '</span></div>' +
                    descHtml +
                    (est.solo_registro
                        ? '<div class="info-row"><span class="label">Tipo:</span><span class="value"><span class="badge badge-info">Fuera de periodo</span></span></div>' +
                          '<div class="info-row"><span class="label">Mensualidad:</span><span class="value">Bs. ' + est.mensualidad.toFixed(2) + '</span></div>'
                        : '<div class="info-row"><span class="label">Inscripción:</span><span class="value">Bs. ' + est.monto_inscripcion.toFixed(2) + '</span></div>' +
                          '<div class="info-row"><span class="label">Mensualidad:</span><span class="value">Bs. ' + est.mensualidad.toFixed(2) + '</span></div>' +
                          '<div class="info-row"><span class="label">Cuota Feb (con desc. insc.):</span><span class="value text-info">Bs. ' + est.cuota_febrero.toFixed(2) + '</span></div>'
                    ) +
                    '<hr style="margin:5px 0">' +
                    '<div class="info-row"><span class="label">Monto a cobrar (' + est.meses_cobrables + ' meses):</span><span class="value text-primary">Bs. ' + est.monto_a_cobrar.toFixed(2) + '</span></div>' +
                    '<div class="info-row"><span class="label">Total Pagado (mens.):</span><span class="value text-success">Bs. ' + est.total_pagado.toFixed(2) + '</span></div>' +
                    '<div class="info-row"><span class="label">Saldo Pendiente:</span><span class="value text-danger">Bs. ' + est.saldo_pendiente.toFixed(2) + '</span></div>' +
                    (function(){
                        var mesLimite = Math.max(mesActual, est.primer_mes || mesActual);
                        var enMora = 0;
                        var mesLimiteP = Math.max(mesActual, est.primer_mes || mesActual);
                        for (var mv = 2; mv < mesLimiteP; mv++) { if (!est.meses_pagados.includes(mv)) enMora++; }
                        return '<div class="info-row"><span class="label">Meses Pagados:</span><span class="value">' + est.meses_pagados.length + ' / 10</span></div>' +
                               (enMora > 0 ? '<div class="info-row"><span class="label">Meses en Mora:</span><span class="value text-danger">' + enMora + '</span></div>' : '');
                    })() +
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

        var mesInicio = parseInt(row.find('.mes-select').val()) || 2;
        var cantidad = parseInt(row.find('.cant-cuotas').val()) || 1;
        var mesesPagables = getMesesPagables(est);
        var subtotal = 0;
        var mesesList = [];
        var cuotasContadas = 0;

        // Recorrer meses pagables desde el mes seleccionado
        for (var pi = 0; pi < mesesPagables.length && cuotasContadas < cantidad; pi++) {
            var m = mesesPagables[pi];
            if (m < mesInicio) continue;
            var monto = getMontoCuota(est, m);
            subtotal += monto;
            mesesList.push(mesesNombres[m] + ' (Bs.' + monto.toFixed(0) + ')');
            cuotasContadas++;
        }

        // Actualizar monto cuota visible (del primer mes)
        var montoPrimer = getMontoCuota(est, mesInicio);
        row.find('.monto-cuota').val(montoPrimer.toFixed(2));

        row.find('.subtotal-cell strong').text('Bs. ' + subtotal.toFixed(2));
        total += subtotal;
        detalle += '<div><strong>' + est.nombre + ' (' + est.curso + '):</strong> ' + mesesList.join(', ') + ' = Bs. ' + subtotal.toFixed(2) + '</div>';
    });

    $('#total-pagar').text(total.toFixed(2));
    $('#detalle-pago').html(detalle || '<span class="text-muted">Seleccione estudiantes</span>');
    $('#btnGuardar').prop('disabled', !haySeleccionados);
}
</script>
@endsection
