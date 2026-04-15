@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header"><h4>Editar Materia</h4></div>
        <div class="card-body">
            <form action="{{ route('materias.update', $materia->mat_id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label>Código</label>
                    <input type="text" class="form-control" value="{{ $materia->mat_codigo }}" readonly>
                </div>
                <div class="mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="mat_nombre" class="form-control @error('mat_nombre') is-invalid @enderror" value="{{ old('mat_nombre', $materia->mat_nombre) }}" required>
                    @error('mat_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label>Campo / Área Curricular</label>
                        <input type="text" name="mat_campo" class="form-control" value="{{ old('mat_campo', $materia->mat_campo) }}" placeholder="Ej: COMUNIDAD Y SOCIEDAD..." list="campos_list">
                        <datalist id="campos_list">
                            <option value="COMUNIDAD Y SOCIEDAD">
                            <option value="CIENCIA Y TECNOLOGÍA">
                            <option value="VIDA TIERRA Y TERRITORIO">
                            <option value="COSMOS Y PENSAMIENTO">
                        </datalist>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Orden</label>
                        <input type="number" name="mat_orden" class="form-control" value="{{ old('mat_orden', $materia->mat_orden) }}" min="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('materias.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
