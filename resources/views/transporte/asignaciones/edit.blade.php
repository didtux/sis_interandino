@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tasks mr-2"></i>Editar Asignación</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('asignaciones-transporte.update', $asignacion->asig_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Chofer *</label>
                                    <select name="chof_codigo" class="form-control select2" required>
                                        @foreach($choferes as $c)
                                            <option value="{{ $c->chof_codigo }}" {{ $asignacion->chof_codigo == $c->chof_codigo ? 'selected' : '' }}>
                                                {{ $c->chof_nombres }} {{ $c->chof_apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vehículo *</label>
                                    <select name="veh_codigo" class="form-control select2" required>
                                        @foreach($vehiculos as $v)
                                            <option value="{{ $v->veh_codigo }}" {{ $asignacion->veh_codigo == $v->veh_codigo ? 'selected' : '' }}>
                                                {{ $v->veh_placa }} - {{ $v->veh_marca }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ruta *</label>
                                    <select name="ruta_codigo" class="form-control select2" required>
                                        @foreach($rutas as $r)
                                            <option value="{{ $r->ruta_codigo }}" {{ $asignacion->ruta_codigo == $r->ruta_codigo ? 'selected' : '' }}>
                                                {{ $r->ruta_nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Inicio *</label>
                                    <input type="date" name="asig_fecha_inicio" class="form-control" value="{{ $asignacion->asig_fecha_inicio }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Fin</label>
                                    <input type="date" name="asig_fecha_fin" class="form-control" value="{{ $asignacion->asig_fecha_fin }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="asig_estado" class="form-control">
                                        <option value="1" {{ $asignacion->asig_estado ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ !$asignacion->asig_estado ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
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
});
</script>
@endsection
