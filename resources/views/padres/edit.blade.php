@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header"><h4>Editar Padre de Familia</h4></div>
        <div class="card-body">
            <form action="{{ route('padres.update', $padre->pfam_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label>Código *</label>
                    <input type="text" name="pfam_codigo" class="form-control @error('pfam_codigo') is-invalid @enderror" value="{{ $padre->pfam_codigo }}" readonly required>
                    @error('pfam_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>CI *</label>
                    <input type="text" name="pfam_ci" class="form-control @error('pfam_ci') is-invalid @enderror" value="{{ $padre->pfam_ci }}" required>
                    @error('pfam_ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>Nombres *</label>
                    <input type="text" name="pfam_nombres" class="form-control @error('pfam_nombres') is-invalid @enderror" value="{{ $padre->pfam_nombres }}" required>
                    @error('pfam_nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>Parentesco</label>
                    <select name="pfam_parentesco" class="form-control">
                        <option value="">Seleccione...</option>
                        <option value="Padre" {{ $padre->pfam_parentesco == 'Padre' ? 'selected' : '' }}>Padre</option>
                        <option value="Madre" {{ $padre->pfam_parentesco == 'Madre' ? 'selected' : '' }}>Madre</option>
                        <option value="Hermano/a" {{ $padre->pfam_parentesco == 'Hermano/a' ? 'selected' : '' }}>Hermano/a</option>
                        <option value="Tutor/a" {{ $padre->pfam_parentesco == 'Tutor/a' ? 'selected' : '' }}>Tutor/a</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Domicilio</label>
                    <input type="text" name="pfam_domicilio" class="form-control" value="{{ $padre->pfam_domicilio }}">
                </div>
                <div class="mb-3">
                    <label>Correo</label>
                    <input type="email" name="pfam_correo" class="form-control" value="{{ $padre->pfam_correo }}">
                </div>
                <div class="mb-3">
                    <label>Celular</label>
                    <input type="text" name="pfam_numeroscelular" class="form-control" value="{{ $padre->pfam_numeroscelular }}">
                </div>
                <div class="mb-3">
                    <label>Foto</label>
                    <input type="file" name="pfam_foto" class="form-control" accept="image/*">
                    @if($padre->pfam_foto)
                        <small class="text-muted">Foto actual: <a href="{{ asset('storage/' . $padre->pfam_foto) }}" target="_blank">Ver</a></small>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('padres.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
