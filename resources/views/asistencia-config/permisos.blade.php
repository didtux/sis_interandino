@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-file-alt mr-2"></i>Gestión de Permisos</h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalPermiso">
                        <i class="fas fa-plus"></i> Nuevo Permiso
                    </button>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Curso</label>
                                <select name="cur_codigo" class="form-control select2-curso">
                                    <option value="">Todos los cursos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Buscar</label>
                                <input type="text" id="searchPermiso" class="form-control" placeholder="Buscar por estudiante...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                                <a href="{{ route('asistencia-config.permisos') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                                <button type="button" class="btn btn-danger" onclick="generarReportePermisos()"><i class="fas fa-file-pdf"></i> Reporte PDF</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped" id="tablaPermisos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Origen</th>
                                <th>Motivo</th>
                                <th>Solicitante</th>
                                <th>Archivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permisos as $p)
                                <tr>
                                    <td>{{ $p->permiso_numero ?? $p->permiso_codigo }}</td>
                                    <td><span class="badge badge-{{ $p->permiso_tipo == 'LICENCIA' ? 'info' : 'secondary' }}">{{ $p->permiso_tipo }}</span></td>
                                    <td>{{ $p->estudiante->est_nombres ?? 'N/A' }} {{ $p->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $p->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>{{ $p->permiso_fecha_inicio ? $p->permiso_fecha_inicio->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $p->permiso_fecha_fin ? $p->permiso_fecha_fin->format('d/m/Y') : '-' }}</td>
                                    <td><span class="badge badge-secondary">{{ $p->permiso_origen ?? 'PERSONAL' }}</span></td>
                                    <td>{{ $p->permiso_motivo }}</td>
                                    <td>{{ $p->solicitante_nombre_completo ?? ($p->estudiante->padres->first()->pfam_nombres ?? '-') }}</td>
                                    <td>
                                        @if($p->permiso_archivo)
                                            <a href="{{ asset('uploads/permisos/' . $p->permiso_archivo) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver archivo">
                                                <i class="fas fa-file"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editarPermiso({{ $p->permiso_id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="{{ route('asistencia-config.permisos.imprimir', $p->permiso_id) }}" class="btn btn-sm btn-success" target="_blank" title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <form action="{{ route('asistencia-config.permisos.destroy', $p->permiso_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center">No hay permisos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $permisos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPermiso">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('asistencia-config.permisos.store') }}" method="POST" enctype="multipart/form-data" id="formPermiso">
                @csrf
                <input type="hidden" name="_method" value="POST" id="methodPermiso">
                <input type="hidden" name="permiso_id" id="permiso_id">
                <div class="modal-header">
                    <h5 id="tituloModal">Nuevo Permiso</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Tipo *</label>
                            <select name="permiso_tipo" id="permiso_tipo" class="form-control" required>
                                <option value="PERMISO">Permiso</option>
                                <option value="LICENCIA">Licencia</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Estudiante *</label>
                            <select name="estud_codigo" id="selectEstudiante" class="form-control select2" required style="width: 100%">
                                <option value="">Seleccione un estudiante...</option>
                                @foreach($estudiantes as $e)
                                    <option value="{{ $e->est_codigo }}">{{ $e->est_codigo }} - {{ $e->est_nombres }} {{ $e->est_apellidos }} - {{ $e->curso->cur_nombre ?? 'Sin curso' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3" id="divPadre">
                            <label>Padre/Tutor *</label>
                            <select name="permiso_solicitante_pfam" id="selectPadre" class="form-control select2" style="width: 100%">
                                <option value="">Primero seleccione estudiante...</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="divOtroSolicitante" style="display:none;">
                            <label>Nombre Solicitante *</label>
                            <input type="text" name="permiso_solicitante_nombre" id="solicitante_nombre" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="checkOtroSolicitante">
                                <label class="form-check-label" for="checkOtroSolicitante">Otro solicitante</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label>Fecha Inicio *</label>
                            <input type="date" name="permiso_fecha_inicio" id="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Fecha Fin *</label>
                            <input type="date" name="permiso_fecha_fin" id="fecha_fin" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Origen *</label>
                            <select name="permiso_origen" id="permiso_origen" class="form-control" required>
                                <option value="PERSONAL">Personal</option>
                                <option value="WHATSAPP">Mensaje WhatsApp</option>
                                <option value="LLAMADA">Llamada</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="divNumeroLicencia" style="display:none;">
                            <label>N° Licencia (solo lectura)</label>
                            <input type="text" id="numero_licencia_display" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Motivo *</label>
                            <input type="text" name="permiso_motivo" id="motivo" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label>Archivo Adjunto (Imagen o PDF)</label>
                            <input type="file" name="permiso_archivo" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Formatos: JPG, PNG, PDF (Máx. 2MB)</small>
                        </div>
                        <div class="col-md-6">
                            <label>Observación</label>
                            <textarea name="permiso_observacion" id="observacion" class="form-control" rows="2"></textarea>
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

@section('scripts')
<script>
const permisos = @json($permisos->items());

// Inicializar select2 para filtros
$(document).ready(function() {
    $('.select2-curso').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true
    });
});

// Inicializar select2 para modal
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modalPermiso')
});

$('#selectEstudiante').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modalPermiso'),
    placeholder: 'Buscar estudiante por nombre, código o curso...',
    allowClear: true
}).on('change', function() {
    const estCodigo = $(this).val();
    if (estCodigo) {
        $.get('{{ url("/api/estudiantes") }}/' + estCodigo + '/padres', function(data) {
            $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
            data.forEach(padre => {
                $('#selectPadre').append(`<option value="${padre.pfam_codigo}">${padre.pfam_nombres}</option>`);
            });
            $('#selectPadre').prop('disabled', false);
        }).fail(function() {
            alert('Error al cargar los padres del estudiante');
            $('#selectPadre').empty().append('<option value="">Error al cargar padres</option>').prop('disabled', true);
        });
    } else {
        $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>').prop('disabled', true);
    }
});

$('#selectPadre').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modalPermiso'),
    placeholder: 'Seleccione padre/tutor...',
    allowClear: true
});

$('#checkOtroSolicitante').on('change', function() {
    if ($(this).is(':checked')) {
        $('#divPadre').hide();
        $('#divOtroSolicitante').show();
        $('#selectPadre').prop('required', false).val('');
        $('#solicitante_nombre').prop('required', true);
    } else {
        $('#divPadre').show();
        $('#divOtroSolicitante').hide();
        $('#selectPadre').prop('required', true);
        $('#solicitante_nombre').prop('required', false).val('');
    }
});

$('#permiso_tipo').on('change', function() {
    if ($(this).val() === 'LICENCIA') {
        $('#divNumeroLicencia').show();
    } else {
        $('#divNumeroLicencia').hide();
    }
});

$('#searchPermiso').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $('#tablaPermisos tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

$('#modalPermiso').on('hidden.bs.modal', function() {
    $('#tituloModal').text('Nuevo Permiso');
    $('#formPermiso').attr('action', '{{ route('asistencia-config.permisos.store') }}');
    $('#methodPermiso').val('POST');
    $('#permiso_id').val('');
    $('#formPermiso')[0].reset();
    $('#selectEstudiante').val('').trigger('change');
    $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>').prop('disabled', true);
    $('#divNumeroLicencia').hide();
    $('#checkOtroSolicitante').prop('checked', false).trigger('change');
});

function editarPermiso(id) {
    const permiso = permisos.find(p => p.permiso_id == id);
    if (!permiso) {
        console.error('Permiso no encontrado:', id);
        return;
    }
    
    $('#tituloModal').text('Editar Permiso');
    $('#formPermiso').attr('action', '{{ url("asistencia-config/permisos") }}/' + id);
    $('#methodPermiso').val('PUT');
    $('#permiso_id').val(id);
    
    $('#permiso_tipo').val(permiso.permiso_tipo).trigger('change');
    $('#selectEstudiante').val(permiso.estud_codigo).trigger('change');
    
    // Verificar si tiene padre o nombre de solicitante
    if (permiso.permiso_solicitante_pfam) {
        $('#checkOtroSolicitante').prop('checked', false).trigger('change');
        setTimeout(() => {
            $.get('{{ url("/api/estudiantes") }}/' + permiso.estud_codigo + '/padres', function(data) {
                $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
                data.forEach(padre => {
                    const selected = padre.pfam_codigo == permiso.permiso_solicitante_pfam ? 'selected' : '';
                    $('#selectPadre').append(`<option value="${padre.pfam_codigo}" ${selected}>${padre.pfam_nombres}</option>`);
                });
                $('#selectPadre').prop('disabled', false).trigger('change');
            }).fail(function() {
                alert('Error al cargar los padres del estudiante');
            });
        }, 300);
    } else if (permiso.permiso_solicitante_nombre) {
        $('#checkOtroSolicitante').prop('checked', true).trigger('change');
        $('#solicitante_nombre').val(permiso.permiso_solicitante_nombre);
    }
    
    const fechaInicio = permiso.permiso_fecha_inicio ? permiso.permiso_fecha_inicio.split('T')[0] : '';
    const fechaFin = permiso.permiso_fecha_fin ? permiso.permiso_fecha_fin.split('T')[0] : '';
    
    $('#fecha_inicio').val(fechaInicio);
    $('#fecha_fin').val(fechaFin);
    $('#motivo').val(permiso.permiso_motivo);
    $('#permiso_origen').val(permiso.permiso_origen || 'PERSONAL').trigger('change');
    $('#observacion').val(permiso.permiso_observacion || '');
    
    if (permiso.permiso_tipo === 'LICENCIA' && permiso.permiso_numero_licencia) {
        $('#divNumeroLicencia').show();
        $('#numero_licencia_display').val(permiso.permiso_numero_licencia);
    }
    
    $('#modalPermiso').modal('show');
}

function generarReportePermisos() {
    const params = new URLSearchParams();
    const fechaInicio = document.querySelector('input[name="fecha_inicio"]').value;
    const fechaFin = document.querySelector('input[name="fecha_fin"]').value;
    const curCodigo = document.querySelector('select[name="cur_codigo"]').value;
    
    if (fechaInicio) params.append('fecha_inicio', fechaInicio);
    if (fechaFin) params.append('fecha_fin', fechaFin);
    if (curCodigo) params.append('cur_codigo', curCodigo);
    
    window.open('{{ route("asistencia-config.permisos.reporte-pdf") }}?' + params.toString(), '_blank');
}
</script>
@endsection
@endsection
