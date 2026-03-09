@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header"><h4>Registrar Pago de Servicio</h4></div>
                <div class="card-body">
                    <form action="{{ route('pagos-servicios.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Servicio *</label>
                            <select name="serv_codigo" id="serv_codigo" class="form-control select2 @error('serv_codigo') is-invalid @enderror" required>
                                <option value="">Seleccione un servicio</option>
                                @foreach($servicios as $servicio)
                                    <option value="{{ $servicio->serv_codigo }}" data-costo="{{ $servicio->serv_costo }}">
                                        {{ $servicio->serv_nombre }} - Bs. {{ number_format($servicio->serv_costo, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('serv_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Estudiante *</label>
                            <select name="est_codigo" id="est_codigo" class="form-control select2 @error('est_codigo') is-invalid @enderror" required>
                                <option value="">Seleccione un estudiante</option>
                                @foreach($estudiantes as $estudiante)
                                    <option value="{{ $estudiante->est_codigo }}">{{ $estudiante->est_nombres }} {{ $estudiante->est_apellidos }}</option>
                                @endforeach
                            </select>
                            @error('est_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Padre de Familia</label>
                            <select name="pfam_codigo" id="pfam_codigo" class="form-control select2">
                                <option value="">Seleccione un padre</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Monto (Bs.) *</label>
                            <input type="number" step="0.01" name="pserv_monto" id="pserv_monto" class="form-control @error('pserv_monto') is-invalid @enderror" value="{{ old('pserv_monto') }}" required>
                            @error('pserv_monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Descuento (Bs.)</label>
                            <input type="number" step="0.01" name="pserv_descuento" class="form-control" value="{{ old('pserv_descuento', 0) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>Observación</label>
                            <textarea name="pserv_observacion" class="form-control" rows="3">{{ old('pserv_observacion') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('pagos-servicios.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        width: '100%'
    });

    $('#serv_codigo').on('change', function() {
        const costo = $(this).find(':selected').data('costo');
        $('#pserv_monto').val(costo || '');
    });

    $('#est_codigo').on('change', function() {
        const estCodigo = $(this).val();
        if (estCodigo) {
            $.get('/api/estudiante-padres/' + estCodigo, function(padres) {
                $('#pfam_codigo').empty().append('<option value="">Seleccione un padre</option>');
                padres.forEach(function(padre) {
                    $('#pfam_codigo').append('<option value="' + padre.pfam_codigo + '">' + padre.pfam_nombres + '</option>');
                });
            });
        }
    });
});
</script>
@endsection
