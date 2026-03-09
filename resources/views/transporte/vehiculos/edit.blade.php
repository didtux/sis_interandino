@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-car mr-2"></i>Editar Vehículo</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('vehiculos.update', $vehiculo->veh_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Número de Bus</label>
                                    <input type="text" name="veh_numero_bus" class="form-control" value="{{ $vehiculo->veh_numero_bus }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Placa *</label>
                                    <input type="text" name="veh_placa" class="form-control" value="{{ $vehiculo->veh_placa }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Marca *</label>
                                    <input type="text" name="veh_marca" class="form-control" value="{{ $vehiculo->veh_marca }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Modelo</label>
                                    <input type="text" name="veh_modelo" class="form-control" value="{{ $vehiculo->veh_modelo }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Año</label>
                                    <input type="number" name="veh_anio" class="form-control" value="{{ $vehiculo->veh_anio }}" min="1900" max="2100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Capacidad (pasajeros) *</label>
                                    <input type="number" name="veh_capacidad" class="form-control" value="{{ $vehiculo->veh_capacidad }}" required min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="text" name="veh_color" class="form-control" value="{{ $vehiculo->veh_color }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="veh_estado" class="form-control">
                                        <option value="1" {{ $vehiculo->veh_estado ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ !$vehiculo->veh_estado ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                        <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
