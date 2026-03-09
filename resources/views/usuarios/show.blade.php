@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-user mr-2"></i>Detalle del Usuario</h4>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Código:</label>
                                <p class="form-control-plaintext">
                                    <span class="modern-badge badge-primary-modern">{{ $usuario->us_codigo }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">CI:</label>
                                <p class="form-control-plaintext">{{ $usuario->us_ci }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombres:</label>
                                <p class="form-control-plaintext">{{ $usuario->us_nombres }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Apellidos:</label>
                                <p class="form-control-plaintext">{{ $usuario->us_apellidos }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Usuario:</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-user mr-1"></i>{{ $usuario->us_user }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Rol:</label>
                                <p class="form-control-plaintext">
                                    <span class="modern-badge badge-warning-modern">
                                        {{ $usuario->rol_id == 1 ? 'Administrador' : 'Usuario' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Estado:</label>
                                <p class="form-control-plaintext">
                                    @if($usuario->us_visible == 1)
                                        <span class="modern-badge badge-success-modern">
                                            <i class="fas fa-check mr-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="modern-badge" style="background-color: #f8d7da; color: #721c24;">
                                            <i class="fas fa-times mr-1"></i>Inactivo
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <a href="{{ route('usuarios.edit', $usuario->us_id) }}" class="btn btn-primary-modern">
                            <i class="fas fa-edit mr-1"></i>Editar Usuario
                        </a>
                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Volver al Listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
