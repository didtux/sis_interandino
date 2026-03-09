@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-edit mr-2"></i>Editar Caso</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('psicopedagogia.update', $caso->psico_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="alert alert-info">
                            <strong>Estudiante:</strong> {{ $caso->estudiante->est_nombres }} {{ $caso->estudiante->est_apellidos }}<br>
                            <strong>Curso:</strong> {{ $caso->estudiante->curso->cur_nombre ?? 'N/A' }}
                        </div>

                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="psico_fecha" class="form-control" value="{{ $caso->psico_fecha->format('Y-m-d') }}" required>
                        </div>

                        <div class="form-group">
                            <label>Caso <span class="text-danger">*</span></label>
                            <textarea name="psico_caso" class="form-control" rows="4" required>{{ $caso->psico_caso }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Solución</label>
                            <textarea name="psico_solucion" class="form-control" rows="3">{{ $caso->psico_solucion }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Acuerdo</label>
                            <textarea name="psico_acuerdo" class="form-control" rows="3">{{ $caso->psico_acuerdo }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Acuerdo <span class="text-danger">*</span></label>
                            <select name="psico_tipo_acuerdo" class="form-control" required>
                                <option value="NINGUNO" {{ $caso->psico_tipo_acuerdo == 'NINGUNO' ? 'selected' : '' }}>Ninguno</option>
                                <option value="VERBAL" {{ $caso->psico_tipo_acuerdo == 'VERBAL' ? 'selected' : '' }}>Verbal</option>
                                <option value="ESCRITO" {{ $caso->psico_tipo_acuerdo == 'ESCRITO' ? 'selected' : '' }}>Escrito</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea name="psico_observaciones" class="form-control" rows="2">{{ $caso->psico_observaciones }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                        <a href="{{ route('psicopedagogia.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
