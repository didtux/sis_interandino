@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-calendar-day mr-2"></i>Fechas Festivas</h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalFestivo">
                        <i class="fas fa-plus"></i> Nueva Fecha
                    </button>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Horario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($festivos as $f)
                                <tr>
                                    <td>{{ $f->festivo_fecha->format('d/m/Y') }}</td>
                                    <td><strong>{{ $f->festivo_nombre }}</strong></td>
                                    <td>{{ $f->festivo_descripcion ?? '-' }}</td>
                                    <td>
                                        @if($f->festivo_tipo == 1)
                                            <span class="badge badge-danger">Feriado</span>
                                        @else
                                            <span class="badge badge-warning">Horario Especial</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($f->festivo_tipo == 2)
                                            {{ $f->festivo_hora_entrada }} - {{ $f->festivo_hora_salida }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editarFestivo({{ $f->festivo_id }}, '{{ $f->festivo_fecha->format('Y-m-d') }}', '{{ $f->festivo_nombre }}', '{{ $f->festivo_descripcion }}', {{ $f->festivo_tipo }}, '{{ $f->festivo_hora_entrada }}', '{{ $f->festivo_hora_salida }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('asistencia-config.festivos.destroy', $f->festivo_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay fechas festivas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $festivos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFestivo">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('asistencia-config.festivos.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5>Nueva Fecha Festiva</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Fecha</label>
                            <input type="date" name="festivo_fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Tipo</label>
                            <select name="festivo_tipo" class="form-control" id="tipoFestivo" required>
                                <option value="1">Feriado (Sin clases)</option>
                                <option value="2">Horario Especial</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="festivo_nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="festivo_descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row" id="horariosDiv" style="display:none">
                        <div class="col-md-6">
                            <label>Hora Entrada</label>
                            <input type="time" name="festivo_hora_entrada" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Hora Salida</label>
                            <input type="time" name="festivo_hora_salida" class="form-control">
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

<div class="modal fade" id="modalEditFestivo">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditFestivo" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5>Editar Fecha Festiva</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Fecha</label>
                            <input type="date" name="festivo_fecha" id="edit_fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Tipo</label>
                            <select name="festivo_tipo" class="form-control" id="edit_tipo" required>
                                <option value="1">Feriado (Sin clases)</option>
                                <option value="2">Horario Especial</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="festivo_nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="festivo_descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row" id="edit_horariosDiv" style="display:none">
                        <div class="col-md-6">
                            <label>Hora Entrada</label>
                            <input type="time" name="festivo_hora_entrada" id="edit_hora_entrada" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Hora Salida</label>
                            <input type="time" name="festivo_hora_salida" id="edit_hora_salida" class="form-control">
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
<script>
$('#tipoFestivo').change(function() {
    $('#horariosDiv').toggle($(this).val() == '2');
});

$('#edit_tipo').change(function() {
    $('#edit_horariosDiv').toggle($(this).val() == '2');
});

function editarFestivo(id, fecha, nombre, descripcion, tipo, horaEntrada, horaSalida) {
    $('#edit_fecha').val(fecha);
    $('#edit_nombre').val(nombre);
    $('#edit_descripcion').val(descripcion);
    $('#edit_tipo').val(tipo);
    $('#edit_hora_entrada').val(horaEntrada ? horaEntrada.substring(11, 16) : '');
    $('#edit_hora_salida').val(horaSalida ? horaSalida.substring(11, 16) : '');
    $('#edit_horariosDiv').toggle(tipo == 2);
    $('#formEditFestivo').attr('action', '/asistencia-config/festivos/' + id);
    $('#modalEditFestivo').modal('show');
}
</script>
@endsection
@endsection
