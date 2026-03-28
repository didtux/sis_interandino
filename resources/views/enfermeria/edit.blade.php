@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-heartbeat mr-2"></i>Editar Registro de Enfermería</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('enfermeria.update', $registro->enf_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" class="form-control" value="{{ $registro->enf_tipo_persona }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ $registro->enf_tipo_persona == 'ESTUDIANTE' ? 'Estudiante' : 'Docente' }}</label>
                                    <input type="text" class="form-control" value="@if($registro->enf_tipo_persona == 'ESTUDIANTE'){{ $registro->estudiante->est_nombres }} {{ $registro->estudiante->est_apellidos }} - {{ $registro->estudiante->curso->cur_nombre ?? '' }}@else{{ $registro->docente->doc_nombres ?? '' }} {{ $registro->docente->doc_apellidos ?? '' }}@endif" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha <span class="text-danger">*</span></label>
                                    <input type="date" name="enf_fecha" class="form-control" value="{{ $registro->enf_fecha->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora <span class="text-danger">*</span></label>
                                    <input type="time" name="enf_hora" class="form-control" value="{{ $registro->enf_hora }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>DX Detalle <span class="text-danger">*</span></label>
                                    <select name="enf_dx_detalle" id="enf_dx_detalle" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <option value="ATENCIÓN MÉDICA" {{ $registro->enf_dx_detalle == 'ATENCIÓN MÉDICA' ? 'selected' : '' }}>ATENCIÓN MÉDICA</option>
                                        <option value="HIGIENE PERSONAL" {{ $registro->enf_dx_detalle == 'HIGIENE PERSONAL' ? 'selected' : '' }}>HIGIENE PERSONAL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="tipo_atencion_row" style="display:{{ $registro->enf_dx_detalle == 'ATENCIÓN MÉDICA' ? 'block' : 'none' }};">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Tipo de Atención</label>
                                    <input type="text" name="enf_tipo_atencion" id="enf_tipo_atencion" class="form-control" value="{{ $registro->enf_tipo_atencion }}" placeholder="Especifique el tipo de atención...">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="medicamentos_row" style="display:{{ $registro->enf_dx_detalle == 'ATENCIÓN MÉDICA' ? 'block' : 'none' }};">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Administración de Medicamentos <span class="text-danger">*</span></label>
                                    <textarea name="enf_medicamentos" id="enf_medicamentos" class="form-control" rows="3" {{ $registro->enf_dx_detalle == 'ATENCIÓN MÉDICA' ? 'required' : '' }}>{{ $registro->enf_medicamentos }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="observaciones_row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Observaciones <span class="text-danger" id="obs_required" style="display:{{ $registro->enf_dx_detalle == 'HIGIENE PERSONAL' ? 'inline' : 'none' }};">*</span></label>
                                    <textarea name="enf_observaciones" id="enf_observaciones" class="form-control" rows="3" {{ $registro->enf_dx_detalle == 'HIGIENE PERSONAL' ? 'required' : '' }}>{{ $registro->enf_observaciones }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                            <a href="{{ route('enfermeria.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
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
    $('#enf_dx_detalle').change(function() {
        var valor = $(this).val();
        if (valor == 'ATENCIÓN MÉDICA') {
            $('#tipo_atencion_row').show();
            $('#medicamentos_row').show();
            $('#enf_medicamentos').prop('required', true);
            $('#enf_observaciones').prop('required', false);
            $('#obs_required').hide();
        } else if (valor == 'HIGIENE PERSONAL') {
            $('#tipo_atencion_row').hide();
            $('#enf_tipo_atencion').val('');
            $('#medicamentos_row').hide();
            $('#enf_medicamentos').prop('required', false);
            $('#enf_observaciones').prop('required', true);
            $('#obs_required').show();
        } else {
            $('#tipo_atencion_row').hide();
            $('#enf_tipo_atencion').val('');
            $('#medicamentos_row').hide();
            $('#enf_medicamentos').prop('required', false);
            $('#enf_observaciones').prop('required', false);
            $('#obs_required').hide();
        }
    });
});
</script>
@endsection
@endsection
