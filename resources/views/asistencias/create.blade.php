@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Registrar Asistencia</h4>
                    <div class="card-header-action">
                        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('asistencias.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Curso <span class="text-danger">*</span></label>
                                    <select name="cur_codigo" id="cur_codigo" class="form-control select2" required>
                                        <option value="">Seleccione un curso</option>
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha <span class="text-danger">*</span></label>
                                    <input type="date" name="asis_fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Estudiantes del Curso</label>
                            <div id="listaEstudiantes" class="border p-3" style="max-height: 400px; overflow-y: auto;">
                                <p class="text-muted">Seleccione un curso para ver los estudiantes</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Asistencias
                            </button>
                            <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
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
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    $('#cur_codigo').on('change', function() {
        var curCodigo = $(this).val();
        if(curCodigo) {
            $.get('{{ url("/api/estudiantes-por-curso") }}/' + curCodigo, function(estudiantes) {
                var html = '<div class="row">';
                html += '<div class="col-12 mb-3">';
                html += '<button type="button" class="btn btn-sm btn-success" onclick="marcarTodos(true)">Marcar Todos</button> ';
                html += '<button type="button" class="btn btn-sm btn-warning" onclick="marcarTodos(false)">Desmarcar Todos</button>';
                html += '</div>';
                
                if(estudiantes.length > 0) {
                    estudiantes.forEach(function(est) {
                        var numLista = est.lista_numero ? est.lista_numero + '. ' : '';
                        html += '<div class="col-md-6 mb-2">';
                        html += '<div class="custom-control custom-checkbox">';
                        html += '<input type="checkbox" class="custom-control-input estudiante-check" id="est_' + est.est_codigo + '" name="estudiantes[]" value="' + est.est_codigo + '" checked>';
                        html += '<label class="custom-control-label" for="est_' + est.est_codigo + '"><strong>' + numLista + '</strong>' + est.est_apellidos + ' ' + est.est_nombres + '</label>';
                        html += '</div>';
                        html += '<div class="ml-4">';
                        html += '<input type="time" name="hora_' + est.est_codigo + '" class="form-control form-control-sm" value="{{ date("H:i") }}" placeholder="Hora">';
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html += '<div class="col-12"><p class="text-muted">No hay estudiantes en este curso</p></div>';
                }
                html += '</div>';
                $('#listaEstudiantes').html(html);
            });
        } else {
            $('#listaEstudiantes').html('<p class="text-muted">Seleccione un curso para ver los estudiantes</p>');
        }
    });
});

function marcarTodos(marcar) {
    $('.estudiante-check').prop('checked', marcar);
}
</script>
@endsection
@endsection
