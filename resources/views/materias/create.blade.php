@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header"><h4>Nueva Materia</h4></div>
        <div class="card-body">
            <form action="{{ route('materias.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label>Código *</label>
                    <input type="text" name="mat_codigo" class="form-control @error('mat_codigo') is-invalid @enderror" required>
                    @error('mat_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="mat_nombre" class="form-control @error('mat_nombre') is-invalid @enderror" required>
                    @error('mat_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('materias.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
