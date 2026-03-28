@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-heartbeat mr-2"></i>Nuevo Registro de Enfermería</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('enfermeria.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="es_docente" name="es_docente" value="1">
                                        <label class="custom-control-label" for="es_docente">Es Docente</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="enf_tipo_persona" id="enf_tipo_persona" value="ESTUDIANTE">
                        <div class="row">
                            <div class="col-md-6" id="estudiante_select">
                                <div class="form-group">
                                    <label>Estudiante <span class="text-danger">*</span></label>
                                    <select name="est_codigo" id="est_codigo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($estudiantes as $est)
                                            <option value="{{ $est->est_codigo }}">
                                                {{ $est->est_nombres }} {{ $est->est_apellidos }} - {{ $est->curso->cur_nombre ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="docente_select" style="display:none;">
                                <div class="form-group">
                                    <label>Docente <span class="text-danger">*</span></label>
                                    <select name="doc_codigo" id="doc_codigo" class="form-control select2" disabled>
                                        <option value="">Seleccione...</option>
                                        @foreach($docentes as $doc)
                                            <option value="{{ $doc->doc_codigo }}">
                                                {{ $doc->doc_nombres }} {{ $doc->doc_apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha <span class="text-danger">*</span></label>
                                    <input type="date" name="enf_fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora <span class="text-danger">*</span></label>
                                    <input type="time" name="enf_hora" class="form-control" value="{{ date('H:i') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>DX Detalle <span class="text-danger">*</span></label>
                                    <select name="enf_dx_detalle" id="enf_dx_detalle" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <option value="ATENCIÓN MÉDICA">ATENCIÓN MÉDICA</option>
                                        <option value="HIGIENE PERSONAL">HIGIENE PERSONAL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="tipo_atencion_row" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Tipo de Atención</label>
                                    <input type="text" name="enf_tipo_atencion" id="enf_tipo_atencion" class="form-control" placeholder="Especifique el tipo de atención...">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="medicamentos_row" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Administración de Medicamentos<span class="text-danger">*</span></label>
                                    <textarea name="enf_medicamentos" id="enf_medicamentos" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="observaciones_row" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Observaciones <span class="text-danger" id="obs_required">*</span></label>
                                    <textarea name="enf_observaciones" id="enf_observaciones" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
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
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    $('#es_docente').change(function() {
        if($(this).is(':checked')) {
            $('#enf_tipo_persona').val('DOCENTE');
            $('#estudiante_select').hide();
            $('#docente_select').show();
            $('#est_codigo').prop('required', false).prop('disabled', true);
            $('#doc_codigo').prop('required', true).prop('disabled', false);
        } else {
            $('#enf_tipo_persona').val('ESTUDIANTE');
            $('#estudiante_select').show();
            $('#docente_select').hide();
            $('#est_codigo').prop('required', true).prop('disabled', false);
            $('#doc_codigo').prop('required', false).prop('disabled', true);
        }
    });
    
    $('#enf_dx_detalle').change(function() {
        var valor = $(this).val();
        if (valor == 'ATENCIÓN MÉDICA') {
            $('#tipo_atencion_row').show();
            $('#medicamentos_row').show();
            $('#observaciones_row').show();
            $('#enf_medicamentos').prop('required', true);
            $('#enf_observaciones').prop('required', false);
            $('#obs_required').hide();
        } else if (valor == 'HIGIENE PERSONAL') {
            $('#tipo_atencion_row').hide();
            $('#enf_tipo_atencion').val('');
            $('#medicamentos_row').hide();
            $('#observaciones_row').show();
            $('#enf_medicamentos').prop('required', false);
            $('#enf_observaciones').prop('required', true);
            $('#obs_required').show();
        } else {
            $('#tipo_atencion_row').hide();
            $('#enf_tipo_atencion').val('');
            $('#medicamentos_row').hide();
            $('#observaciones_row').hide();
            $('#enf_medicamentos').prop('required', false);
            $('#enf_observaciones').prop('required', false);
        }
    });
});
</script>
@endsection
@endsection
