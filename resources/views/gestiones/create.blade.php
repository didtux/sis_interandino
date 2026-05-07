@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-plus mr-2"></i>Nueva Gestión</h4></div>
                <div class="card-body">
                    <form action="{{ route('gestiones.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Año *</label>
                            <input type="text" name="ges_anio" class="form-control @error('ges_anio') is-invalid @enderror"
                                   value="{{ old('ges_anio') }}" required maxlength="10" placeholder="2027">
                            @error('ges_anio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Nombre *</label>
                            <input type="text" name="ges_nombre" class="form-control" value="{{ old('ges_nombre') }}" required maxlength="80" placeholder="GESTIÓN 2027">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Abreviado</label>
                            <input type="text" name="ges_abreviado" class="form-control" value="{{ old('ges_abreviado') }}" maxlength="20">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Estado</label>
                            <select name="ges_estado" class="form-control">
                                <option value="0" selected>INACTIVO</option>
                                <option value="1">ACTIVO (desactiva las demás)</option>
                            </select>
                        </div>
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                        <a href="{{ route('gestiones.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
