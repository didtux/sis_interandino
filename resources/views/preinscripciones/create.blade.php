@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4><i class="fas fa-user-clock mr-2"></i>Pre-inscripción — gestión {{ $gestion }}</h4></div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif
            <form method="POST" action="{{ route('preinscripciones.store') }}" id="formPre">
                @csrf
                <input type="hidden" name="gestion" value="{{ $gestion }}">

                <div class="btn-group btn-group-toggle mb-3" data-toggle="buttons">
                    <label class="btn btn-outline-primary {{ old('tipo', 'ANTIGUO') === 'ANTIGUO' ? 'active' : '' }}">
                        <input type="radio" name="tipo" value="ANTIGUO" {{ old('tipo', 'ANTIGUO') === 'ANTIGUO' ? 'checked' : '' }}> Alumno del colegio
                    </label>
                    <label class="btn btn-outline-primary {{ old('tipo') === 'NUEVO' ? 'active' : '' }}">
                        <input type="radio" name="tipo" value="NUEVO" {{ old('tipo') === 'NUEVO' ? 'checked' : '' }}> Alumno nuevo
                    </label>
                </div>

                {{-- Alumno antiguo --}}
                <div id="bloqueAntiguo">
                    <div class="form-group">
                        <label class="font-weight-bold">Estudiante</label>
                        <select name="est_codigo" id="estSelect" class="form-control select2">
                            <option value="">Buscar por apellido, nombre o CI…</option>
                            @foreach($estudiantes as $e)
                                <option value="{{ $e->est_codigo }}" {{ old('est_codigo') === $e->est_codigo ? 'selected' : '' }}>
                                    {{ $e->est_apellidos }} {{ $e->est_nombres }} — {{ optional($e->curso)->cur_nombre }} {{ $e->est_ci ? '· CI ' . $e->est_ci : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="infoAntiguo" class="mb-3" style="display:none;"></div>
                </div>

                {{-- Alumno nuevo --}}
                <div id="bloqueNuevo" style="display:none;">
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Apellidos *</label><input type="text" name="pre_apellidos" class="form-control" value="{{ old('pre_apellidos') }}"></div>
                        <div class="col-md-4 form-group"><label>Nombres *</label><input type="text" name="pre_nombres" class="form-control" value="{{ old('pre_nombres') }}"></div>
                        <div class="col-md-2 form-group"><label>CI</label><input type="text" name="pre_ci" class="form-control" value="{{ old('pre_ci') }}"></div>
                        <div class="col-md-2 form-group"><label>Nacimiento</label><input type="date" name="pre_fecha_nac" class="form-control" value="{{ old('pre_fecha_nac') }}"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group"><label>U.E. de procedencia</label><input type="text" name="pre_ue_procedencia" class="form-control" value="{{ old('pre_ue_procedencia') }}"></div>
                        <div class="col-md-5 form-group"><label>Padre / tutor *</label><input type="text" name="padre_nombre" class="form-control" value="{{ old('padre_nombre') }}"></div>
                        <div class="col-md-3 form-group"><label>CI del padre</label><input type="text" name="padre_ci" class="form-control" value="{{ old('padre_ci') }}" placeholder="si ya existe, se reutiliza"></div>
                    </div>
                    <div class="alert alert-light border small">La ficha del alumno se crea al activar la gestión {{ $gestion }}; hasta entonces no aparece en las listas de este año.</div>
                </div>

                <div class="row">
                    <div class="col-md-5 form-group">
                        <label class="font-weight-bold">Curso {{ $gestion }}</label>
                        <select name="cur_destino" id="curDestino" class="form-control js-select2" required>
                            <option value="">Seleccione…</option>
                            @foreach($cursos as $c)
                                <option value="{{ $c->cur_codigo }}" data-tarifa="{{ $tarifas[$c->cur_codigo] ?? '' }}" data-reserva="{{ $reservas[$c->cur_codigo][0] }}" data-reserva-origen="{{ $reservas[$c->cur_codigo][1] }}" {{ old('cur_destino') === $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                            @endforeach
                        </select>
                        <small id="tarifaInfo" class="text-muted"></small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Descuento propuesto</label>
                        <select name="desc_id" class="form-control js-select2">
                            <option value="">Sin descuento</option>
                            @foreach($descuentos as $d)
                                <option value="{{ $d->desc_id }}" {{ old('desc_id') == $d->desc_id ? 'selected' : '' }}>
                                    {{ $d->desc_nombre }} ({{ $d->desc_tipo === 'MONTO' ? 'Bs ' . number_format($d->desc_monto, 2) : rtrim(rtrim(number_format($d->desc_porcentaje, 2), '0'), '.') . '%' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Se confirma al inscribir, contra la tarifa {{ $gestion }}.</small>
                    </div>
                </div>

                <hr>
                <h6 class="font-weight-bold"><i class="fas fa-cash-register mr-1"></i>Cobro de la reserva</h6>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Monto de la reserva (Bs)</label>
                        <input type="number" step="0.01" min="0" name="monto" id="monto" class="form-control" value="{{ old('monto') }}" required {{ $puedeCambiarMonto ? '' : 'readonly' }}>
                        <small class="text-muted" id="reservaOrigen"></small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Forma de pago</label>
                        <select name="metodo" id="metodo" class="form-control js-select2">
                            @foreach(['EFECTIVO', 'QR', 'MIXTO'] as $m)<option value="{{ $m }}" {{ old('metodo') === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mixto"><label>Efectivo</label><input type="number" step="0.01" name="monto_efectivo" class="form-control" value="{{ old('monto_efectivo') }}"></div>
                    <div class="col-md-2 form-group mixto"><label>QR</label><input type="number" step="0.01" name="monto_qr" class="form-control" value="{{ old('monto_qr') }}"></div>
                    <div class="col-md-2 form-group qr"><label>N° transacción</label><input type="text" name="transferencia_nro" class="form-control" value="{{ old('transferencia_nro') }}"></div>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="sinFactura" name="sin_factura" value="1" {{ old('sin_factura') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sinFactura">Sin factura (talonario)</label>
                </div>
                <div class="form-group"><label>Observaciones</label><textarea name="obs" class="form-control" rows="2">{{ old('obs') }}</textarea></div>
                <div id="overrideBox" class="custom-control custom-checkbox mb-3" style="display:none;">
                    <input type="checkbox" class="custom-control-input" id="override" name="override_observado" value="1">
                    <label class="custom-control-label text-danger" for="override">Autorizo la pre-inscripción de este estudiante observado</label>
                </div>
                <button class="btn btn-primary-modern" id="btnGuardar"><i class="fas fa-save mr-1"></i>Registrar y emitir recibo</button>
                <a href="{{ route('preinscripciones.index', ['gestion' => $gestion]) }}" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    function tipo() { return $('input[name=tipo]:checked').val(); }
    function mostrarTipo() {
        $('#bloqueAntiguo').toggle(tipo() === 'ANTIGUO');
        $('#bloqueNuevo').toggle(tipo() === 'NUEVO');
        $('#overrideBox').hide();
        $('#btnGuardar').prop('disabled', false);
    }
    $('input[name=tipo]').on('change', mostrarTipo); mostrarTipo();

    function metodo() {
        var m = $('#metodo').val();
        $('.mixto').toggle(m === 'MIXTO'); $('.qr').toggle(m !== 'EFECTIVO');
    }
    $('#metodo').on('change', metodo); metodo();

    var puedeCambiarMonto = {{ $puedeCambiarMonto ? 'true' : 'false' }};
    var montoInicial = '{{ old('monto') }}';
    function tarifa() {
        var $o = $('#curDestino option:selected');
        var t = $o.data('tarifa');
        $('#tarifaInfo').text(t ? 'Tarifa {{ $gestion }}: Bs ' + Number(t).toFixed(2) : 'Sin tarifa cargada para este curso en {{ $gestion }}.');
        // La reserva sale del curso destino; sin la capacidad no se puede cambiar
        if ($o.val()) {
            if (!montoInicial || !puedeCambiarMonto) $('#monto').val(Number($o.data('reserva')).toFixed(2));
            montoInicial = '';
            $('#reservaOrigen').text('Según ' + $o.data('reserva-origen') + (puedeCambiarMonto ? '' : ' · su rol no puede cambiarlo'));
        }
    }
    $('#curDestino').on('change', tarifa); tarifa();

    // Alumno antiguo: curso sugerido, mora y lista de observados.
    $('#estSelect').on('change', function () {
        var est = $(this).val(); var $i = $('#infoAntiguo');
        $('#overrideBox').hide(); $('#btnGuardar').prop('disabled', false);
        if (!est) { $i.hide(); return; }
        $.get('{{ url('preinscripciones/controles') }}/' + est, { gestion: {{ $gestion }} }, function (d) {
            var h = '<div class="border rounded p-2 bg-light small">Curso actual: <strong>' + (d.curso_actual || '—') + '</strong>';
            if (d.egresa) h += ' <span class="badge badge-dark">ÚLTIMO GRADO: egresa</span>';
            h += '</div>';
            if (d.ya_preinscrito) { h += '<div class="alert alert-danger mt-2 mb-0">Ya tiene la pre-inscripción ' + d.ya_preinscrito + '.</div>'; $('#btnGuardar').prop('disabled', true); }
            if (d.deuda > 0) h += '<div class="alert alert-' + (d.bloquea_mora ? 'danger' : 'warning') + ' mt-2 mb-0">Debe Bs ' + d.deuda.toFixed(2) + ' de mensualidades (' + d.meses_deuda.length + ' mes/es).' + (d.bloquea_mora ? ' La regla del colegio no permite pre-inscribir.' : '') + '</div>';
            if (d.bloquea_mora && d.deuda > 0) $('#btnGuardar').prop('disabled', true);
            if (d.observado) {
                h += '<div class="alert alert-danger mt-2 mb-0"><strong>ESTUDIANTE OBSERVADO</strong> (' + d.observado.tipo + '): ' + d.observado.motivo + '</div>';
                if (d.puede_override) $('#overrideBox').show(); else $('#btnGuardar').prop('disabled', true);
            }
            if (d.destino) { $('#curDestino').val(d.destino).trigger('change'); }
            $i.html(h).show();
        });
    });
    if ($('#estSelect').val()) $('#estSelect').trigger('change');
});
</script>
@endsection
