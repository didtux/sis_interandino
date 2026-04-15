@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-edit mr-2"></i>Editar Curso</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('cursos.update', $curso->cur_id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Código</label>
                            <input type="text" class="form-control" value="{{ $curso->cur_codigo }}" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Nombre *</label>
                            <input type="text" name="cur_nombre" class="form-control @error('cur_nombre') is-invalid @enderror"
                                   value="{{ old('cur_nombre', $curso->cur_nombre) }}" required maxlength="20">
                            @error('cur_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary-modern">
                                <i class="fas fa-save mr-1"></i>Actualizar
                            </button>
                            <a href="{{ route('cursos.show', $curso->cur_id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
