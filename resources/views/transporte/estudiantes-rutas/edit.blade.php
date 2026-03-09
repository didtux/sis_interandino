@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-users mr-2"></i>Editar Asignación de Estudiante</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('estudiantes-rutas.update', $asignacion->ter_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante</label>
                                    <input type="text" class="form-control" value="{{ $asignacion->estudiante->est_nombres }} {{ $asignacion->estudiante->est_apellidos }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ruta *</label>
                                    <select name="ruta_codigo" class="form-control select2" required>
                                        @foreach($rutas as $r)
                                            @php
                                                $asignacion = $r->asignaciones->where('asig_estado', 1)->first();
                                                $vehiculoInfo = '';
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    if ($asignacion->vehiculo->veh_numero_bus) {
                                                        $vehiculoInfo = ' - Bus ' . $asignacion->vehiculo->veh_numero_bus;
                                                    }
                                                    $vehiculoInfo .= ' - ' . $asignacion->vehiculo->veh_placa;
                                                }
                                            @endphp
                                            <option value="{{ $r->ruta_codigo }}" {{ $asignacion->ruta_codigo == $r->ruta_codigo ? 'selected' : '' }}>
                                                {{ $r->ruta_nombre }}{{ $vehiculoInfo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Pago de Transporte *</label>
                                    <select name="tpago_codigo" class="form-control select2" required>
                                        @foreach($pagos as $p)
                                            <option value="{{ $p->tpago_codigo }}" {{ $asignacion->tpago_codigo == $p->tpago_codigo ? 'selected' : '' }}>
                                                {{ $p->estudiante->est_nombres }} {{ $p->estudiante->est_apellidos }} - 
                                                {{ ucfirst($p->tpago_tipo) }} - 
                                                Bs. {{ number_format($p->tpago_monto, 2) }} - 
                                                {{ $p->tpago_estado }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="ter_estado" class="form-control">
                                        <option value="1" {{ $asignacion->ter_estado ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ !$asignacion->ter_estado ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección de Recogida</label>
                            <input type="text" name="ter_direccion_recogida" class="form-control" value="{{ $asignacion->ter_direccion_recogida }}">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                        <a href="{{ route('estudiantes-rutas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
