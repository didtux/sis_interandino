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
                                    <label>Cantidad de Meses a Pagar *</label>
                                    <select name="meses_pagar" id="meses_pagar" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <option value="1">1 mes (Mensual)</option>
                                        <option value="2">2 meses (Bimestral)</option>
                                        <option value="3">3 meses (Trimestral)</option>
                                        <option value="4">4 meses (Cuatrimestral)</option>
                                        <option value="5">5 meses</option>
                                        <option value="6">6 meses (Semestral)</option>
                                        <option value="7">7 meses</option>
                                        <option value="8">8 meses</option>
                                        <option value="9">9 meses</option>
                                        <option value="10">10 meses (Anual)</option>
                                    </select>
                                    <small class="text-muted" id="mesesDisponiblesInfo"></small>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Monto Mensual (Bs.) *</label>
                                    <input type="number" name="tpago_monto" id="tpago_monto" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha de Pago *</label>
                                    <input type="date" name="tpago_fecha_pago" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
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
let mesesRestantesGlobal = 10;

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
            $('#detalleNuevoPago').hide();
            mesesRestantesGlobal = 10;
            actualizarOpcionesMeses();
        }
    });
    
    $('#meses_pagar').on('change', function() {
        calcularDetalle();
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
            let html = '<table class="table table-sm table-bordered mt-2"><thead><tr><th>Tipo</th><th>Monto</th><th>Fecha Pago</th><th>Vigencia</th><th>Estado</th></tr></thead><tbody>';
            data.pagos.forEach(p => {
                totalPagado += parseFloat(p.tpago_monto);
                html += `<tr><td>${p.tpago_tipo}</td><td>Bs. ${parseFloat(p.tpago_monto).toFixed(2)}</td><td>${formatDate(p.tpago_fecha_pago)}</td><td>${formatDate(p.tpago_fecha_inicio)} al ${formatDate(p.tpago_fecha_fin)}</td><td><span class="badge badge-success">${p.tpago_estado}</span></td></tr>`;
            });
            html += '</tbody></table>';
            $('#historialContent').html(html);
        } else {
            $('#historialContent').html('<p class="mb-0">No hay pagos registrados en esta gestión</p>');
        }
        
        $('#mesesPagados').text(data.mesesPagados);
        $('#ultimaVigencia').text(data.ultimaVigencia || '-');
        $('#totalPagado').text(totalPagado.toFixed(2));
        $('#ultima_vigencia').val(data.ultimaVigencia ? data.ultimaVigencia.split('-').reverse().join('-') : '');
        $('#historialContainer').show();
        
        mesesRestantesGlobal = 10 - data.mesesPagados;
        actualizarOpcionesMeses();
        
        // Reset selección
        $('#meses_pagar').val('');
        $('#detalleNuevoPago').hide();
        $('#total_pagar').val('');
    });
}

function actualizarOpcionesMeses() {
    const select = $('#meses_pagar');
    select.find('option').each(function() {
        const val = parseInt($(this).val());
        if (val) {
            $(this).prop('disabled', val > mesesRestantesGlobal);
        }
    });
    
    if (mesesRestantesGlobal <= 0) {
        $('#mesesDisponiblesInfo').html('<span class="text-danger font-weight-bold">Ya se completaron los 10 meses del año escolar</span>');
        select.prop('disabled', true);
    } else {
        $('#mesesDisponiblesInfo').html('Meses disponibles: <strong>' + mesesRestantesGlobal + '</strong>');
        select.prop('disabled', false);
    }
}

function calcularDetalle() {
    const mesesPagar = parseInt($('#meses_pagar').val());
    if (!mesesPagar || !historialData) return;
    
    if (mesesPagar > mesesRestantesGlobal) {
        alert('Solo quedan ' + mesesRestantesGlobal + ' meses disponibles');
        $('#meses_pagar').val('');
        return;
    }
    
    const mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    let inicio, fin;
    
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
        listaMeses += '<span class="badge badge-primary mr-1 mb-1">' + mesesNombres[current.getMonth()] + ' ' + current.getFullYear() + '</span>';
        current.setMonth(current.getMonth() + 1);
    }
    $('#listaMesesPagar').html(listaMeses);
    
    const mesInicio = mesesNombres[inicio.getMonth()];
    const idxFin = fin.getMonth() === 0 ? 11 : fin.getMonth() - 1;
    const mesFin = mesesNombres[idxFin];
    const listaMesesTexto = mesInicio === mesFin ? mesInicio : `${mesInicio} - ${mesFin}`;
    
    $('#mesesDetalle').text(`${listaMesesTexto} (${mesesPagar} ${mesesPagar === 1 ? 'mes' : 'meses'})`);
    $('#vigenciaDetalle').text(`${formatDateObj(inicio)} al ${formatDateObj(fin)}`);
    $('#detalleNuevoPago').show();
    
    calcularTotal();
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
