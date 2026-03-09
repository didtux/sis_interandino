@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header"><h4>Editar Servicio</h4></div>
                <div class="card-body">
                    <form action="{{ route('servicios.update', $servicio->serv_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label>Nombre *</label>
                            <input type="text" name="serv_nombre" class="form-control @error('serv_nombre') is-invalid @enderror" value="{{ old('serv_nombre', $servicio->serv_nombre) }}" required>
                            @error('serv_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Descripción</label>
                            <textarea name="serv_descripcion" class="form-control" rows="3">{{ old('serv_descripcion', $servicio->serv_descripcion) }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Costo (Bs.) *</label>
                            <input type="number" step="0.01" name="serv_costo" class="form-control @error('serv_costo') is-invalid @enderror" value="{{ old('serv_costo', $servicio->serv_costo) }}" required>
                            @error('serv_costo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Estado</label>
                            <select name="serv_estado" class="form-control">
                                <option value="1" {{ $servicio->serv_estado ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ !$servicio->serv_estado ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('servicios.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
