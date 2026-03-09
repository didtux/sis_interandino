@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-cog mr-2"></i>Configuración de Horarios</h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalConfig">
                        <i class="fas fa-plus"></i> Nueva Configuración
                    </button>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Turno</th>
                                <th>Cursos</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Tolerancia</th>
                                <th>Atraso Desde</th>
                                <th>Atraso Hasta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configuraciones as $c)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $c->config_categoria }}</span></td>
                                    <td><span class="badge badge-info">{{ $c->config_turno ?? 'N/A' }}</span></td>
                                    <td>
                                        @if($c->cursos->isEmpty())
                                            <span class="badge badge-success">Todos</span>
                                        @else
                                            @foreach($c->cursos as $curso)
                                                <span class="badge badge-success">{{ $curso->cur_nombre }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ $c->hora_entrada }}</td>
                                    <td>{{ $c->hora_salida }}</td>
                                    <td>{{ $c->tolerancia_atraso }}</td>
                                    <td>{{ $c->hora_atraso_desde }}</td>
                                    <td>{{ $c->hora_atraso_hasta }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editarConfig({{ $c->config_id }}, '{{ $c->config_categoria }}', '{{ $c->config_turno }}', {{ json_encode($c->cursos->pluck('cur_codigo')) }}, '{{ $c->hora_entrada }}', '{{ $c->hora_salida }}', '{{ $c->tolerancia_atraso }}', '{{ $c->hora_atraso_desde }}', '{{ $c->hora_atraso_hasta }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('asistencia-config.configuracion.destroy', $c->config_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay configuraciones</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfig">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('asistencia-config.configuracion.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5>Nueva Configuración</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Categoría</label>
                            <input type="text" name="config_categoria" class="form-control" placeholder="Ej: Primaria" required>
                        </div>
                        <div class="col-md-6">
                            <label>Turno</label>
                            <select name="config_turno" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="Mañana">Mañana</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noche">Noche</option>
                            </select>
                        </div>
                        <div class="col-md-12 mt-3">
                            <label>Cursos</label>
                            <select name="cur_codigos[]" id="select_curso_create" class="form-control select2" multiple style="width: 100%;">
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Vacío = Todos los cursos</small>
                        </div>
                        <div class="col-md-6">
                            <label>Hora Entrada</label>
                            <input type="time" name="hora_entrada" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Hora Salida</label>
                            <input type="time" name="hora_salida" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Tolerancia</label>
                            <input type="time" name="tolerancia_atraso" class="form-control" value="00:15" required>
                        </div>
                        <div class="col-md-6">
                            <label>Atraso Desde</label>
                            <input type="time" name="hora_atraso_desde" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Atraso Hasta</label>
                            <input type="time" name="hora_atraso_hasta" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditConfig">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditConfig" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5>Editar Configuración</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Categoría</label>
                            <input type="text" name="config_categoria" id="edit_categoria" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Turno</label>
                            <select name="config_turno" id="edit_turno" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="Mañana">Mañana</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noche">Noche</option>
                            </select>
                        </div>
                        <div class="col-md-12 mt-3">
                            <label>Cursos</label>
                            <select name="cur_codigos[]" id="edit_curso" class="form-control select2-edit" multiple style="width: 100%;">
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Vacío = Todos los cursos</small>
                        </div>
                        <div class="col-md-6">
                            <label>Hora Entrada</label>
                            <input type="time" name="hora_entrada" id="edit_entrada" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Hora Salida</label>
                            <input type="time" name="hora_salida" id="edit_salida" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Tolerancia</label>
                            <input type="time" name="tolerancia_atraso" id="edit_tolerancia" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Atraso Desde</label>
                            <input type="time" name="hora_atraso_desde" id="edit_desde" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Atraso Hasta</label>
                            <input type="time" name="hora_atraso_hasta" id="edit_hasta" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<style>
.select2-container--bootstrap4 .select2-selection--multiple {
    min-height: 100px !important;
    padding: 8px !important;
    border: 1px solid #ced4da !important;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: #007bff !important;
    border: none !important;
    color: #fff !important;
    padding: 6px 10px !important;
    margin: 3px !important;
    font-size: 0.95rem !important;
    border-radius: 3px !important;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff !important;
    margin-right: 6px !important;
    font-size: 1.1rem !important;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ffcccc !important;
}
</style>
<script>
// Inicializar Select2 cuando se abre el modal de crear
$('#modalConfig').on('shown.bs.modal', function() {
    if (!$('#select_curso_create').hasClass('select2-hidden-accessible')) {
        $('#select_curso_create').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Buscar y seleccionar cursos...',
            allowClear: true,
            closeOnSelect: false,
            dropdownParent: $('#modalConfig')
        });
    }
});

// Inicializar Select2 cuando se abre el modal de editar
$('#modalEditConfig').on('shown.bs.modal', function() {
    if (!$('#edit_curso').hasClass('select2-hidden-accessible')) {
        $('#edit_curso').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Buscar y seleccionar cursos...',
            allowClear: true,
            closeOnSelect: false,
            dropdownParent: $('#modalEditConfig')
        });
    }
});

function editarConfig(id, categoria, turno, cursos, entrada, salida, tolerancia, desde, hasta) {
    $('#edit_categoria').val(categoria);
    $('#edit_turno').val(turno);
    
    // Extraer solo HH:MM de los valores de tiempo
    $('#edit_entrada').val(entrada.includes(' ') ? entrada.substring(11, 16) : entrada.substring(0, 5));
    $('#edit_salida').val(salida.includes(' ') ? salida.substring(11, 16) : salida.substring(0, 5));
    $('#edit_tolerancia').val(tolerancia.includes(' ') ? tolerancia.substring(11, 16) : tolerancia.substring(0, 5));
    $('#edit_desde').val(desde.includes(' ') ? desde.substring(11, 16) : desde.substring(0, 5));
    $('#edit_hasta').val(hasta.includes(' ') ? hasta.substring(11, 16) : hasta.substring(0, 5));
    
    $('#formEditConfig').attr('action', '{{ url("/asistencia-config/configuracion") }}/' + id);
    
    // Primero abrir el modal, luego cargar los cursos
    $('#modalEditConfig').modal('show');
    
    setTimeout(function() {
        $('#edit_curso').val(cursos).trigger('change');
    }, 300);
}

$('#modalConfig').on('hidden.bs.modal', function() {
    $('#select_curso_create').val(null).trigger('change');
});

$('#modalEditConfig').on('hidden.bs.modal', function() {
    $('#edit_curso').val(null).trigger('change');
    $('#formEditConfig')[0].reset();
});
</script>
@endsection
@endsection
