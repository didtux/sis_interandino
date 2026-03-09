@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-user-plus mr-2"></i>Nuevo Usuario</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('usuarios.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código <span class="text-danger">*</span></label>
                                    <input type="text" name="us_codigo" class="form-control @error('us_codigo') is-invalid @enderror" value="{{ old('us_codigo') }}" required>
                                    @error('us_codigo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CI <span class="text-danger">*</span></label>
                                    <input type="text" name="us_ci" class="form-control @error('us_ci') is-invalid @enderror" value="{{ old('us_ci') }}" required>
                                    @error('us_ci')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres <span class="text-danger">*</span></label>
                                    <input type="text" name="us_nombres" class="form-control @error('us_nombres') is-invalid @enderror" value="{{ old('us_nombres') }}" required>
                                    @error('us_nombres')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos <span class="text-danger">*</span></label>
                                    <input type="text" name="us_apellidos" class="form-control @error('us_apellidos') is-invalid @enderror" value="{{ old('us_apellidos') }}" required>
                                    @error('us_apellidos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Usuario <span class="text-danger">*</span></label>
                                    <input type="text" name="us_user" class="form-control @error('us_user') is-invalid @enderror" value="{{ old('us_user') }}" required>
                                    @error('us_user')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" name="us_pass" class="form-control @error('us_pass') is-invalid @enderror" required>
                                    @error('us_pass')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rol <span class="text-danger">*</span></label>
                                    <select name="rol_id" class="form-control @error('rol_id') is-invalid @enderror" required>
                                        <option value="">Seleccione un rol</option>
                                        <option value="1" {{ old('rol_id') == 1 ? 'selected' : '' }}>Administrador</option>
                                        <option value="2" {{ old('rol_id') == 2 ? 'selected' : '' }}>Usuario</option>
                                    </select>
                                    @error('rol_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary-modern">
                                <i class="fas fa-save mr-1"></i>Guardar Usuario
                            </button>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
