@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-car mr-2"></i>Vehículos</h4>
                    <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Vehículo
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por número de bus, placa, marca o modelo..." value="{{ request('buscar') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Buscar</button>
                                @if(request('buscar'))
                                    <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                                @endif
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>N° Bus</th>
                                <th>Placa</th>
                                <th>Marca/Modelo</th>
                                <th>Año</th>
                                <th>Capacidad</th>
                                <th>Color</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehiculos as $v)
                                <tr>
                                    <td>{{ $v->veh_codigo }}</td>
                                    <td><strong>{{ $v->veh_numero_bus ?? '-' }}</strong></td>
                                    <td><strong>{{ $v->veh_placa }}</strong></td>
                                    <td>{{ $v->veh_marca }} {{ $v->veh_modelo }}</td>
                                    <td>{{ $v->veh_anio }}</td>
                                    <td><span class="badge badge-info">{{ $v->veh_capacidad }} pasajeros</span></td>
                                    <td>{{ $v->veh_color }}</td>
                                    <td>
                                        <span class="badge badge-{{ $v->veh_estado ? 'success' : 'danger' }}">
                                            {{ $v->veh_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vehiculos.edit', $v->veh_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vehiculos.destroy', $v->veh_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar vehículo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay vehículos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
