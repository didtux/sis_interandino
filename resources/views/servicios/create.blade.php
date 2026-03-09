@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header"><h4>Nuevo Servicio</h4></div>
                <div class="card-body">
                    <form action="{{ route('servicios.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Nombre *</label>
                            <input type="text" name="serv_nombre" class="form-control @error('serv_nombre') is-invalid @enderror" value="{{ old('serv_nombre') }}" required>
                            @error('serv_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label>Descripción</label>
                            <textarea name="serv_descripcion" class="form-control" rows="3">{{ old('serv_descripcion') }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Costo (Bs.) *</label>
                            <input type="number" step="0.01" name="serv_costo" class="form-control @error('serv_costo') is-invalid @enderror" value="{{ old('serv_costo') }}" required>
                            @error('serv_costo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('servicios.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
