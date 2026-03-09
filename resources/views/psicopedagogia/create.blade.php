@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus mr-2"></i>Nuevo Caso Psicopedagógico</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('psicopedagogia.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante <span class="text-danger">*</span></label>
                                    <select name="est_codigo" id="est_codigo" class="form-control select2" required>
                                        <option value="">Seleccione estudiante</option>
                                        @foreach($estudiantes as $est)
                                            <option value="{{ $est->est_codigo }}">{{ $est->est_nombres }} {{ $est->est_apellidos }} - {{ $est->curso->cur_nombre ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha <span class="text-danger">*</span></label>
                                    <input type="date" name="psico_fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div id="info-estudiante" style="display:none;" class="alert alert-info mb-3"></div>

                        <div class="form-group">
                            <label>Caso <span class="text-danger">*</span></label>
                            <textarea name="psico_caso" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Solución</label>
                            <textarea name="psico_solucion" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Acuerdo</label>
                            <textarea name="psico_acuerdo" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Acuerdo <span class="text-danger">*</span></label>
                            <select name="psico_tipo_acuerdo" class="form-control" required>
                                <option value="NINGUNO">Ninguno</option>
                                <option value="VERBAL">Verbal</option>
                                <option value="ESCRITO">Escrito</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea name="psico_observaciones" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="{{ route('psicopedagogia.index') }}" class="btn btn-secondary">Cancelar</a>
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
    
    $('#est_codigo').on('change', function() {
        const codigo = $(this).val();
        if (!codigo) {
            $('#info-estudiante').hide();
            return;
        }
        
        $.get('/psicopedagogia/buscar-estudiante/' + codigo, function(data) {
            if (data.success) {
                let html = '<strong>Estudiante:</strong> ' + data.estudiante.nombres + '<br>';
                html += '<strong>CI:</strong> ' + (data.estudiante.ci || 'N/A') + '<br>';
                html += '<strong>Curso:</strong> ' + data.estudiante.curso + '<br>';
                html += '<strong>Padres:</strong> ' + (data.estudiante.padres.length > 0 ? data.estudiante.padres.join(', ') : 'Sin padres registrados');
                $('#info-estudiante').html(html).fadeIn();
            }
        });
    });
});
</script>
@endsection
@endsection
