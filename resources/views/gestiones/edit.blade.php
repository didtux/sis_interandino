@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-edit mr-2"></i>Editar Gestión</h4></div>
                <div class="card-body">
                    <form action="{{ route('gestiones.update', $gestion->ges_id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Año *</label>
                            <input type="text" name="ges_anio" class="form-control @error('ges_anio') is-invalid @enderror"
                                   value="{{ old('ges_anio', $gestion->ges_anio) }}" required maxlength="10">
                            @error('ges_anio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Nombre *</label>
                            <input type="text" name="ges_nombre" class="form-control" value="{{ old('ges_nombre', $gestion->ges_nombre) }}" required maxlength="80">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Abreviado</label>
                            <input type="text" name="ges_abreviado" class="form-control" value="{{ old('ges_abreviado', $gestion->ges_abreviado) }}" maxlength="20">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Estado</label>
                            <select name="ges_estado" class="form-control">
                                <option value="1" {{ $gestion->ges_estado == 1 ? 'selected' : '' }}>ACTIVO (desactiva las demás)</option>
                                <option value="0" {{ $gestion->ges_estado == 0 ? 'selected' : '' }}>INACTIVO</option>
                            </select>
                        </div>
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Actualizar</button>
                        <a href="{{ route('gestiones.index') }}" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
