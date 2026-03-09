@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tasks mr-2"></i>Nueva Asignación</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('asignaciones-transporte.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Chofer *</label>
                                    <select name="chof_codigo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($choferes as $c)
                                            <option value="{{ $c->chof_codigo }}">{{ $c->chof_nombres }} {{ $c->chof_apellidos }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vehículo *</label>
                                    <select name="veh_codigo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($vehiculos as $v)
                                            <option value="{{ $v->veh_codigo }}">
                                                @if($v->veh_numero_bus) Bus {{ $v->veh_numero_bus }} - @endif{{ $v->veh_placa }} - {{ $v->veh_marca }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ruta *</label>
                                    <select name="ruta_codigo" id="ruta-select" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($rutas as $r)
                                            @php
                                                $asignacion = $r->asignaciones->where('asig_estado', 1)->first();
                                                $vehiculoInfo = '';
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    $vehiculoInfo = ' - ';
                                                    if ($asignacion->vehiculo->veh_numero_bus) {
                                                        $vehiculoInfo .= 'Bus ' . $asignacion->vehiculo->veh_numero_bus . ' - ';
                                                    }
                                                    $vehiculoInfo .= $asignacion->vehiculo->veh_marca . ' ' . $asignacion->vehiculo->veh_placa;
                                                }
                                            @endphp
                                            <option value="{{ $r->ruta_codigo }}" data-vehiculo="{{ $asignacion && $asignacion->vehiculo ? $asignacion->vehiculo->veh_codigo : '' }}">
                                                {{ $r->ruta_nombre }}{{ $vehiculoInfo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha Inicio *</label>
                                    <input type="date" name="asig_fecha_inicio" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha Fin</label>
                                    <input type="date" name="asig_fecha_fin" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="{{ route('asignaciones-transporte.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
        width: '100%'
    });
    
    // Auto-seleccionar vehículo cuando se selecciona una ruta
    $('#ruta-select').on('change', function() {
        var vehiculoCodigo = $(this).find(':selected').data('vehiculo');
        if (vehiculoCodigo) {
            $('select[name="veh_codigo"]').val(vehiculoCodigo).trigger('change');
        }
    });
});
</script>
@endsection
