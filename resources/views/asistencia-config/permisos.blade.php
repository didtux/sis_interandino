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
                                <input type="text" name="buscar" id="searchPermiso" class="form-control" placeholder="Nombre, apellido, código, motivo..." value="{{ request('buscar') }}">
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

                    {{-- ====== Reportes Excel de Licencias ====== --}}
                    <div class="card mb-3" style="border-left:4px solid #1c4789;">
                        <div class="card-body py-2">
                            <h6 class="mb-2"><i class="fas fa-file-excel text-success mr-1"></i>Reportes Excel de Licencias</h6>
                            <div class="row align-items-end">
                                <div class="col-md-2">
                                    <label class="small mb-1">Gestión</label>
                                    <input type="number" id="lic_gestion" class="form-control form-control-sm" value="{{ date('Y') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="small mb-1">Mes</label>
                                    <select id="lic_mes" class="form-control form-control-sm">
                                        @php $meses = [2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']; @endphp
                                        @foreach($meses as $n => $m)
                                            <option value="{{ $n }}" {{ (int)date('n') == $n ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small mb-1">Turno (opcional)</label>
                                    <select id="lic_turno" class="form-control form-control-sm">
                                        <option value="">Todos</option>
                                        @foreach(($horarios ?? []) as $h)
                                            <option value="{{ $h->config_id }}">{{ $h->config_turno }} — {{ $h->config_categoria }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small mb-1">Curso (para anual×estudiante)</label>
                                    <select id="lic_curso" class="form-control form-control-sm select2-curso">
                                        <option value="">— Seleccione —</option>
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="excelLic('mensual')">
                                    <i class="fas fa-calendar-alt mr-1"></i>Mensual (días × curso)
                                </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="excelLic('anual-est')">
                                    <i class="fas fa-user-graduate mr-1"></i>Anual × estudiante
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="excelLic('anual-curso')">
                                    <i class="fas fa-layer-group mr-1"></i>Anual × curso
                                </button>
                            </div>
                        </div>
                    </div>

                    <table class="table table-striped" id="tablaPermisos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Horario</th>
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
                                    <td>{{ $p->permiso_codigo }}</td>
                                    <td><span class="badge badge-{{ $p->permiso_tipo == 'LICENCIA' ? 'info' : 'secondary' }}">{{ $p->permiso_tipo }}</span></td>
                                    <td>{{ $p->estudiante->est_nombres ?? 'N/A' }} {{ $p->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $p->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>
                                        @if($p->configuracion)
                                            <span class="badge badge-primary">{{ $p->configuracion->config_categoria }}</span>
                                            <br><small>{{ substr($p->configuracion->hora_entrada, 0, 5) }} - {{ substr($p->configuracion->hora_salida, 0, 5) }}</small>
                                        @else
                                            <span class="text-muted">Todos</span>
                                        @endif
                                    </td>
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
                                <tr><td colspan="12" class="text-center">No hay permisos</td></tr>
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
                                    <option value="{{ $e->est_codigo }}" data-curso="{{ $e->cur_codigo }}">{{ $e->est_codigo }} - {{ $e->est_nombres }} {{ $e->est_apellidos }} - {{ $e->curso->cur_nombre ?? 'Sin curso' }}</option>
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
                            <label>Horario / Turno</label>
                            <select name="config_id" id="selectHorario" class="form-control">
                                <option value="">Todos los horarios</option>
                                @foreach($horarios as $h)
                                    <option value="{{ $h->config_id }}"
                                        data-cursos="{{ $h->cursos->pluck('cur_codigo')->toJson() }}">
                                        {{ $h->config_categoria }} ({{ substr($h->hora_entrada, 0, 5) }} - {{ substr($h->hora_salida, 0, 5) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="horarioInfo">Dejar vacío para aplicar a todos los turnos</small>
                        </div>
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
                    </div>
                    <div class="row mt-3" id="divNumeroLicencia" style="display:none;">
                        <div class="col-md-3">
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
                <div id="alertaDuplicado" class="alert alert-warning mx-3 mb-0" style="display:none;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Posible duplicado:</strong>
                    <span id="alertaDuplicadoTexto"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const permisos = @json($permisos->items());
const horariosData = {!! $horarios->map(function($h) {
    return [
        'config_id' => $h->config_id,
        'config_categoria' => $h->config_categoria,
        'cursos' => $h->cursos->pluck('cur_codigo'),
    ];
})->toJson() !!};

$(document).ready(function() {
    $('.select2-curso').select2({ theme: 'bootstrap4', width: '100%', allowClear: true });
});

$('.select2').select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $('#modalPermiso') });

// Matcher tolerante al orden: "perez juan" o "juan perez" matchean al mismo estudiante.
function tokenMatcher(params, data) {
    if ($.trim(params.term) === '') return data;
    if (typeof data.text === 'undefined') return null;
    var norm = function(s){
        return (s || '').toString().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g,'');
    };
    var hay = norm(data.text);
    var tokens = norm(params.term).split(/\s+/).filter(Boolean);
    for (var i = 0; i < tokens.length; i++) {
        if (hay.indexOf(tokens[i]) === -1) return null;
    }
    return data;
}

$('#selectEstudiante').select2({
    theme: 'bootstrap4', width: '100%', dropdownParent: $('#modalPermiso'),
    placeholder: 'Buscar estudiante por nombre, apellido, código o curso...',
    allowClear: true,
    matcher: tokenMatcher
}).on('change', function() {
    var estCodigo = $(this).val();
    if (estCodigo) {
        // Cargar padres
        $.get('{{ url("/api/estudiantes") }}/' + estCodigo + '/padres', function(data) {
            $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
            data.forEach(function(padre) {
                $('#selectPadre').append('<option value="' + padre.pfam_codigo + '">' + padre.pfam_nombres + '</option>');
            });
            $('#selectPadre').prop('disabled', false);
        });

        // Filtrar horarios según el curso del estudiante
        var curCodigo = $(this).find(':selected').data('curso');
        filtrarHorarios(curCodigo);
    } else {
        $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>').prop('disabled', true);
        $('#selectHorario option').show();
        $('#horarioInfo').text('Dejar vacío para aplicar a todos los turnos');
    }
});

function filtrarHorarios(curCodigo) {
    var hayCoincidencia = false;
    $('#selectHorario option').each(function() {
        var val = $(this).val();
        if (!val) return; // opción "Todos"
        var cursosStr = $(this).data('cursos');
        var cursos = typeof cursosStr === 'string' ? JSON.parse(cursosStr) : cursosStr;
        if (cursos && cursos.length > 0 && !cursos.includes(curCodigo)) {
            $(this).hide();
        } else {
            $(this).show();
            hayCoincidencia = true;
        }
    });

    if (hayCoincidencia) {
        $('#horarioInfo').html('<span class="text-info">Mostrando horarios del curso del estudiante</span>');
    } else {
        $('#selectHorario option').show();
        $('#horarioInfo').html('<span class="text-warning">No hay horario específico para este curso</span>');
    }
    $('#selectHorario').val('');
}

$('#selectPadre').select2({
    theme: 'bootstrap4', width: '100%', dropdownParent: $('#modalPermiso'),
    placeholder: 'Seleccione padre/tutor...', allowClear: true
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
    $('#divNumeroLicencia').toggle($(this).val() === 'LICENCIA');
});

$('#searchPermiso').on('keyup', function() {
    var normalize = function(s){
        return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
    };
    var tokens = normalize($(this).val()).split(/\s+/).filter(Boolean);
    $('#tablaPermisos tbody tr').filter(function() {
        var text = normalize($(this).text());
        var match = tokens.every(function(t){ return text.indexOf(t) > -1; });
        $(this).toggle(match);
    });
});

function verificarDuplicadoPermiso() {
    var est = $('#selectEstudiante').val();
    var fi = $('#fecha_inicio').val();
    var ff = $('#fecha_fin').val();
    var cfg = $('#selectHorario').val();
    var permId = $('#permiso_id').val();
    if (!est || !fi || !ff) { $('#alertaDuplicado').hide(); return; }

    $.get('{{ route("asistencia-config.permisos.verificar-duplicado") }}', {
        estud_codigo: est, fecha_inicio: fi, fecha_fin: ff, config_id: cfg, permiso_id: permId
    }).done(function(resp){
        if (resp.duplicado) {
            var html = 'Ya existen <strong>' + resp.cantidad + '</strong> permiso(s) activos para este estudiante en el rango/turno:<ul class="mb-0 mt-1">';
            resp.permisos.slice(0, 5).forEach(function(p){
                html += '<li><strong>' + p.permiso_codigo + '</strong> (' + p.permiso_tipo + ') ' +
                        p.permiso_fecha_inicio + ' → ' + p.permiso_fecha_fin +
                        ' — <em>' + (p.permiso_motivo || '') + '</em></li>';
            });
            if (resp.cantidad > 5) html += '<li>... y ' + (resp.cantidad - 5) + ' más</li>';
            html += '</ul>Los días con permiso ya existente se omitirán automáticamente al guardar.';
            $('#alertaDuplicadoTexto').html(html);
            $('#alertaDuplicado').show();
        } else {
            $('#alertaDuplicado').hide();
        }
    });
}

$('#fecha_inicio, #fecha_fin, #selectHorario').on('change', verificarDuplicadoPermiso);
$('#selectEstudiante').on('change', verificarDuplicadoPermiso);

$('#modalPermiso').on('hidden.bs.modal', function() {
    $('#tituloModal').text('Nuevo Permiso');
    $('#formPermiso').attr('action', '{{ route('asistencia-config.permisos.store') }}');
    $('#methodPermiso').val('POST');
    $('#permiso_id').val('');
    $('#formPermiso')[0].reset();
    $('#selectEstudiante').val('').trigger('change');
    $('#selectPadre').empty().append('<option value="">Primero seleccione estudiante...</option>').prop('disabled', true);
    $('#selectHorario').val('');
    $('#selectHorario option').show();
    $('#horarioInfo').text('Dejar vacío para aplicar a todos los turnos');
    $('#divNumeroLicencia').hide();
    $('#checkOtroSolicitante').prop('checked', false).trigger('change');
    $('#alertaDuplicado').hide();
});

function editarPermiso(id) {
    var permiso = permisos.find(function(p) { return p.permiso_id == id; });
    if (!permiso) return;

    $('#tituloModal').text('Editar Permiso');
    $('#formPermiso').attr('action', '{{ url("asistencia-config/permisos") }}/' + id);
    $('#methodPermiso').val('PUT');
    $('#permiso_id').val(id);

    $('#permiso_tipo').val(permiso.permiso_tipo).trigger('change');
    $('#selectEstudiante').val(permiso.estud_codigo).trigger('change');

    // Horario
    setTimeout(function() {
        $('#selectHorario').val(permiso.config_id || '');
    }, 200);

    if (permiso.permiso_solicitante_pfam) {
        $('#checkOtroSolicitante').prop('checked', false).trigger('change');
        setTimeout(function() {
            $.get('{{ url("/api/estudiantes") }}/' + permiso.estud_codigo + '/padres', function(data) {
                $('#selectPadre').empty().append('<option value="">Seleccione padre/tutor...</option>');
                data.forEach(function(padre) {
                    var selected = padre.pfam_codigo == permiso.permiso_solicitante_pfam ? 'selected' : '';
                    $('#selectPadre').append('<option value="' + padre.pfam_codigo + '" ' + selected + '>' + padre.pfam_nombres + '</option>');
                });
                $('#selectPadre').prop('disabled', false).trigger('change');
            });
        }, 300);
    } else if (permiso.permiso_solicitante_nombre) {
        $('#checkOtroSolicitante').prop('checked', true).trigger('change');
        $('#solicitante_nombre').val(permiso.permiso_solicitante_nombre);
    }

    var fechaInicio = permiso.permiso_fecha_inicio ? permiso.permiso_fecha_inicio.split('T')[0] : '';
    var fechaFin = permiso.permiso_fecha_fin ? permiso.permiso_fecha_fin.split('T')[0] : '';

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
    var params = new URLSearchParams();
    var fechaInicio = document.querySelector('input[name="fecha_inicio"]').value;
    var fechaFin = document.querySelector('input[name="fecha_fin"]').value;
    var curCodigo = document.querySelector('select[name="cur_codigo"]').value;

    if (fechaInicio) params.append('fecha_inicio', fechaInicio);
    if (fechaFin) params.append('fecha_fin', fechaFin);
    if (curCodigo) params.append('cur_codigo', curCodigo);

    window.open('{{ route("asistencia-config.permisos.reporte-pdf") }}?' + params.toString(), '_blank');
}

function excelLic(tipo) {
    var gestion = document.getElementById('lic_gestion').value || {{ date('Y') }};
    var mes     = document.getElementById('lic_mes').value;
    var turno   = document.getElementById('lic_turno').value;
    var curso   = document.getElementById('lic_curso').value;
    var p = new URLSearchParams();
    p.append('gestion', gestion);
    if (turno) p.append('turno', turno);

    var url;
    if (tipo === 'mensual') {
        p.append('mes', mes);
        url = '{{ route("asistencia-config.licencias.excel-mensual") }}';
    } else if (tipo === 'anual-est') {
        if (!curso) { alert('Seleccione un curso para el reporte anual por estudiante.'); return; }
        p.append('cur_codigo', curso);
        url = '{{ route("asistencia-config.licencias.excel-anual-est") }}';
    } else {
        url = '{{ route("asistencia-config.licencias.excel-anual-curso") }}';
    }
    window.open(url + '?' + p.toString(), '_blank');
}
</script>
@endsection
