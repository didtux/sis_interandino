@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-chalkboard-teacher mr-2"></i>Nuevo Docente</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('docentes.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código</label>
                                    <input type="text" class="form-control" value="{{ $codigoGenerado }}" readonly>
                                    <small class="text-muted">Código generado automáticamente</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CI</label>
                                    <input type="text" name="doc_ci" class="form-control" value="{{ old('doc_ci') }}" maxlength="20">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres <span class="text-danger">*</span></label>
                                    <input type="text" name="doc_nombres" class="form-control" value="{{ old('doc_nombres') }}" required maxlength="30">
                                    @error('doc_nombres')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos</label>
                                    <input type="text" name="doc_apellidos" class="form-control" value="{{ old('doc_apellidos') }}" maxlength="30">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Foto de Perfil</label>
                                    <input type="file" name="doc_foto" class="form-control" accept="image/*">
                                    <small class="text-muted">Formatos: JPG, PNG. Máximo 2MB</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <a href="{{ route('docentes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
