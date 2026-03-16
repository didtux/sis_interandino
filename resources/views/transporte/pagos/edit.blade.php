@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-money-bill mr-2"></i>Editar Pago de Transporte</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('pagos-transporte.update', $pago->tpago_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante {{ !$pago->estudiante ? '*' : '' }}</label>
                                    @if($pago->estudiante)
                                        <input type="text" class="form-control" value="{{ $pago->estudiante->est_nombres }} {{ $pago->estudiante->est_apellidos }}" readonly>
                                    @else
                                        <select name="est_codigo" class="form-control select2" required>
                                            <option value="">Seleccione un estudiante</option>
                                            @foreach($estudiantes as $est)
                                                <option value="{{ $est->est_codigo }}">
                                                    {{ $est->est_nombres }} {{ $est->est_apellidos }} - {{ $est->curso->cur_nombre ?? '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-danger">Este pago no tiene estudiante asignado. Seleccione uno para vincularlo.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" class="form-control" value="{{ ucfirst($pago->tpago_tipo) }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Monto (Bs.)</label>
                                    @if(!$pago->tpago_monto_modificado)
                                        <input type="number" name="tpago_monto" class="form-control" value="{{ $pago->tpago_monto }}" step="0.01" min="0">
                                        <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Solo se puede modificar el monto una vez</small>
                                    @else
                                        <input type="text" class="form-control" value="Bs. {{ number_format($pago->tpago_monto, 2) }}" readonly>
                                        <small class="text-muted"><i class="fas fa-lock"></i> El monto ya fue modificado anteriormente</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vigencia</label>
                                    <input type="text" class="form-control" value="{{ $pago->tpago_fecha_inicio }} - {{ $pago->tpago_fecha_fin }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado *</label>
                                    <select name="tpago_estado" class="form-control" required>
                                        <option value="vigente" {{ $pago->tpago_estado == 'vigente' ? 'selected' : '' }}>Vigente</option>
                                        <option value="vencido" {{ $pago->tpago_estado == 'vencido' ? 'selected' : '' }}>Vencido</option>
                                        <option value="cancelado" {{ $pago->tpago_estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
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
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Buscar estudiante...',
        allowClear: true
    });
});
</script>
@endsection
