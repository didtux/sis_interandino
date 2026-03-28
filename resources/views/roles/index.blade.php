@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-shield-alt mr-2"></i>Roles del Sistema</h4>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Rol
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Usuarios</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $rol)
                                    <tr>
                                        <td data-label="ID">{{ $rol->rol_id }}</td>
                                        <td data-label="Nombre">
                                            <span class="modern-badge badge-primary-modern">{{ $rol->rol_nombre }}</span>
                                        </td>
                                        <td data-label="Descripción">{{ $rol->rol_descripcion }}</td>
                                        <td data-label="Usuarios">
                                            <span class="badge badge-info">{{ $rol->usuarios_count }}</span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('roles.edit', $rol->rol_id) }}" class="btn btn-action btn-action-edit" title="Editar Permisos">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                                @if($rol->rol_id != 1)
                                                    <form action="{{ route('roles.destroy', $rol->rol_id) }}" method="POST" style="display:inline;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Eliminar este rol?')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-shield-alt"></i>
                                                <h5>No hay roles registrados</h5>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
