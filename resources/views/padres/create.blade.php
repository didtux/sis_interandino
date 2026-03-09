@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header"><h4>Nuevo Padre de Familia</h4></div>
        <div class="card-body">
            <form action="{{ route('padres.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label>Código *</label>
                    <input type="text" name="pfam_codigo" id="pfam_codigo" class="form-control @error('pfam_codigo') is-invalid @enderror" value="{{ $siguienteCodigo }}" readonly required>
                    @error('pfam_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Código generado automáticamente</small>
                </div>
                <div class="mb-3">
                    <label>CI *</label>
                    <input type="text" name="pfam_ci" class="form-control @error('pfam_ci') is-invalid @enderror" required>
                    @error('pfam_ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>Nombres *</label>
                    <input type="text" name="pfam_nombres" class="form-control @error('pfam_nombres') is-invalid @enderror" required>
                    @error('pfam_nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>Domicilio</label>
                    <input type="text" name="pfam_domicilio" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Correo</label>
                    <input type="email" name="pfam_correo" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Celular</label>
                    <input type="text" name="pfam_numeroscelular" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Foto</label>
                    <input type="file" name="pfam_foto" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('padres.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
