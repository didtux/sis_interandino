@extends('layouts.app')

@section('content')
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante <span class="text-danger">*</span></label>
                                    <select name="est_codigo" id="estudiante-select" class="form-control select2" required>
                                        <option value="">Seleccione un estudiante inscrito</option>
                                        @foreach($estudiantes as $e)
                                            @if($e->inscripcion)
                                            @php
                                                $insc = $e->inscripcion;
                                                $montoTotal = $insc->insc_monto_total ?? 0;
                                                $montoFinal = $insc->insc_monto_final ?? 0;
                                                $montoPagado = $insc->insc_monto_pagado ?? 0; // 300 Bs inscripción
                                                $montoMensualidad = $montoFinal > 0 ? $montoFinal / 10 : 0;
                                                // Primera cuota = mensualidad - pago inicial (300)
                                                $primeraCuota = max(0, $montoMensualidad - 300);
                                                $proximaCuota = $insc->proxima_cuota ?? $montoMensualidad;
                                                
                                                // Calcular total pagado en mensualidades
                                                $totalPagosMensualidades = 0;
                                                if(isset($insc->historial_pagos)) {
                                                    foreach($insc->historial_pagos as $pago) {
                                                        $totalPagosMensualidades += $pago->pagos_precio;
                                                    }
                                                }
                                                
                                                // Saldo = Monto Final - (Inscripción + Mensualidades pagadas)
                                                $saldo = $montoFinal - ($montoPagado + $totalPagosMensualidades);
                                                
                                                $descuento = $insc->descuentos->first()->desc_nombre ?? 'Sin descuento';
                                                $sinFactura = $insc->insc_sin_factura ?? 0;
                                                $pagosRealizados = $insc->pagos_realizados ?? 0;
                                                $mesesPagados = $insc->meses_pagados ?? [];
                                                $padreCodigo = $insc->pfam_codigo ?? '';
                                                $padreNombre = $insc->padreFamilia->pfam_nombres ?? 'N/A';
                                                $padreCi = $insc->padreFamilia->pfam_ci ?? 'N/A';
                                            @endphp
                                            <option value="{{ $e->est_codigo }}" 
                                                data-padre="{{ $padreCodigo }}"
                                                data-padre-nombre="{{ $padreNombre }}"
                                                data-padre-ci="{{ $padreCi }}"
                                                data-padres='@json($e->padres->map(function($p) { return ["pfam_codigo" => $p->pfam_codigo, "pfam_nombres" => $p->pfam_nombres, "pfam_ci" => $p->pfam_ci]; }))'
                                                data-monto-total="{{ $montoTotal }}"
                                                data-monto-final="{{ $montoFinal }}"
                                                data-monto-pagado="{{ $montoPagado }}"
                                                data-saldo="{{ $saldo }}"
                                                data-mensualidad="{{ $montoMensualidad }}"
                                                data-primera-cuota="{{ $primeraCuota }}"
                                                data-proxima-cuota="{{ $proximaCuota }}"
                                                data-descuento="{{ $descuento }}"
                                                data-sin-factura="{{ $sinFactura }}"
                                                data-pagos-realizados="{{ $pagosRealizados }}"
                                                data-meses-pagados="{{ json_encode($mesesPagados) }}">
                                                {{ $e->est_nombres }} {{ $e->est_apellidos }} - {{ $e->curso->cur_nombre ?? '' }}
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Padre de Familia <span class="text-danger">*</span></label>
                                    <select name="pfam_codigo" id="padre-select" class="form-control select2" required>
                                        <option value="">Primero seleccione estudiante...</option>
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
                                        <label class="form-check-label" for="checkOtroPadre" title="Registrar nuevo padre">Otro</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="info-inscripcion" style="display:none;">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Monto Total Anual:</strong> Bs. <span id="info-monto-total">0.00</span><br>
                                            <strong>Descuento Aplicado:</strong> <span id="info-descuento">-</span><br>
                                            <strong>Monto Final (con descuento):</strong> Bs. <span id="info-monto-final">0.00</span><br>
                                            <strong>Monto por Mensualidad:</strong> Bs. <span id="info-mensualidad">0.00</span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Pago Inicial (Inscripción):</strong> Bs. <span id="info-pagado">0.00</span><br>
                                            <strong>Saldo Total Pendiente:</strong> <span class="text-danger">Bs. <span id="info-saldo">0.00</span></span><br>
                                            <strong>Primera Cuota a Pagar:</strong> <span class="text-primary">Bs. <span id="info-primera-cuota">0.00</span></span><br>
                                            <strong>Próxima Cuota:</strong> <span class="text-warning">Bs. <span id="info-proxima-cuota">0.00</span></span><br>
                                            <strong>Tipo de Factura:</strong> <span id="info-tipo-factura">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="sin_factura" id="sin_factura" value="0">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mes/Cuota <span class="text-danger">*</span></label>
                                    <select name="mes" id="mes-select" class="form-control" required>
                                        <option value="">Seleccione mes</option>
                                        <option value="2" data-mes="2">Febrero (Cuota 1)</option>
                                        <option value="3" data-mes="3">Marzo (Cuota 2)</option>
                                        <option value="4" data-mes="4">Abril (Cuota 3)</option>
                                        <option value="5" data-mes="5">Mayo (Cuota 4)</option>
                                        <option value="6" data-mes="6">Junio (Cuota 5)</option>
                                        <option value="7" data-mes="7">Julio (Cuota 6)</option>
                                        <option value="8" data-mes="8">Agosto (Cuota 7)</option>
                                        <option value="9" data-mes="9">Septiembre (Cuota 8)</option>
                                        <option value="10" data-mes="10">Octubre (Cuota 9)</option>
                                        <option value="11" data-mes="11">Noviembre (Cuota 10)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cantidad de Cuotas <span class="text-danger">*</span></label>
                                    <input type="number" name="cantidad_cuotas" id="cantidad-cuotas" class="form-control" min="1" max="10" value="1" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Monto por Cuota <span class="text-danger">*</span></label>
                                    <input type="number" name="pagos_precio" id="monto-cuota" class="form-control" step="0.01" min="0" value="0" readonly required>
                                    <small class="text-muted" id="texto-cuota">Calculado automáticamente</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-success">
                                    <strong>Total a Pagar:</strong> Bs. <span id="total-pagar">0.00</span>
                                    <div id="detalle-cuotas" style="margin-top:10px; display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Pago
                            </button>
                            <a href="{{ route('pagos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>

                    <!-- Historial de Pagos -->
                    <div id="historial-pagos" style="display:none; margin-top:30px;">
                        <h5><i class="fas fa-history mr-2"></i>Historial de Pagos del Estudiante</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Código</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="historial-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var historialPagos = @json($estudiantes->pluck('inscripcion.historial_pagos', 'est_codigo'));

$(document).ready(function() {
    $('#estudiante-select').select2({
        placeholder: 'Buscar estudiante inscrito...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap4'
    });

    $('#padre-select').select2({
        placeholder: 'Seleccionar padre...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap4'
    });

    $('#checkOtroPadre').on('change', function() {
        if ($(this).is(':checked')) {
            $('#padre-select').closest('.col-md-5').hide();
            $('#divOtroPadre').show();
            $('#padre-select').prop('required', false).val('').trigger('change');
            $('#pfam_nombre_nuevo').prop('required', true);
        } else {
            $('#padre-select').closest('.col-md-5').show();
            $('#divOtroPadre').hide();
            $('#padre-select').prop('required', true);
            $('#pfam_nombre_nuevo').prop('required', false).val('');
        }
    });
    
    $('#estudiante-select').on('change', function() {
        var selected = $(this).find(':selected');
        var estCodigo = selected.val();
        
        if (estCodigo) {
            var padreCodigo = selected.data('padre');
            var padreNombre = selected.data('padre-nombre');
            var padreCi = selected.data('padre-ci');
            var montoTotal = parseFloat(selected.data('monto-total')) || 0;
            var montoFinal = parseFloat(selected.data('monto-final')) || 0;
            var montoPagado = parseFloat(selected.data('monto-pagado')) || 0;
            var saldo = parseFloat(selected.data('saldo')) || 0;
            var montoMensualidad = parseFloat(selected.data('mensualidad')) || 0;
            var primeraCuota = parseFloat(selected.data('primera-cuota')) || 0;
            var proximaCuota = parseFloat(selected.data('proxima-cuota')) || 0;
            var descuento = selected.data('descuento');
            var sinFactura = selected.data('sin-factura');
            var pagosRealizados = parseInt(selected.data('pagos-realizados')) || 0;
            var mesesPagados = selected.data('meses-pagados');
            if (typeof mesesPagados === 'string') {
                try {
                    mesesPagados = JSON.parse(mesesPagados);
                } catch(e) {
                    mesesPagados = [];
                }
            }
            if (!Array.isArray(mesesPagados)) {
                mesesPagados = [];
            }

            // Guardar en variables globales
            window.montoMensualidad = montoMensualidad;
            window.primeraCuota = primeraCuota;
            window.proximaCuota = proximaCuota;
            window.pagosRealizados = pagosRealizados;
            window.mesesPagados = mesesPagados;

            // Ocultar meses ya pagados
            $('#mes-select option').each(function() {
                var mes = parseInt($(this).data('mes'));
                if (mes && mesesPagados.includes(mes)) {
                    $(this).hide();
                } else if (mes) {
                    $(this).show();
                }
            });

            // Cargar padres del estudiante directamente desde data attribute
            var padresData = selected.data('padres');
            if (padresData && padresData.length > 0) {
                $('#padre-select').empty().append('<option value="">Seleccione padre/tutor...</option>');
                padresData.forEach(function(padre) {
                    var isSelected = padre.pfam_codigo == padreCodigo ? 'selected' : '';
                    $('#padre-select').append('<option value="' + padre.pfam_codigo + '" ' + isSelected + '>' + padre.pfam_nombres + ' - CI: ' + (padre.pfam_ci || 'N/A') + '</option>');
                });
                if (!padreCodigo && padresData.length > 0) {
                    $('#padre-select').val(padresData[0].pfam_codigo).trigger('change');
                }
            } else {
                $('#padre-select').empty().append('<option value="">No hay padres registrados</option>');
            }

            // Mostrar información
            $('#info-monto-total').text(montoTotal.toFixed(2));
            $('#info-monto-final').text(montoFinal.toFixed(2));
            $('#info-mensualidad').text(montoMensualidad.toFixed(2));
            $('#info-pagado').text(montoPagado.toFixed(2));
            $('#info-saldo').text(saldo.toFixed(2));
            $('#info-descuento').text(descuento);
            $('#info-primera-cuota').text(primeraCuota.toFixed(2));
            $('#info-proxima-cuota').text(proximaCuota.toFixed(2));
            $('#info-tipo-factura').html(sinFactura == 1 ? '<span class="badge badge-warning">Sin Factura (TAL)</span>' : '<span class="badge badge-success">Con Factura (REC)</span>');
            
            $('#sin_factura').val(sinFactura || 0);
            $('#monto-cuota').val(proximaCuota.toFixed(2));
            $('#texto-cuota').text('Próxima cuota (ajustada según pagos anteriores)');
            
            // Mostrar historial
            var pagos = historialPagos[estCodigo] || [];
            if (pagos.length > 0) {
                var html = '';
                pagos.forEach(function(pago) {
                    var fecha = new Date(pago.pagos_fecha);
                    var estado = pago.pagos_estado == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Anulado</span>';
                    html += '<tr>';
                    html += '<td>' + fecha.toLocaleDateString() + '</td>';
                    html += '<td>' + (pago.pagos_codigo || 'N/A') + '</td>';
                    html += '<td>' + pago.concepto + '</td>';
                    html += '<td>Bs. ' + parseFloat(pago.pagos_precio).toFixed(2) + '</td>';
                    html += '<td>' + estado + '</td>';
                    html += '</tr>';
                });
                $('#historial-tbody').html(html);
                $('#historial-pagos').show();
            } else {
                $('#historial-pagos').hide();
            }
            
            $('#info-inscripcion').show();
            calcularTotal();
        } else {
            $('#info-inscripcion').hide();
            $('#historial-pagos').hide();
            $('#padre-select').empty().append('<option value="">Primero seleccione estudiante...</option>').trigger('change');
            $('#monto-cuota').val(0);
            $('#mes-select option').show();
        }
    });
    
    $('#mes-select, #cantidad-cuotas').on('change', calcularTotal);
});

function calcularTotal() {
    var mes = parseInt($('#mes-select').val()) || 0;
    var cantidad = parseInt($('#cantidad-cuotas').val()) || 0;
    var montoMensualidad = window.montoMensualidad || 0;
    var proximaCuota = window.proximaCuota || 0;
    var mesesPagados = window.mesesPagados || [];
    
    if (!mes || !cantidad) {
        $('#total-pagar').text('0.00');
        $('#detalle-cuotas').hide();
        return;
    }
    
    var total = 0;
    var detalle = '<small><strong>Detalle:</strong><br>';
    var mesesNombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];
    var primerMes = true;
    
    for (var i = 0; i < cantidad; i++) {
        var mesActual = mes + i;
        if (mesActual > 11) break;
        if (mesesPagados.includes(mesActual)) continue;
        
        // Primer mes disponible usa próxima cuota, resto usa cuota regular
        var monto = primerMes ? proximaCuota : montoMensualidad;
        primerMes = false;
        
        total += monto;
        detalle += mesesNombres[mesActual] + ': Bs. ' + monto.toFixed(2) + '<br>';
    }
    
    detalle += '</small>';
    $('#total-pagar').text(total.toFixed(2));
    $('#detalle-cuotas').html(detalle).show();
}
</script>
@endsection
