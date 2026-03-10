@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Asistencias</h4>
                    <div>
                        <form method="POST" action="{{ route('asistencias.limpiar-duplicados') }}" style="display: inline;" onsubmit="return confirm('¿Está seguro de limpiar los registros duplicados?');">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-broom"></i> Limpieza
                            </button>
                        </form>
                        <a href="{{ route('asistencias.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar Asistencia
                        </a>
                        <button class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="GET" class="mb-3" id="formFiltros">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label>Curso</label>
                                <select name="cur_codigo" id="filtroCurso" class="form-control select2">
                                    <option value="">Todos los cursos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estudiante</label>
                                <select name="est_codigo" id="filtroEstudiante" class="form-control select2">
                                    <option value="">Todos los estudiantes</option>
                                    @foreach($estudiantes as $est)
                                        <option value="{{ $est->est_codigo }}" {{ request('est_codigo') == $est->est_codigo ? 'selected' : '' }}>
                                            {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="puntual" {{ request('estado') == 'puntual' ? 'selected' : '' }}>Puntual</option>
                                    <option value="atraso" {{ request('estado') == 'atraso' ? 'selected' : '' }}>Atraso</option>
                                    <option value="permiso" {{ request('estado') == 'permiso' ? 'selected' : '' }}>Permiso</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Turno</label>
                                <select name="turno" id="filtroTurno" class="form-control select2">
                                    <option value="">Todos los turnos</option>
                                    @foreach($turnos as $turno)
                                        <option value="{{ $turno['turno'] }}" {{ request('turno') == $turno['turno'] ? 'selected' : '' }}>
                                            {{ $turno['categoria'] }} - {{ $turno['nombre'] }} ({{ $turno['hora_entrada'] }}-{{ $turno['hora_salida'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                                    <i class="fas fa-eraser"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Reportes -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalReporteTrimestral">
                                    <i class="fas fa-file-pdf"></i> Reporte Trimestral
                                </button>
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalReporteAnual">
                                    <i class="fas fa-file-pdf"></i> Reporte Anual
                                </button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalReporteFaltas">
                                    <i class="fas fa-user-times"></i> Reporte Faltas
                                </button>
                                <button type="button" class="btn btn-warning" onclick="window.location.href='{{ route('asistencia-config.atrasos') }}'">
                                    <i class="fas fa-clock"></i> Gestionar Atrasos
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $totalAsistencias }}</h3>
                                    <p class="mb-0">Total Asistencias</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $totalPuntuales }}</h3>
                                    <p class="mb-0">Puntuales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $totalAtrasos }}</h3>
                                    <p class="mb-0">Atrasos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $totalPermisos }}</h3>
                                    <p class="mb-0">Permisos Activos</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-striped" id="tablaAsistencias">
                        <thead>
                            <tr>
                                @if(isset($mostrarPermisos) && $mostrarPermisos)
                                    <th>Código Permiso</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Motivo</th>
                                @else
                                    <th>Código</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($mostrarPermisos) && $mostrarPermisos)
                                @forelse($permisos as $p)
                                    <tr>
                                        <td>{{ $p->permiso_codigo }}</td>
                                        <td>{{ $p->estudiante->est_nombres ?? 'N/A' }} {{ $p->estudiante->est_apellidos ?? '' }}</td>
                                        <td>{{ $p->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($p->permiso_fecha_inicio)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($p->permiso_fecha_fin)->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge badge-info" title="{{ $p->permiso_observacion }}">
                                                {{ $p->permiso_motivo }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay permisos registrados</td>
                                    </tr>
                                @endforelse
                            @else
                                @forelse($asistencias as $a)
                                    <tr>
                                        <td>{{ $a->estud_codigo }}</td>
                                        <td>{{ $a->estudiante->est_nombres ?? 'N/A' }} {{ $a->estudiante->est_apellidos ?? '' }}</td>
                                        <td>{{ $a->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                        <td>{{ $a->asis_fecha->format('d/m/Y') }}</td>
                                        <td>{{ $a->asis_hora }}</td>
                                        <td>
                                            @if(isset($a->esAtraso) && $a->esAtraso)
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Atraso
                                                </span>
                                            @else
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Puntual
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay asistencias registradas</td>
                                    </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                    @if(isset($mostrarPermisos) && $mostrarPermisos)
                        {{ $permisos->appends(request()->query())->links() }}
                    @else
                        {{ $asistencias->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reporte Trimestral -->
<div class="modal fade" id="modalReporteTrimestral" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reporte Trimestral de Asistencias</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Curso <span class="text-danger">*</span></label>
                    <select id="cur_codigo_trimestral" class="form-control select2-modal-trimestral" required>
                        <option value="">Seleccione un curso</option>
                        @foreach(\App\Models\Curso::visible()->get() as $curso)
                            <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Trimestre <span class="text-danger">*</span></label>
                    <select id="trimestre" class="form-control" required>
                        <option value="1">1er Trimestre (Febrero - Mayo)</option>
                        <option value="2">2do Trimestre (Junio - Septiembre)</option>
                        <option value="3">3er Trimestre (Octubre - Diciembre)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" onclick="generarReporteTrimestral('pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
                <button type="button" class="btn btn-success" onclick="generarReporteTrimestral('excel')"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reporte Anual -->
<div class="modal fade" id="modalReporteAnual" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reporte Anual de Asistencias</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Curso <span class="text-danger">*</span></label>
                    <select id="cur_codigo_anual" class="form-control select2-modal-anual" required>
                        <option value="">Seleccione un curso</option>
                        @foreach(\App\Models\Curso::visible()->get() as $curso)
                            <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted">Este reporte incluye los 3 trimestres completos del año {{ date('Y') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" onclick="generarReporteAnual('pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
                <button type="button" class="btn btn-success" onclick="generarReporteAnual('excel')"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reporte Faltas -->
<div class="modal fade" id="modalReporteFaltas" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reporte de Faltas Sin Licencia</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Turno <span class="text-danger">*</span></label>
                    <select id="turno_faltas" class="form-control" required>
                        <option value="">Seleccione un turno</option>
                        @foreach($turnos as $turno)
                            <option value="{{ $turno['turno'] }}" data-categoria="{{ $turno['categoria'] }}" data-turno="{{ $turno['nombre'] }}">{{ $turno['categoria'] }} - {{ strtoupper($turno['nombre']) }} ({{ $turno['hora_entrada'] }}-{{ $turno['hora_salida'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select id="cur_codigo_faltas" class="form-control select2-modal-faltas">
                        <option value="todos">Todos los cursos del turno</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->cur_codigo }}" data-cursos="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Dejar en "Todos" para incluir todos los cursos del turno</small>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="todos_cursos_horarios">
                        <label class="custom-control-label" for="todos_cursos_horarios">
                            Todos los cursos y horarios
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">Marcar para incluir todos los cursos de todos los horarios configurados</small>
                </div>
                <div class="form-group">
                    <label>Fecha <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_faltas" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="generarReporteFaltas()"><i class="fas fa-file-pdf"></i> Generar PDF</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

function limpiarFiltros() {
    window.location.href = '{{ route('asistencias.index') }}';
}

$('#modalReporteTrimestral').on('shown.bs.modal', function () {
    $('.select2-modal-trimestral').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un curso',
        dropdownParent: $('#modalReporteTrimestral')
    });
});

$('#modalReporteAnual').on('shown.bs.modal', function () {
    $('.select2-modal-anual').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un curso',
        dropdownParent: $('#modalReporteAnual')
    });
});

$('#modalReporteFaltas').on('shown.bs.modal', function () {
    $('.select2-modal-faltas').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un curso',
        dropdownParent: $('#modalReporteFaltas')
    });
});

// Filtrar cursos por turno
$('#turno_faltas').on('change', function() {
    const turnoSeleccionado = $(this).val();
    const optionSeleccionada = $(this).find(':selected');
    const categoriaSeleccionada = optionSeleccionada.data('categoria');
    const turnoNombre = optionSeleccionada.data('turno');
    
    if (!turnoSeleccionado) {
        $('#cur_codigo_faltas').val('todos').trigger('change');
        $('#cur_codigo_faltas option').show();
        return;
    }
    
    // Obtener cursos del turno seleccionado via AJAX
    $.ajax({
        url: '{{ route("asistencias.cursos-por-turno") }}',
        type: 'GET',
        data: { 
            categoria: categoriaSeleccionada,
            turno: turnoNombre
        },
        beforeSend: function() {
            console.log('Enviando AJAX con:', { categoria: categoriaSeleccionada, turno: turnoNombre });
        },
        success: function(response) {
            console.log('Respuesta AJAX exitosa:', response);
            const cursosPermitidos = response.cursos;
            const aplicaATodos = response.aplica_a_todos;
            
            if (aplicaATodos) {
                $('#cur_codigo_faltas option').show();
            } else {
                $('#cur_codigo_faltas option').each(function() {
                    const valor = $(this).val();
                    if (valor === 'todos' || cursosPermitidos.includes(valor)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            $('#cur_codigo_faltas').val('todos').trigger('change');
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            console.error('Status:', status);
            console.error('Respuesta completa:', xhr.responseText);
            alert('Error al cargar los cursos del turno');
        }
    });
});

$('#todos_cursos_horarios').on('change', function() {
    if ($(this).is(':checked')) {
        $('#turno_faltas').prop('disabled', true).val('').trigger('change');
        $('#cur_codigo_faltas').prop('disabled', true).val('todos').trigger('change');
    } else {
        $('#turno_faltas').prop('disabled', false);
        $('#cur_codigo_faltas').prop('disabled', false);
    }
});

function generarReporteTrimestral(tipo) {
    const curso = $('#cur_codigo_trimestral').val();
    const trimestre = $('#trimestre').val();
    
    if (!curso) {
        alert('Seleccione un curso');
        return;
    }
    
    const url = tipo === 'pdf' 
        ? '{{ route("asistencias.reporte-trimestral") }}?cur_codigo=' + curso + '&trimestre=' + trimestre
        : '{{ route("asistencias.reporte-trimestral-excel") }}?cur_codigo=' + curso + '&trimestre=' + trimestre;
    
    window.open(url, '_blank');
}

function generarReporteAnual(tipo) {
    const curso = $('#cur_codigo_anual').val();
    
    if (!curso) {
        alert('Seleccione un curso');
        return;
    }
    
    const url = tipo === 'pdf'
        ? '{{ route("asistencias.reporte-anual") }}?cur_codigo=' + curso
        : '{{ route("asistencias.reporte-anual-excel") }}?cur_codigo=' + curso;
    
    window.open(url, '_blank');
}

function generarReporteFaltas() {
    const todosCursosHorarios = $('#todos_cursos_horarios').is(':checked');
    const curso = $('#cur_codigo_faltas').val() || 'todos';
    const fecha = $('#fecha_faltas').val();
    
    if (!fecha) {
        alert('Seleccione una fecha');
        return;
    }
    
    if (todosCursosHorarios) {
        const url = '{{ route("asistencias.reporte-faltas") }}?cur_codigo=todos&fecha=' + fecha + '&todos_horarios=1';
        window.open(url, '_blank');
    } else {
        const optionSeleccionada = $('#turno_faltas').find(':selected');
        const turnoNombre = optionSeleccionada.data('turno');
        const categoria = optionSeleccionada.data('categoria');
        
        if (!turnoNombre) {
            alert('Seleccione un turno');
            return;
        }
        
        const url = '{{ route("asistencias.reporte-faltas") }}?cur_codigo=' + curso + '&fecha=' + fecha + '&turno=' + turnoNombre + '&categoria=' + categoria;
        window.open(url, '_blank');
    }
}

function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Cargar logo
    const logoPath = '{{ asset("img/logo.png") }}';
    const img = new Image();
    img.crossOrigin = 'Anonymous';
    
    img.onload = function() {
        // Logo en la esquina superior izquierda
        doc.addImage(img, 'PNG', 14, 10, 20, 20);
        
        generarContenidoPDF(doc);
    };
    
    img.onerror = function() {
        // Si falla la carga del logo, generar sin él
        generarContenidoPDF(doc);
    };
    
    img.src = logoPath;
}

function generarContenidoPDF(doc) {
    // Badge de fecha en esquina superior derecha
    doc.setFillColor(220, 53, 69);
    doc.roundedRect(165, 10, 35, 12, 3, 3, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(8);
    doc.setFont(undefined, 'bold');
    doc.text('Fecha', 182.5, 14.5, { align: 'center' });
    doc.text(new Date().toLocaleDateString('es-BO'), 182.5, 19, { align: 'center' });
    
    // Encabezado institucional (ajustado para dar espacio al logo)
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(10);
    doc.setFont(undefined, 'bold');
    doc.text('Unidad Educativa', 105, 15, { align: 'center' });
    doc.setFontSize(11);
    doc.text('INTERANDINO BOLIVIANO', 105, 20, { align: 'center' });
    doc.setFontSize(7);
    doc.setFont(undefined, 'normal');
    doc.text('Dir. Calle Victor Gutierrez Nro 3339', 105, 25, { align: 'center' });
    doc.text('Teléfonos: 2840320', 105, 29, { align: 'center' });
    
    // Línea separadora
    doc.setLineWidth(0.5);
    doc.line(14, 32, 196, 32);
    
    // Título del reporte
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('REPORTE DE ASISTENCIAS', 105, 38, { align: 'center' });
    
    // Información del reporte
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    let startY = 44;
    doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 14, startY);
    @if(request('fecha_inicio') && request('fecha_fin'))
        doc.text('Período: {{ request("fecha_inicio") }} - {{ request("fecha_fin") }}', 14, startY + 5);
        startY += 10;
    @else
        startY += 5;
    @endif
    
    // Recopilar datos de la tabla
    var datos = [];
    $('#tablaAsistencias tbody tr').each(function() {
        if($(this).find('td').length > 1) {
            var fila = [];
            $(this).find('td').each(function(index) {
                if(index < 6) {
                    fila.push($(this).text().trim());
                }
            });
            if(fila.length > 0) datos.push(fila);
        }
    });
    
    // Tabla de datos
    doc.autoTable({
        head: [['Código', 'Estudiante', 'Curso', 'Fecha', 'Hora', 'Estado']],
        body: datos,
        startY: startY,
        headStyles: { 
            fillColor: [44, 62, 80],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 9
        },
        styles: { 
            fontSize: 8,
            cellPadding: 2
        },
        alternateRowStyles: {
            fillColor: [245, 245, 245]
        }
    });
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for(let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(7);
        doc.setTextColor(128, 128, 128);
        doc.text('Fecha y hora de impresión: ' + new Date().toLocaleString('es-BO'), 14, 285);
        doc.text('Página ' + i + ' de ' + pageCount, 196, 285, { align: 'right' });
    }
    
    doc.save('asistencias_' + new Date().getTime() + '.pdf');
}
</script>
@endsection
@endsection
