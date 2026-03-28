@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-concierge-bell mr-2"></i>Servicios</h4>
                    @puede('servicios', 'crear')
                    <a href="{{ route('servicios.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Servicio
                    </a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Costo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($servicios as $servicio)
                                    <tr>
                                        <td data-label="Código">{{ $servicio->serv_codigo }}</td>
                                        <td data-label="Nombre"><strong>{{ $servicio->serv_nombre }}</strong></td>
                                        <td data-label="Descripción">{{ Str::limit($servicio->serv_descripcion, 50) }}</td>
                                        <td data-label="Costo">Bs. {{ number_format($servicio->serv_costo, 2) }}</td>
                                        <td data-label="Estado">
                                            <span class="badge badge-{{ $servicio->serv_estado ? 'success' : 'danger' }}">
                                                {{ $servicio->serv_estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                @puede('servicios', 'editar')
                                                <a href="{{ route('servicios.edit', $servicio->serv_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('servicios', 'eliminar')
                                                <form action="{{ route('servicios.destroy', $servicio->serv_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Eliminar servicio?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="fas fa-concierge-bell"></i>
                                                <h5>No hay servicios registrados</h5>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $servicios->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
