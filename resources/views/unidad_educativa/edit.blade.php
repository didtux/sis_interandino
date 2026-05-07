@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-school mr-2"></i>Unidad Educativa — Editar Perfil</h4></div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
                    <form action="{{ route('unidad-educativa.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="row">
                            <div class="col-md-4 form-group mb-3 text-center">
                                <label class="font-weight-bold d-block">Logo</label>
                                @if($config->config_logo)
                                    <img src="{{ asset('storage/' . $config->config_logo) }}" style="max-width:160px;max-height:160px;border:1px solid #ccc;padding:4px;background:#fff;">
                                @else
                                    <div style="width:160px;height:160px;border:1px dashed #aaa;line-height:160px;color:#888;margin:auto;">Sin logo</div>
                                @endif
                                <input type="file" name="config_logo" class="form-control mt-2" accept="image/*">
                            </div>
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label>Denominación</label>
                                    <input type="text" name="config_denominacion" class="form-control" value="{{ old('config_denominacion', $config->config_denominacion) }}" maxlength="200">
                                </div>
                                <div class="form-group mb-3">
                                    <label>Nombre de la Unidad Educativa *</label>
                                    <input type="text" name="config_nombre_ue" class="form-control @error('config_nombre_ue') is-invalid @enderror"
                                           value="{{ old('config_nombre_ue', $config->config_nombre_ue) }}" required maxlength="200">
                                    @error('config_nombre_ue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group mb-3">
                                    <label>Dirección</label>
                                    <input type="text" name="config_direccion" class="form-control" value="{{ old('config_direccion', $config->config_direccion) }}" maxlength="255">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label>Teléfono</label>
                                        <input type="text" name="config_telefono" class="form-control" value="{{ old('config_telefono', $config->config_telefono) }}" maxlength="50">
                                    </div>
                                    <div class="col-md-6 form-group mb-3">
                                        <label>Ciudad</label>
                                        <input type="text" name="config_ciudad" class="form-control" value="{{ old('config_ciudad', $config->config_ciudad) }}" maxlength="100">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" name="config_email" class="form-control" value="{{ old('config_email', $config->config_email) }}" maxlength="100">
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
