@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Nueva Inscripción</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('inscripciones.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <label>Estudiante *</label>
                                <select name="est_codigo" id="selectEstudiante" class="form-control select2" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($estudiantes as $e)
                                        <option value="{{ $e->est_codigo }}">{{ $e->est_codigo }} - {{ $e->est_nombres }} {{ $e->est_apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5" id="divPadre">
                                <label>Padre/Tutor *</label>
                                <select name="pfam_codigo" id="selectPadre" class="form-control select2" required>
                                    <option value="">Primero seleccione estudiante...</option>
                                </select>
                            </div>
                            <div class="col-md-5" id="divOtroPadre" style="display:none;">
                                <label>Nombre del Padre/Tutor *</label>
                                <input type="text" name="pfam_nombre_nuevo" id="pfam_nombre_nuevo" class="form-control" placeholder="Ingrese nombre completo">
                            </div>
                            <div class="col-md-1 mt-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="checkOtroPadre">
                                    <label class="form-check-label" for="checkOtroPadre" title="Registrar nuevo padre">Otro</label>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Curso *</label>
                                <select name="cur_codigo" class="form-control select2" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Gestión *</label>
                                <input type="text" name="insc_gestion" class="form-control" value="{{ date('Y') }}" required>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Monto Total (Inscripción + 10 Mensualidades) *</label>
                                <input type="number" step="0.01" name="insc_monto_total" id="monto_total" class="form-control" required>
                                <small class="text-muted">Monto total anual</small>
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
                            <div class="col-md-6 mt-3">
                                <label>Tipo de Recibo</label>
                                <input type="text" id="tipo_recibo" class="form-control" readonly value="Con Factura (REC)">
                                <input type="hidden" name="insc_sin_factura" id="insc_sin_factura" value="0">
                                <small class="text-muted">Se detecta automáticamente según descuento</small>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Monto Descuento</label>
                                <input type="number" step="0.01" name="insc_monto_descuento" id="monto_descuento" class="form-control" readonly value="0">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Monto Final</label>
                                <input type="number" step="0.01" name="insc_monto_final" id="monto_final" class="form-control" readonly value="0">
                                <small class="text-muted">Monto después del descuento</small>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Monto por Mensualidad</label>
                                <input type="text" id="monto_mensualidad" class="form-control" readonly value="0.00">
                                <small class="text-muted">Monto final ÷ 10 meses</small>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label>Pago Inicial (Máx. 300 Bs)</label>
                                <input type="number" step="0.01" name="insc_monto_pagado" id="monto_pagado" class="form-control" value="0" max="300">
                                <small class="text-muted">Pago inicial de inscripción</small>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label>Concepto</label>
                                <textarea name="insc_concepto" class="form-control" rows="3">Inscripción gestión {{ date('Y') }}</textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar Inscripción</button>
                            <a href="{{ route('inscripciones.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
});

$('#checkOtroPadre').on('change', function() {
    if ($(this).is(':checked')) {
        $('#divPadre').hide();
        $('#divOtroPadre').show();
        $('#selectPadre').prop('required', false).val('').trigger('change');
        $('#pfam_nombre_nuevo').prop('required', true);
    } else {
        $('#divPadre').show();
        $('#divOtroPadre').hide();
        $('#selectPadre').prop('required', true);
        $('#pfam_nombre_nuevo').prop('required', false).val('');
    }
});

$('#selectEstudiante').on('change', function() {
    const estCodigo = $(this).val();
    if (estCodigo) {
        $.ajax({
            url: '{{ url("/api/estudiantes") }}/' + estCodigo + '/padres',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
                data.forEach(padre => {
                    $('#selectPadre').append(`<option value="${padre.pfam_codigo}">${padre.pfam_nombres} - CI: ${padre.pfam_ci || 'N/A'}</option>`);
                });
                if (data.length > 0) {
                    $('#selectPadre').val(data[0].pfam_codigo).trigger('change');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar padres:', error);
                $('#selectPadre').empty().append('<option value="">Error al cargar padres</option>');
            }
        });
    } else {
        $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>');
    }
});

function calcularMontos() {
    const montoTotal = parseFloat($('#monto_total').val()) || 0;
    const descId = $('#desc_id').val();
    let porcentaje = 0;
    let sinFactura = false;
    
    if (descId) {
        porcentaje = parseFloat($('#desc_id option:selected').data('porcentaje')) || 0;
        const nombreDesc = $('#desc_id option:selected').data('nombre') || '';
        sinFactura = nombreDesc.includes('sin factura');
    }
    
    const montoDescuento = (montoTotal * porcentaje) / 100;
    const montoFinal = montoTotal - montoDescuento;
    const montoMensualidad = montoFinal / 10;
    
    $('#monto_descuento').val(montoDescuento.toFixed(2));
    $('#monto_final').val(montoFinal.toFixed(2));
    $('#monto_mensualidad').val(montoMensualidad.toFixed(2));
    
    if (sinFactura) {
        $('#tipo_recibo').val('Sin Factura (TAL)');
        $('#insc_sin_factura').val('1');
    } else {
        $('#tipo_recibo').val('Con Factura (REC)');
        $('#insc_sin_factura').val('0');
    }
}

$('#monto_total, #desc_id').on('change', calcularMontos);

$('#monto_pagado').on('input', function() {
    const valor = parseFloat($(this).val()) || 0;
    if (valor > 300) {
        $(this).val(300);
        alert('El pago inicial máximo es 300 Bs');
    }
});
</script>
@endsection
@endsection
