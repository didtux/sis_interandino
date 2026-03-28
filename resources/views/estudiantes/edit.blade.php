@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Editar Estudiante</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('estudiantes.update', $estudiante->est_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group mb-3">
                            <label>Código *</label>
                            <input type="text" name="est_codigo" class="form-control" value="{{ $estudiante->est_codigo }}" readonly style="background-color: #e9ecef;">
                            <small class="text-muted">El código no puede ser modificado</small>
                        </div>

                        <div class="form-group mb-3">
                            <label>Curso *</label>
                            <select name="cur_codigo" class="form-control select2 @error('cur_codigo') is-invalid @enderror" required>
                                <option value="">Seleccione un curso</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->cur_codigo }}" {{ old('cur_codigo', $estudiante->cur_codigo) == $curso->cur_codigo ? 'selected' : '' }}>
                                        {{ $curso->cur_nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cur_codigo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>Nombres *</label>
                            <input type="text" name="est_nombres" class="form-control @error('est_nombres') is-invalid @enderror" value="{{ old('est_nombres', $estudiante->est_nombres) }}" required>
                            @error('est_nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>Apellidos</label>
                            <input type="text" name="est_apellidos" class="form-control @error('est_apellidos') is-invalid @enderror" value="{{ old('est_apellidos', $estudiante->est_apellidos) }}">
                            @error('est_apellidos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>CI</label>
                            <input type="text" name="est_ci" class="form-control @error('est_ci') is-invalid @enderror" value="{{ old('est_ci', $estudiante->est_ci) }}">
                            @error('est_ci')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>Lugar de Nacimiento</label>
                            <input type="text" name="est_lugarnac" class="form-control" value="{{ old('est_lugarnac', $estudiante->est_lugarnac) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" name="est_fechanac" class="form-control" value="{{ old('est_fechanac', $estudiante->est_fechanac) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label>U.E. de Procedencia</label>
                            <input type="text" name="est_ueprocedencia" class="form-control" value="{{ old('est_ueprocedencia', $estudiante->est_ueprocedencia) }}">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Código RUDE</label>
                                    <input type="text" name="est_rude" class="form-control" value="{{ old('est_rude', $estudiante->est_rude) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nro. Celular</label>
                                    <input type="text" name="est_celular" class="form-control" value="{{ old('est_celular', $estudiante->est_celular) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Preinscripción</label>
                            <input type="number" step="0.01" name="preinscripcion" class="form-control" value="{{ old('preinscripcion', $estudiante->preinscripcion) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label>Foto</label>
                            <input type="file" name="est_foto" class="form-control" accept="image/*">
                            @if($estudiante->est_foto)
                                <small class="text-muted">Foto actual: <a href="{{ asset('storage/' . $estudiante->est_foto) }}" target="_blank">Ver</a></small>
                            @endif
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                            <a href="{{ route('estudiantes.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>
@endsection
@endsection
