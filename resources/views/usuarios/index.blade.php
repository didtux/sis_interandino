@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-users-cog mr-2"></i>Usuarios del Sistema</h4>
                    <a href="{{ route('usuarios.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Usuario
                    </a>
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
                                    <th>CI</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usuarios as $usuario)
                                    <tr>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $usuario->us_codigo }}</span>
                                        </td>
                                        <td data-label="CI">{{ $usuario->us_ci }}</td>
                                        <td data-label="Nombres">{{ $usuario->us_nombres }}</td>
                                        <td data-label="Apellidos">{{ $usuario->us_apellidos }}</td>
                                        <td data-label="Usuario">
                                            <i class="fas fa-user mr-1"></i>{{ $usuario->us_user }}
                                        </td>
                                        <td data-label="Rol">
                                            <span class="modern-badge badge-warning-modern">
                                                {{ $usuario->rol_id == 1 ? 'Administrador' : 'Usuario' }}
                                            </span>
                                        </td>
                                        <td data-label="Estado">
                                            @if($usuario->us_visible == 1)
                                                <span class="modern-badge badge-success-modern">
                                                    <i class="fas fa-check mr-1"></i>Activo
                                                </span>
                                            @else
                                                <span class="modern-badge" style="background-color: #f8d7da; color: #721c24;">
                                                    <i class="fas fa-times mr-1"></i>Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('usuarios.show', $usuario->us_id) }}" class="btn btn-action btn-action-view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('usuarios.edit', $usuario->us_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($usuario->us_visible == 1)
                                                    <form action="{{ route('usuarios.destroy', $usuario->us_id) }}" method="POST" style="display:inline;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de desactivar este usuario?')" title="Desactivar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-users-cog"></i>
                                                <h5>No hay usuarios registrados</h5>
                                                <p>Comienza agregando el primer usuario</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $usuarios->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
