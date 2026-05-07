@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-plus mr-2"></i>Nuevo Curso</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('cursos.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Código *</label>
                                <input type="text" name="cur_codigo" class="form-control @error('cur_codigo') is-invalid @enderror"
                                       value="{{ old('cur_codigo') }}" required maxlength="20"
                                       placeholder="Ej. 1roPRIM">
                                @error('cur_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8 form-group mb-3">
                                <label class="font-weight-bold">Nombre *</label>
                                <input type="text" name="cur_nombre" class="form-control @error('cur_nombre') is-invalid @enderror"
                                       value="{{ old('cur_nombre') }}" required maxlength="20"
                                       placeholder="Ej. Primero Primaria">
                                @error('cur_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Abreviado</label>
                                <input type="text" name="cur_abreviado" class="form-control @error('cur_abreviado') is-invalid @enderror"
                                       value="{{ old('cur_abreviado') }}" maxlength="30"
                                       placeholder="Ej. 1° A PRIM">
                                @error('cur_abreviado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Nivel</label>
                                <select name="cur_nivel" class="form-control @error('cur_nivel') is-invalid @enderror">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach(['INICIAL','PRIMARIA','SECUNDARIA'] as $n)
                                        <option value="{{ $n }}" {{ old('cur_nivel') == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                @error('cur_nivel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Cupo</label>
                                <input type="number" name="cur_cupo" class="form-control @error('cur_cupo') is-invalid @enderror"
                                       value="{{ old('cur_cupo', 0) }}" min="0">
                                @error('cur_cupo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Orden</label>
                                <input type="number" name="cur_orden" class="form-control @error('cur_orden') is-invalid @enderror"
                                       value="{{ old('cur_orden', 0) }}" min="0">
                                <small class="text-muted">Posición en listas</small>
                                @error('cur_orden')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary-modern">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                            <a href="{{ route('cursos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
