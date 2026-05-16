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
                    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

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

                        <ul class="nav nav-tabs mt-3" id="tabsAsist" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#tabPresentes" role="tab">
                                    <i class="fas fa-user-check text-success mr-1"></i>Presentes
                                    <span class="badge badge-success ml-1" id="cntPresentes">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabFaltas" role="tab">
                                    <i class="fas fa-user-times text-danger mr-1"></i>Quitar Asistencia
                                    <span class="badge badge-danger ml-1" id="cntFaltas">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabPermisos" role="tab">
                                    <i class="fas fa-id-badge text-info mr-1"></i>Con Permiso / Licencia
                                    <span class="badge badge-info ml-1" id="cntPermisos">0</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content border border-top-0 p-3" style="max-height:480px;overflow-y:auto;">
                            <div class="tab-pane fade show active" id="tabPresentes" role="tabpanel">
                                <div class="mb-2 d-flex align-items-center flex-wrap">
                                    <button type="button" class="btn btn-sm btn-success mr-1" onclick="marcar('pres', true)">Marcar todos</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" onclick="marcar('pres', false)">Desmarcar todos</button>
                                    <div class="input-group input-group-sm mr-2" style="width:auto;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                        </div>
                                        <input type="time" id="horaMasiva" class="form-control form-control-sm" value="{{ date('H:i') }}" style="width:110px;">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-sm btn-info" onclick="aplicarHoraMasiva()">Aplicar a marcados</button>
                                        </div>
                                    </div>
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Sólo los marcados se registran como presentes.</small>
                                </div>
                                <div id="listaPresentes">
                                    <p class="text-muted">Seleccione un curso para ver los estudiantes</p>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tabFaltas" role="tabpanel">
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="marcar('falta', true)">Marcar todos</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcar('falta', false)">Desmarcar todos</button>
                                    <small class="text-muted ml-2"><i class="fas fa-info-circle"></i> Para estos estudiantes se <strong>elimina</strong> el registro de asistencia de ese día (si lo había desde QR). Sin registro = falta, como en el cálculo normal de horarios dinámicos.</small>
                                </div>
                                <div id="listaFaltas">
                                    <p class="text-muted">Seleccione un curso para ver los estudiantes</p>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tabPermisos" role="tabpanel">
                                <div class="alert alert-info py-2 px-3 mb-2" style="font-size:12px;">
                                    <i class="fas fa-info-circle"></i> Solo informativo: estos estudiantes ya cuentan con permiso o licencia para este día. Gestionalos en
                                    <a href="{{ route('asistencia-config.permisos') }}" target="_blank"><strong>Permisos</strong></a>.
                                </div>
                                <div id="listaPermisos">
                                    <p class="text-muted">Seleccione un curso para ver los estudiantes</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar
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
function actualizarConteos() {
    $('#cntPresentes').text($('.chk-pres:checked').length);
    $('#cntFaltas').text($('.chk-falta:checked').length);
}

function aplicarHoraMasiva() {
    var nuevaHora = $('#horaMasiva').val();
    if (!nuevaHora) return;
    var count = 0;
    $('.chk-pres:checked').each(function(){
        var est = $(this).data('est');
        $('input[type="time"][name="hora_' + est + '"]').val(nuevaHora);
        count++;
    });
    if (count === 0) {
        alert('No hay estudiantes marcados como presentes.');
    }
}

function marcar(tipo, val) {
    $('.chk-' + tipo).prop('checked', val);
    if (val && tipo === 'pres') $('.chk-falta').prop('checked', false);
    if (val && tipo === 'falta') $('.chk-pres').prop('checked', false);
    actualizarConteos();
}

function renderPermisos(estudiantes, permisosMap) {
    if (!estudiantes.length) {
        return '<p class="text-muted">No hay estudiantes con permiso o licencia para esta fecha.</p>';
    }
    var html = '<div class="row">';
    estudiantes.forEach(function(est) {
        var numLista = est.lista_numero ? est.lista_numero + '. ' : '';
        var p = permisosMap[est.est_codigo] || {};
        var badgeColor = (p.tipo === 'LICENCIA') ? 'badge-info' : 'badge-secondary';
        var rowStyle = 'background:#e1f0fb;border-left:3px solid #17a2b8;border-radius:3px;padding:4px 8px;';
        html += '<div class="col-md-6 mb-2">';
        html += '<div style="' + rowStyle + '">';
        html += '<div><strong>' + numLista + '</strong>' + est.est_apellidos + ' ' + est.est_nombres;
        if (p.tipo) html += ' <span class="badge ' + badgeColor + ' ml-1">' + p.tipo + '</span>';
        if (p.codigo) html += ' <small class="text-muted ml-1">[' + p.codigo + ']</small>';
        html += '</div>';
        if (p.motivo) html += '<small class="text-muted d-block">' + p.motivo + '</small>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    return html;
}

function renderLista(estudiantes, tipo, presentesSet, horas, permisosMap) {
    var defaultHora = '{{ date("H:i") }}';
    presentesSet = presentesSet || {};
    horas = horas || {};
    permisosMap = permisosMap || {};
    if (!estudiantes.length) {
        return '<p class="text-muted">No hay estudiantes en este curso</p>';
    }
    var html = '';
    if (tipo === 'falta') {
        html += '<div class="alert alert-info py-2 px-3 mb-2" style="font-size:12px;">';
        html += '<i class="fas fa-check-circle text-success mr-1"></i> <strong>Lista de estudiantes con asistencia registrada</strong>';
        html += ' <span class="badge badge-success ml-1">' + estudiantes.length + '</span>';
        html += '<br><span class="text-muted">Marca los que quieres quitar (se eliminará el registro del día).</span>';
        html += '</div>';
    }
    html += '<div class="row">';
    estudiantes.forEach(function(est) {
        var numLista = est.lista_numero ? est.lista_numero + '. ' : '';
        var inputName = tipo === 'pres' ? 'estudiantes[]' : 'faltantes[]';
        var cls       = tipo === 'pres' ? 'chk-pres' : 'chk-falta';
        var id        = tipo + '_' + est.est_codigo;
        var yaPresente = !!presentesSet[est.est_codigo];
        var checked   = (tipo === 'pres' && yaPresente) ? ' checked' : '';
        var horaVal   = (tipo === 'pres' && yaPresente && horas[est.est_codigo]) ? horas[est.est_codigo] : defaultHora;
        var rowStyle  = (tipo === 'falta') ? 'background:#eafbf0;border-left:3px solid #28a745;border-radius:3px;padding:4px 8px;' : '';
        html += '<div class="col-md-6 mb-2">';
        html += '<div style="' + rowStyle + '">';
        html += '<div class="custom-control custom-checkbox">';
        html += '<input type="checkbox" class="custom-control-input ' + cls + '" data-est="' + est.est_codigo + '" id="' + id + '" name="' + inputName + '" value="' + est.est_codigo + '"' + checked + '>';
        html += '<label class="custom-control-label" for="' + id + '"><strong>' + numLista + '</strong>' + est.est_apellidos + ' ' + est.est_nombres;
        if (tipo === 'pres' && yaPresente) {
            html += ' <span class="badge badge-info ml-1">Ya registrado</span>';
        }
        if (tipo === 'pres' && permisosMap[est.est_codigo]) {
            var pinfo = permisosMap[est.est_codigo];
            html += ' <span class="badge badge-warning ml-1" title="' + (pinfo.motivo || '') + '"><i class="fas fa-id-badge"></i> ' + pinfo.tipo + '</span>';
        }
        if (tipo === 'falta') {
            var hr = horas[est.est_codigo] || '';
            html += ' <span class="badge badge-success ml-1"><i class="fas fa-clock"></i> ' + hr + '</span>';
        }
        html += '</label>';
        html += '</div>';
        if (tipo === 'pres') {
            html += '<div class="ml-4 mt-1">';
            html += '<input type="time" name="hora_' + est.est_codigo + '" class="form-control form-control-sm" value="' + horaVal + '">';
            html += '</div>';
        }
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    return html;
}

$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    function cargarEstudiantes() {
        var curCodigo = $('#cur_codigo').val();
        var fecha = $('input[name="asis_fecha"]').val();
        if (!curCodigo) {
            $('#listaPresentes,#listaFaltas,#listaPermisos').html('<p class="text-muted">Seleccione un curso para ver los estudiantes</p>');
            $('#cntPermisos').text(0);
            actualizarConteos();
            return;
        }
        $.get('{{ url("/api/estudiantes-por-curso") }}/' + curCodigo, { fecha: fecha }, function(resp) {
            var estudiantes = resp.estudiantes || resp;
            var presentesArr = resp.presentes || [];
            var horas = resp.horas || {};
            var permisosMap = resp.permisos || {};
            var presentesSet = {};
            presentesArr.forEach(function(c){ presentesSet[c] = true; });

            // Para "Quitar asistencia" solo tiene sentido mostrar a los que ya tienen registro ese día.
            var conAsistencia = estudiantes.filter(function(est){ return presentesSet[est.est_codigo]; });
            var conPermiso  = estudiantes.filter(function(est){ return permisosMap[est.est_codigo]; });

            $('#listaPresentes').html(renderLista(estudiantes, 'pres', presentesSet, horas, permisosMap));
            if (conAsistencia.length === 0) {
                $('#listaFaltas').html('<p class="text-muted">No hay estudiantes con asistencia registrada en esta fecha.</p>');
            } else {
                $('#listaFaltas').html(renderLista(conAsistencia, 'falta', presentesSet, horas));
            }
            $('#listaPermisos').html(renderPermisos(conPermiso, permisosMap));
            $('#cntPermisos').text(conPermiso.length);
            actualizarConteos();
        });
    }

    $('#cur_codigo').on('change', cargarEstudiantes);
    $('input[name="asis_fecha"]').on('change', cargarEstudiantes);

    // Exclusión mutua: si marco a un estudiante como presente, lo desmarco de faltas (y viceversa).
    $(document).on('change', '.chk-pres', function(){
        if ($(this).is(':checked')) {
            $('.chk-falta[data-est="' + $(this).data('est') + '"]').prop('checked', false);
        }
        actualizarConteos();
    });
    $(document).on('change', '.chk-falta', function(){
        if ($(this).is(':checked')) {
            $('.chk-pres[data-est="' + $(this).data('est') + '"]').prop('checked', false);
        }
        actualizarConteos();
    });
});
</script>
@endsection
@endsection
