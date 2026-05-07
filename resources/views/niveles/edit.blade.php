@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-edit mr-2"></i>Editar Nivel</h4></div>
                <div class="card-body">
                    <form action="{{ route('niveles.update', $nivel->niv_id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Nombre *</label>
                            <input type="text" name="niv_nombre" class="form-control @error('niv_nombre') is-invalid @enderror"
                                   value="{{ old('niv_nombre', $nivel->niv_nombre) }}" required maxlength="50">
                            @error('niv_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Abreviado</label>
                            <input type="text" name="niv_abreviado" class="form-control" value="{{ old('niv_abreviado', $nivel->niv_abreviado) }}" maxlength="20">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Orden</label>
                            <input type="number" name="niv_orden" class="form-control" value="{{ old('niv_orden', $nivel->niv_orden) }}" min="0">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Estado</label>
                            <select name="niv_estado" class="form-control">
                                <option value="1" {{ $nivel->niv_estado == 1 ? 'selected' : '' }}>ACTIVO</option>
                                <option value="0" {{ $nivel->niv_estado == 0 ? 'selected' : '' }}>INACTIVO</option>
                            </select>
                        </div>
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Actualizar</button>
                        <a href="{{ route('niveles.index') }}" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
