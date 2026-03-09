@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-money-bill mr-2"></i>Nuevo Pago de Transporte</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('pagos-transporte.store') }}" method="POST" id="formPago">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante *</label>
                                    <select name="est_codigo" id="est_codigo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($estudiantes as $e)
                                            <option value="{{ $e->est_codigo }}">{{ $e->est_nombres }} {{ $e->est_apellidos }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo de Pago *</label>
                                    <select name="tpago_tipo" id="tpago_tipo" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <option value="mensual" data-meses="1">Mensual (1 mes)</option>
                                        <option value="trimestral" data-meses="3">Trimestral (3 meses)</option>
                                        <option value="semestral" data-meses="6">Semestral (6 meses)</option>
                                        <option value="anual" data-meses="10">Anual (10 meses)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="historialContainer" style="display:none;">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-history"></i> Historial de Pagos - Gestión {{ date('Y') }}</h6>
                                <div id="historialContent"></div>
                                <hr>
                                <strong>Meses pagados: <span id="mesesPagados">0</span>/10</strong><br>
                                <strong>Última vigencia: <span id="ultimaVigencia">-</span></strong><br>
                                <strong>Total pagado: Bs. <span id="totalPagado">0.00</span></strong>
                            </div>
                        </div>
                        
                        <div id="detalleNuevoPago" class="alert alert-warning" style="display:none;">
                            <h6><i class="fas fa-calendar-alt"></i> Detalle del Nuevo Pago</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Meses a cancelar:</strong> <span id="mesesDetalle"></span><br>
                                    <strong>Vigencia:</strong> <span id="vigenciaDetalle"></span>
                                </div>
                                <div class="col-md-6">
                                    <div id="listaMesesPagar" style="font-size: 0.9em;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Meses a Pagar *</label>
                                    <input type="number" name="meses_pagar" id="meses_pagar" class="form-control" min="1" max="10" required readonly>
                                    <small class="text-muted">Se calcula automáticamente</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Monto (Bs.) *</label>
                                    <input type="number" name="tpago_monto" id="tpago_monto" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha de Pago *</label>
                                    <input type="date" name="tpago_fecha_pago" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total a Pagar</label>
                                    <input type="text" id="total_pagar" class="form-control" readonly style="font-weight:bold; font-size:1.1em;">
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="ultima_vigencia" id="ultima_vigencia">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Las fechas se calculan automáticamente considerando solo días hábiles (lunes a viernes). Si la fecha fin cae en fin de semana, se ajustará al viernes anterior.
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="{{ route('pagos-transporte.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let historialData = null;

$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    $('#est_codigo').on('change', function() {
        const estCodigo = $(this).val();
        if (estCodigo) {
            cargarHistorial(estCodigo);
        } else {
            $('#historialContainer').hide();
            $('#tpago_tipo').val('').prop('disabled', false);
        }
    });
    
    $('#tpago_tipo').on('change', function() {
        calcularMesesPagar();
    });
    
    $('#tpago_monto').on('input', function() {
        calcularTotal();
    });
});

function cargarHistorial(estCodigo) {
    $.get(`{{ url('pagos-transporte/historial') }}/${estCodigo}`, function(data) {
        historialData = data;
        
        let totalPagado = 0;
        if (data.pagos.length > 0) {
            let html = '<table class="table table-sm table-bordered mt-2"><thead><tr><th>Tipo</th><th>Monto</th><th>Fecha Pago</th><th>Vigencia</th></tr></thead><tbody>';
            data.pagos.forEach(p => {
                totalPagado += parseFloat(p.tpago_monto);
                html += `<tr><td>${p.tpago_tipo}</td><td>Bs. ${p.tpago_monto}</td><td>${formatDate(p.tpago_fecha_pago)}</td><td>${formatDate(p.tpago_fecha_inicio)} al ${formatDate(p.tpago_fecha_fin)}</td></tr>`;
            });
            html += '</tbody></table>';
            $('#historialContent').html(html);
            $('#mesesPagados').text(data.mesesPagados);
            $('#ultimaVigencia').text(data.ultimaVigencia || '-');
            $('#totalPagado').text(totalPagado.toFixed(2));
            $('#ultima_vigencia').val(data.ultimaVigencia ? data.ultimaVigencia.split('-').reverse().join('-') : '');
            $('#historialContainer').show();
        } else {
            $('#historialContent').html('<p class="mb-0">No hay pagos registrados en esta gestión</p>');
            $('#mesesPagados').text('0');
            $('#ultimaVigencia').text('-');
            $('#totalPagado').text('0.00');
            $('#ultima_vigencia').val('');
            $('#historialContainer').show();
        }
        
        calcularMesesPagar();
    });
}

function calcularMesesPagar() {
    const tipo = $('#tpago_tipo').val();
    if (!tipo || !historialData) return;
    
    const mesesTipo = parseInt($('#tpago_tipo option:selected').data('meses'));
    const mesesPagados = historialData.mesesPagados;
    const mesesRestantes = 10 - mesesPagados;
    
    let mesesPagar = Math.min(mesesTipo, mesesRestantes);
    if (mesesPagar < 1) mesesPagar = 0;
    
    $('#meses_pagar').val(mesesPagar);
    
    if (mesesPagar > 0) {
        let inicio, fin;
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        if (historialData.ultimaVigencia) {
            const partes = historialData.ultimaVigencia.split('-');
            const fecha = new Date(partes[2], partes[1] - 1, partes[0]);
            fecha.setDate(fecha.getDate() + 1);
            inicio = new Date(fecha);
            fin = new Date(fecha);
            fin.setMonth(fin.getMonth() + mesesPagar);
        } else {
            inicio = new Date();
            fin = new Date();
            fin.setMonth(fin.getMonth() + mesesPagar);
        }
        
        // Generar lista de meses
        let listaMeses = '<strong>Meses incluidos:</strong><br>';
        let current = new Date(inicio);
        for(let i = 0; i < mesesPagar; i++) {
            listaMeses += '<span class="badge badge-primary mr-1 mb-1">' + meses[current.getMonth()] + ' ' + current.getFullYear() + '</span>';
            current.setMonth(current.getMonth() + 1);
        }
        $('#listaMesesPagar').html(listaMeses);
        
        const mesInicio = meses[inicio.getMonth()];
        const mesFin = meses[fin.getMonth() - 1];
        const listaMesesTexto = mesInicio === mesFin ? mesInicio : `${mesInicio} - ${mesFin}`;
        
        $('#mesesDetalle').text(`${listaMesesTexto} (${mesesPagar} ${mesesPagar === 1 ? 'mes' : 'meses'})`);
        $('#vigenciaDetalle').text(`${formatDateObj(inicio)} al ${formatDateObj(fin)}`);
        $('#detalleNuevoPago').show();
        
        calcularTotal();
    } else {
        $('#detalleNuevoPago').hide();
        alert('Ya se completaron los 10 meses del año escolar');
    }
}

function calcularTotal() {
    const meses = parseInt($('#meses_pagar').val()) || 0;
    const monto = parseFloat($('#tpago_monto').val()) || 0;
    const total = meses * monto;
    $('#total_pagar').val(total > 0 ? `Bs. ${total.toFixed(2)}` : '');
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;
}

function formatDateObj(d) {
    return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;
}
</script>
@endsection
