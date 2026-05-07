@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-edit mr-2"></i>Editar Curso</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('cursos.update', $curso->cur_id) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Código</label>
                                <input type="text" class="form-control" value="{{ $curso->cur_codigo }}" readonly>
                            </div>
                            <div class="col-md-8 form-group mb-3">
                                <label class="font-weight-bold">Nombre *</label>
                                <input type="text" name="cur_nombre" class="form-control @error('cur_nombre') is-invalid @enderror"
                                       value="{{ old('cur_nombre', $curso->cur_nombre) }}" required maxlength="20">
                                @error('cur_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Abreviado</label>
                                <input type="text" name="cur_abreviado" class="form-control @error('cur_abreviado') is-invalid @enderror"
                                       value="{{ old('cur_abreviado', $curso->cur_abreviado) }}" maxlength="30"
                                       placeholder="Ej. 1° A PRIM">
                                @error('cur_abreviado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Nivel</label>
                                <select name="cur_nivel" class="form-control @error('cur_nivel') is-invalid @enderror">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach(['INICIAL','PRIMARIA','SECUNDARIA'] as $n)
                                        <option value="{{ $n }}" {{ old('cur_nivel', $curso->cur_nivel) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                @error('cur_nivel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Cupo</label>
                                <input type="number" name="cur_cupo" class="form-control @error('cur_cupo') is-invalid @enderror"
                                       value="{{ old('cur_cupo', $curso->cur_cupo) }}" min="0">
                                @error('cur_cupo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Orden</label>
                                <input type="number" name="cur_orden" class="form-control @error('cur_orden') is-invalid @enderror"
                                       value="{{ old('cur_orden', $curso->cur_orden) }}" min="0">
                                <small class="text-muted">Posición en listas (PreKinder=1, 6toSEC=25)</small>
                                @error('cur_orden')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="font-weight-bold">Estado</label>
                                <select name="cur_visible" class="form-control">
                                    <option value="1" {{ old('cur_visible', $curso->cur_visible) == 1 ? 'selected' : '' }}>ACTIVO</option>
                                    <option value="0" {{ old('cur_visible', $curso->cur_visible) == 0 ? 'selected' : '' }}>INACTIVO</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary-modern">
                                <i class="fas fa-save mr-1"></i>Actualizar
                            </button>
                            <a href="{{ route('cursos.index') }}" class="btn btn-secondary">
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
