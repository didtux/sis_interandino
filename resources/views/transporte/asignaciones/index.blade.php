@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-tasks mr-2"></i>Asignaciones Chofer-Vehículo-Ruta</h4>
                    <a href="{{ route('asignaciones-transporte.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Asignación
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Chofer</th>
                                <th>Vehículo</th>
                                <th>Ruta</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asignaciones as $a)
                                <tr>
                                    <td>{{ $a->asig_codigo }}</td>
                                    <td>{{ $a->chofer->chof_nombres ?? 'N/A' }} {{ $a->chofer->chof_apellidos ?? '' }}</td>
                                    <td>{{ $a->vehiculo->veh_placa ?? 'N/A' }}</td>
                                    <td>{{ $a->ruta->ruta_nombre ?? 'N/A' }}</td>
                                    <td>{{ $a->asig_fecha_inicio }}</td>
                                    <td>{{ $a->asig_fecha_fin ?? 'Indefinido' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $a->asig_estado ? 'success' : 'danger' }}">
                                            {{ $a->asig_estado ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('asignaciones-transporte.edit', $a->asig_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('asignaciones-transporte.destroy', $a->asig_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar asignación?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No hay asignaciones registradas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
