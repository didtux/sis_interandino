@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-users mr-2"></i>Estudiantes en Rutas</h4>
                    <a href="{{ route('estudiantes-rutas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Asignar Estudiante
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" name="buscar" class="form-control" placeholder="Buscar estudiante por nombre, apellido o CI..." value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="ruta_codigo" class="form-control">
                                    <option value="">Todas las rutas</option>
                                    @foreach($rutas as $r)
                                        @php
                                            $asignacion = $r->asignaciones ? $r->asignaciones->where('asig_estado', 1)->first() : null;
                                            $vehiculoInfo = '';
                                            if ($asignacion && $asignacion->vehiculo) {
                                                if ($asignacion->vehiculo->veh_numero_bus) {
                                                    $vehiculoInfo = ' - Bus ' . $asignacion->vehiculo->veh_numero_bus;
                                                }
                                                $vehiculoInfo .= ' - ' . $asignacion->vehiculo->veh_placa;
                                            }
                                        @endphp
                                        <option value="{{ $r->ruta_codigo }}" {{ request('ruta_codigo') == $r->ruta_codigo ? 'selected' : '' }}>
                                            {{ $r->ruta_nombre }}{{ $vehiculoInfo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-search"></i> Buscar</button>
                                @if(request('buscar') || request('ruta_codigo'))
                                    <a href="{{ route('estudiantes-rutas.index') }}" class="btn btn-secondary btn-block mt-1"><i class="fas fa-times"></i> Limpiar</a>
                                @endif
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Ruta</th>
                                <th>N° Bus</th>
                                <th>Dirección Recogida</th>
                                <th>Estado Pago</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asignaciones as $a)
                                @php
                                    $asignacionRuta = $a->ruta && $a->ruta->asignaciones ? $a->ruta->asignaciones->where('asig_estado', 1)->first() : null;
                                    $numeroBus = $asignacionRuta && $asignacionRuta->vehiculo && $asignacionRuta->vehiculo->veh_numero_bus 
                                        ? $asignacionRuta->vehiculo->veh_numero_bus 
                                        : '-';
                                @endphp
                                <tr>
                                    <td>{{ $a->ter_codigo }}</td>
                                    <td><strong>{{ $a->estudiante->est_nombres ?? 'N/A' }} {{ $a->estudiante->est_apellidos ?? '' }}</strong></td>
                                    <td>{{ $a->ruta->ruta_nombre ?? 'N/A' }}</td>
                                    <td><strong>{{ $numeroBus }}</strong></td>
                                    <td>{{ $a->ter_direccion_recogida ?? 'N/A' }}</td>
                                    <td>
                                        @if($a->pago && $a->pago->tpago_estado == 'vigente')
                                            <span class="badge badge-success">Vigente</span>
                                        @elseif($a->pago && $a->pago->tpago_estado == 'vencido')
                                            <span class="badge badge-danger">Vencido</span>
                                        @elseif($a->pago && $a->pago->tpago_estado == 'cancelado')
                                            <span class="badge badge-secondary">Cancelado</span>
                                        @else
                                            <span class="badge badge-warning">Sin pago</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $a->ter_estado ? 'success' : 'danger' }}">
                                            {{ $a->ter_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('estudiantes-rutas.edit', $a->ter_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('estudiantes-rutas.destroy', $a->ter_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar asignación?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No hay estudiantes asignados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
