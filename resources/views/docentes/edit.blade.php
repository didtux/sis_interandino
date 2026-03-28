@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-chalkboard-teacher mr-2"></i>Editar Docente</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('docentes.update', $docente->doc_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código</label>
                                    <input type="text" class="form-control" value="{{ $docente->doc_codigo }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CI</label>
                                    <input type="text" name="doc_ci" class="form-control" value="{{ old('doc_ci', $docente->doc_ci) }}" maxlength="20">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres <span class="text-danger">*</span></label>
                                    <input type="text" name="doc_nombres" class="form-control" value="{{ old('doc_nombres', $docente->doc_nombres) }}" required maxlength="30">
                                    @error('doc_nombres')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos</label>
                                    <input type="text" name="doc_apellidos" class="form-control" value="{{ old('doc_apellidos', $docente->doc_apellidos) }}" maxlength="30">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Foto de Perfil</label>
                                    @if($docente->doc_foto)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $docente->doc_foto) }}" alt="Foto actual" style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:3px solid #667eea;">
                                            <label class="ml-3"><input type="checkbox" name="eliminar_foto" value="1"> Eliminar foto</label>
                                        </div>
                                    @endif
                                    <input type="file" name="doc_foto" class="form-control" accept="image/*">
                                    <small class="text-muted">Formatos: JPG, PNG. Máximo 2MB</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
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
