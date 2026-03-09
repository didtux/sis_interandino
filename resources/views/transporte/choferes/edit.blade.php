@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-id-card mr-2"></i>Editar Chofer</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('choferes.update', $chofer->chof_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres *</label>
                                    <input type="text" name="chof_nombres" class="form-control" value="{{ $chofer->chof_nombres }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos *</label>
                                    <input type="text" name="chof_apellidos" class="form-control" value="{{ $chofer->chof_apellidos }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CI *</label>
                                    <input type="text" name="chof_ci" class="form-control" value="{{ $chofer->chof_ci }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Licencia *</label>
                                    <input type="text" name="chof_licencia" class="form-control" value="{{ $chofer->chof_licencia }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="chof_telefono" class="form-control" value="{{ $chofer->chof_telefono }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Nacimiento</label>
                                    <input type="date" name="chof_fecha_nacimiento" class="form-control" value="{{ $chofer->chof_fecha_nacimiento }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="chof_estado" class="form-control">
                                        <option value="1" {{ $chofer->chof_estado ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ !$chofer->chof_estado ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="chof_direccion" class="form-control" value="{{ $chofer->chof_direccion }}">
                        </div>
                        <div class="form-group">
                            <label>Foto</label>
                            <input type="file" name="chof_foto" class="form-control" accept="image/*">
                            @if($chofer->chof_foto)
                                <small class="text-muted">Foto actual: <a href="{{ asset('storage/' . $chofer->chof_foto) }}" target="_blank">Ver</a></small>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                        <a href="{{ route('choferes.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
