@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3 text-white">
                <h4><i class="fas fa-cog mr-2 text-white"></i>Configuración de Notas - Gestión {{ $gestion }}</h4>
                <a href="{{ route('notas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success-modern">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Pestañas --}}
    <ul class="nav nav-tabs mb-0" id="tabsConfig" role="tablist" style="border-bottom:2px solid #dee2e6;">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-general" role="tab">
                <i class="fas fa-sliders-h mr-1"></i>Periodos y dimensiones
            </a>
        </li>
        @if(auth()->user()->rol_id == 1)
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-recalculo" role="tab">
                <i class="fas fa-calculator mr-1"></i>Recalcular notas guardadas
            </a>
        </li>
        @endif
    </ul>

    <div class="tab-content pt-3">
    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">


    <div class="row">
        {{-- Periodos --}}
        <div class="col-md-7">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Periodos (Trimestres/Bimestres)</h5>
                    <button class="btn btn-primary-modern btn-sm" onclick="modalPeriodo()">
                        <i class="fas fa-plus mr-1"></i>Nuevo
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Nombre</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periodos as $p)
                                <tr>
                                    <td>{{ $p->periodo_numero }}</td>
                                    <td>{{ $p->periodo_nombre }}</td>
                                    <td>{{ $p->periodo_fecha_inicio->format('d/m/Y') }}</td>
                                    <td>{{ $p->periodo_fecha_fin->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="modern-badge {{ $p->periodo_estado ? 'badge-success-modern' : 'badge-secondary' }}">
                                            {{ $p->periodo_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-action btn-action-edit btn-sm" onclick="modalPeriodo({{ json_encode($p) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('notas.eliminar-periodo', $p->periodo_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete btn-sm" onclick="return confirm('¿Eliminar este periodo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No hay periodos configurados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Dimensiones --}}
        <div class="col-md-5">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Dimensiones</h5>
                    <button class="btn btn-primary-modern btn-sm" onclick="modalDimension()">
                        <i class="fas fa-plus mr-1"></i>Nueva
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Dimensión</th>
                                <th>Valor Máx.</th>
                                <th>Columnas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalMax = 0; @endphp
                            @forelse($dimensiones as $d)
                                @php $totalMax += $d->dimension_valor_max; @endphp
                                <tr>
                                    <td>{{ $d->dimension_orden }}</td>
                                    <td><strong>{{ $d->dimension_nombre }}</strong></td>
                                    <td><span class="modern-badge badge-primary-modern">{{ $d->dimension_valor_max }}</span></td>
                                    <td><span class="modern-badge badge-warning-modern">{{ $d->dimension_columnas }}</span></td>
                                    <td>
                                        <button class="btn btn-action btn-action-edit btn-sm" onclick="modalDimension({{ json_encode($d) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('notas.eliminar-dimension', $d->dimension_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete btn-sm" onclick="return confirm('¿Eliminar esta dimensión?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No hay dimensiones configuradas</td></tr>
                            @endforelse
                        </tbody>
                        @if($dimensiones->count())
                            <tfoot>
                                <tr style="background:#f8f9fa;font-weight:bold;">
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td><span class="modern-badge {{ $totalMax == 100 ? 'badge-success-modern' : 'badge-danger-modern' }}">{{ $totalMax }}/100</span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- /tab-general --}}

    <div class="tab-pane fade" id="tab-recalculo" role="tabpanel">

    {{-- ═══ Recálculo de promedios ya guardados (sólo administradores) ═══ --}}
    @if(auth()->user()->rol_id == 1)
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header" style="background:linear-gradient(135deg,#e67e22,#d35400);color:#fff;">
                    <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i>Mantenimiento — Recalcular notas ya guardadas</h5>
                </div>
                <div class="card-body">

                    <div class="alert" style="background:#fff4e5;border-left:4px solid #e67e22;color:#7a4a12;">
                        <h6 class="mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Leé esto antes de usarlo</h6>
                        <p class="mb-2">
                            Las notas guardadas antes de unificar la fórmula tienen el promedio trimestral
                            calculado como <strong>suma de decimales redondeada al final</strong>, mientras la
                            pantalla del docente suma los promedios de cada dimensión <strong>ya redondeados</strong>.
                            Por eso un mismo estudiante puede verse como <code>82</code> en el registro del docente
                            y salir <code>83</code> en el boletín y el centralizador.
                        </p>
                        <p class="mb-2">
                            Esta herramienta recompone el promedio <strong>a partir de las notas por celda ya
                            cargadas</strong> (no inventa ni modifica ninguna nota individual) y lo deja igual
                            al que ve el docente.
                        </p>
                        <ul class="mb-0 pl-3">
                            <li>Puede <strong>modificar notas ya APROBADAS</strong>, y un estudiante puede pasar de aprobado a reprobado o al revés.</li>
                            <li>La acción <strong>no se puede deshacer</strong> desde el sistema; queda registrada en Auditoría con el detalle de cada cambio.</li>
                            <li><strong>Siempre analizá primero</strong> y revisá el detalle antes de aplicar.</li>
                        </ul>
                    </div>

                    <div class="form-row align-items-end">
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold mb-1">Gestión</label>
                                <input type="text" class="form-control" value="{{ $gestion }}" readonly>
                                <input type="hidden" id="recalc_gestion" value="{{ $gestion }}">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold mb-1">Trimestre a recalcular</label>
                                <select id="recalc_periodo" class="form-control">
                                    <option value="">Todos los trimestres de {{ $gestion }}</option>
                                    @foreach($periodos as $p)
                                        <option value="{{ $p->periodo_id }}">
                                            {{ $p->periodo_nombre }} ({{ $p->periodo_numero }}°)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <button type="button" class="btn btn-block" id="btnAnalizar"
                                        style="background:#2c3e50;color:#fff;">
                                    <i class="fas fa-search mr-1"></i>Analizar notas afectadas
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="recalcResultado" class="mt-3" style="display:none;"></div>

                </div>
            </div>
        </div>
    </div>
    @endif
    </div>{{-- /tab-recalculo --}}
    </div>{{-- /tab-content --}}


</div>

{{-- Modal Periodo --}}
<div class="modal fade" id="modalPeriodo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;">
                <h5 class="modal-title" id="tituloPeriodo">Nuevo Periodo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.guardar-periodo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" id="periodo_id">
                <input type="hidden" name="periodo_gestion" value="{{ $gestion }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="periodo_nombre" id="periodo_nombre" class="form-control" required placeholder="Ej: 1er Trimestre">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>N° Orden <span class="text-danger">*</span></label>
                                <input type="number" name="periodo_numero" id="periodo_numero" class="form-control" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="periodo_fecha_inicio" id="periodo_fecha_inicio" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha Fin <span class="text-danger">*</span></label>
                                <input type="date" name="periodo_fecha_fin" id="periodo_fecha_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="periodo_estado" id="periodo_estado" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Dimensión --}}
<div class="modal fade" id="modalDimension" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#9b59b6,#8e44ad);color:#fff;">
                <h5 class="modal-title" id="tituloDimension">Nueva Dimensión</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.guardar-dimension') }}" method="POST">
                @csrf
                <input type="hidden" name="dimension_id" id="dimension_id">
                <input type="hidden" name="dimension_gestion" value="{{ $gestion }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="dimension_nombre" id="dimension_nombre" class="form-control" required placeholder="Ej: SER, SABER, HACER">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valor Máximo <span class="text-danger">*</span></label>
                                <input type="number" name="dimension_valor_max" id="dimension_valor_max" class="form-control" required min="1" max="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Columnas <span class="text-danger">*</span></label>
                                <input type="number" name="dimension_columnas" id="dimension_columnas" class="form-control" required min="1" max="10" value="1">
                                <small class="text-muted">Cantidad de sub-notas</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" name="dimension_orden" id="dimension_orden" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="dimension_estado" id="dimension_estado" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" style="background:#9b59b6;color:#fff;"><i class="fas fa-save mr-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- Modal confirmación de recálculo --}}
@if(auth()->user()->rol_id == 1)
<div class="modal fade" id="modalRecalc" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#c0392b,#922b21);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmar recálculo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.recalcular') }}" method="POST">
                @csrf
                <input type="hidden" name="gestion" id="conf_gestion">
                <input type="hidden" name="periodo_id" id="conf_periodo">
                {{-- alcance=todo aplica todo lo detectado; seleccion usa la lista de nota_id --}}
                <input type="hidden" name="alcance" id="conf_alcance" value="todo">
                <input type="hidden" name="notas" id="conf_notas">
                <div class="modal-body">
                    <p class="mb-2">Estás por reescribir el promedio trimestral de:</p>
                    <div class="alert alert-danger py-2 mb-3">
                        <div id="conf_resumen" style="font-size:0.95rem;"></div>
                    </div>
                    <p class="mb-2 text-muted" style="font-size:0.9rem;">
                        Las notas por celda no se tocan. La acción no se puede deshacer desde el
                        sistema y queda registrada en Auditoría.
                    </p>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Escribí <code>RECALCULAR</code> para habilitar el botón</label>
                        <input type="text" name="confirmacion" id="conf_texto" class="form-control"
                               autocomplete="off" placeholder="RECALCULAR">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmarRecalc" disabled>
                        <i class="fas fa-calculator mr-1"></i>Aplicar recálculo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
function modalPeriodo(data = null) {
    if (data) {
        $('#tituloPeriodo').text('Editar Periodo');
        $('#periodo_id').val(data.periodo_id);
        $('#periodo_nombre').val(data.periodo_nombre);
        $('#periodo_numero').val(data.periodo_numero);
        $('#periodo_fecha_inicio').val(data.periodo_fecha_inicio);
        $('#periodo_fecha_fin').val(data.periodo_fecha_fin);
        $('#periodo_estado').val(data.periodo_estado);
    } else {
        $('#tituloPeriodo').text('Nuevo Periodo');
        $('#modalPeriodo form')[0].reset();
        $('#periodo_id').val('');
    }
    $('#modalPeriodo').modal('show');
}

function modalDimension(data = null) {
    if (data) {
        $('#tituloDimension').text('Editar Dimensión');
        $('#dimension_id').val(data.dimension_id);
        $('#dimension_nombre').val(data.dimension_nombre);
        $('#dimension_valor_max').val(data.dimension_valor_max);
        $('#dimension_columnas').val(data.dimension_columnas);
        $('#dimension_orden').val(data.dimension_orden);
        $('#dimension_estado').val(data.dimension_estado);
    } else {
        $('#tituloDimension').text('Nueva Dimensión');
        $('#modalDimension form')[0].reset();
        $('#dimension_id').val('');
    }
    $('#modalDimension').modal('show');
}

@if(auth()->user()->rol_id == 1)
// ═══════════════════════════════════════════════════════════════
// Recálculo de promedios ya guardados
// ═══════════════════════════════════════════════════════════════
(function () {
    var analisis = null;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    // ── Píldora de situación (aprobado / reprobado) ──
    function pill(txt, ok, tenue) {
        var fondo = ok ? (tenue ? '#eaf7ee' : '#d4edda') : (tenue ? '#fdeeec' : '#f8d7da');
        var borde = ok ? '#8fd3a4' : '#eba9a3';
        var color = ok ? '#1b6b34' : '#8c2018';
        return '<span style="display:inline-block;padding:1px 7px;border-radius:10px;' +
               'background:' + fondo + ';border:1px solid ' + borde + ';color:' + color + ';' +
               'font-size:0.68rem;font-weight:700;letter-spacing:0.3px;white-space:nowrap;">' + txt + '</span>';
    }

    function celdaSituacion(f) {
        if (!f.flip) return pill(f.despues, f.despues === 'APROBADO', true);
        return '<span style="white-space:nowrap;">' +
               pill(f.antes, f.antes === 'APROBADO', true) +
               '<i class="fas fa-long-arrow-alt-right mx-1" style="color:#c0392b;"></i>' +
               pill(f.despues, f.despues === 'APROBADO', false) +
               '</span>';
    }

    function chipDelta(d) {
        var sube = d > 0;
        return '<span style="display:inline-block;min-width:30px;padding:1px 5px;border-radius:3px;' +
               'font-weight:700;font-size:0.72rem;background:' + (sube ? '#eaf7ee' : '#fdeeec') +
               ';color:' + (sube ? '#1b6b34' : '#8c2018') + ';">' + (sube ? '+' : '') + d + '</span>';
    }

    function tile(valor, etiqueta, color) {
        return '<div class="col-6 col-md-3 col-xl-2 mb-2">' +
            '<div style="border:1px solid #e4e7ea;border-left:3px solid ' + color + ';border-radius:4px;padding:8px 10px;background:#fff;height:100%;">' +
              '<div style="font-size:1.5rem;font-weight:700;line-height:1.1;color:' + color + ';">' + valor + '</div>' +
              '<div style="font-size:0.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:0.3px;">' + etiqueta + '</div>' +
            '</div></div>';
    }

    function chk(attr, val, extra) {
        return '<input type="checkbox" class="' + extra + '" ' + attr + '="' + val + '" checked ' +
               'style="cursor:pointer;width:15px;height:15px;vertical-align:middle;">';
    }

    // ── Render principal ──
    function pintar(d) {
        var h = '';

        if (d.afectadas === 0) {
            h += '<div class="alert alert-success-modern">' +
                 '<i class="fas fa-check-circle mr-2"></i>' +
                 'No hay notas mal calculadas en este alcance. ' +
                 (d.solo_decimal > 0
                    ? 'Sí quedan <strong>' + d.solo_decimal + '</strong> nota(s) sin el decimal auxiliar del cuadro de honor; aplicar sólo completaría ese dato, sin cambiar ningún promedio.'
                    : 'No hay nada que corregir.') +
                 '</div>';
            if (d.solo_decimal === 0) return h;
        }

        if (d.afectadas > 0) {
            h += '<div class="row mb-2">';
            h += tile(d.afectadas, 'Notas a corregir', '#e67e22');
            h += tile(d.porcentaje + '%', 'del total (' + d.total_notas + ')', '#7f8c8d');
            h += tile(d.n_estudiantes, 'Estudiantes', '#2980b9');
            h += tile(d.n_cursos, 'Cursos', '#8e44ad');
            h += tile(d.n_materias, 'Materias', '#16a085');
            h += tile(d.n_docentes, 'Docentes', '#2c3e50');
            h += tile(d.aprobadas, 'Ya APROBADAS', d.aprobadas > 0 ? '#c0392b' : '#7f8c8d');
            h += tile(d.cambia_situacion, 'Cambian situación', d.cambia_situacion > 0 ? '#c0392b' : '#27ae60');
            h += '</div>';

            h += '<p class="text-muted mb-3" style="font-size:0.85rem;">' +
                 '<i class="fas fa-info-circle mr-1"></i>' +
                 d.sube + ' suben, ' + d.baja + ' bajan; diferencia máxima ' + d.max_delta + ' punto(s), ' +
                 'en ' + d.n_asignaciones + ' asignación(es) materia+curso+trimestre.' +
                 (d.solo_decimal > 0 ? ' Además se completará el decimal del cuadro de honor en ' + d.solo_decimal + ' nota(s) ya correctas (sólo si aplicás todo).' : '') +
                 '</p>';
        }

        if (d.cambia_situacion > 0) {
            h += '<div class="alert alert-danger py-2">' +
                 '<i class="fas fa-user-times mr-2"></i>' +
                 '<strong>' + d.cambia_situacion + ' estudiante(s) cambian de aprobado/reprobado.</strong> ' +
                 'Están resaltados en el detalle y sus cursos aparecen primero.' +
                 '</div>';
        }

        // ── Barra de selección ──
        h += '<div class="d-flex flex-wrap justify-content-between align-items-center mb-2" ' +
             'style="gap:8px;background:#f7f9fa;border:1px solid #e4e7ea;border-radius:4px;padding:8px 10px;">' +
             '<div>' +
               '<button type="button" class="btn btn-sm btn-outline-secondary" id="selTodo"><i class="far fa-check-square mr-1"></i>Marcar todo</button> ' +
               '<button type="button" class="btn btn-sm btn-outline-secondary" id="selNada"><i class="far fa-square mr-1"></i>Desmarcar todo</button> ' +
               '<button type="button" class="btn btn-sm btn-outline-danger" id="selFlips"><i class="fas fa-user-times mr-1"></i>Sólo los que cambian situación</button> ' +
               '<button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleTodo"><i class="fas fa-expand-alt mr-1"></i>Expandir todo</button>' +
             '</div>' +
             '<div id="selContador" style="font-weight:700;color:#2c3e50;"></div>' +
             '</div>';

        // ── Árbol curso → materia/trimestre → estudiante ──
        var estados = {0: 'Borrador', 1: 'Enviado', 2: 'Aprobado', 3: 'Rechazado'};
        var badges  = {0: 'secondary', 1: 'info', 2: 'success', 3: 'danger'};

        h += '<div style="max-height:520px;overflow:auto;border:1px solid #e4e7ea;border-radius:4px;">';

        d.cursos.forEach(function (c, ci) {
            // Cabecera de curso
            h += '<div style="border-bottom:1px solid #e4e7ea;">';
            h += '<div class="d-flex align-items-center curso-head" data-ci="' + ci + '" ' +
                 'style="gap:8px;padding:8px 10px;background:#2c3e50;color:#fff;cursor:pointer;">' +
                 '<span onclick="event.stopPropagation();">' + chk('data-curso', ci, 'chk-curso') + '</span>' +
                 '<i class="fas fa-chevron-right curso-ico" style="width:12px;"></i>' +
                 '<strong style="flex:1;">' + esc(c.nombre) + '</strong>' +
                 '<span class="badge badge-light">' + c.notas + ' nota(s)</span>' +
                 '<span class="badge badge-light">' + c.estudiantes + ' est.</span>' +
                 (c.aprobadas > 0 ? '<span class="badge badge-warning">' + c.aprobadas + ' aprob.</span>' : '') +
                 (c.flips > 0 ? '<span class="badge badge-danger">' + c.flips + ' cambian sit.</span>' : '') +
                 '</div>';

            // Cuerpo del curso
            h += '<div class="curso-body" data-ci="' + ci + '" style="display:none;">';

            c.asignaciones.forEach(function (a, ai) {
                h += '<div style="border-top:1px solid #eef1f3;">';
                h += '<div class="d-flex align-items-center asig-head" data-ci="' + ci + '" data-ai="' + ai + '" ' +
                     'style="gap:8px;padding:6px 10px 6px 26px;background:#f7f9fa;cursor:pointer;">' +
                     '<span onclick="event.stopPropagation();">' + chk('data-asig', ci + '-' + ai, 'chk-asig') + '</span>' +
                     '<i class="fas fa-chevron-right asig-ico" style="width:12px;color:#7f8c8d;"></i>' +
                     '<strong style="flex:1;">' + esc(a.materia) + '</strong>' +
                     '<small class="text-muted">' + esc(a.docente) + '</small>' +
                     '<span class="badge badge-secondary">' + esc(a.periodo) + '</span>' +
                     '<span class="badge badge-' + (badges[a.estado] || 'secondary') + '">' + (estados[a.estado] || a.estado) + '</span>' +
                     '<span class="badge badge-light border">' + a.notas + '</span>' +
                     (a.flips > 0 ? '<span class="badge badge-danger">' + a.flips + '</span>' : '') +
                     '</div>';

                h += '<div class="asig-body" data-ci="' + ci + '" data-ai="' + ai + '" style="display:none;padding:0 10px 8px 26px;">';
                h += '<table class="table table-sm mb-0" style="font-size:0.8rem;background:#fff;">' +
                     '<thead><tr style="background:#fbfcfd;">' +
                     '<th style="width:34px;"></th>' +
                     '<th style="width:28px;" title="Ver el cálculo"></th>' +
                     '<th style="width:42px;">N°</th>' +
                     '<th>Estudiante</th>' +
                     '<th class="text-center" style="width:120px;">Guardado hoy</th>' +
                     '<th class="text-center" style="width:90px;">Quedará</th>' +
                     '<th class="text-center" style="width:66px;">Dif.</th>' +
                     '<th class="text-center" style="width:210px;">Situación</th>' +
                     '</tr></thead><tbody>';

                a.filas.forEach(function (f) {
                    h += '<tr class="fila-nota"' + (f.flip ? ' style="background:#fdf3f2;"' : '') + '>' +
                         '<td class="text-center">' + chk('data-nota', f.nota_id, 'chk-nota') + '</td>' +
                         '<td class="text-center"><button type="button" class="btn btn-link p-0 btn-desglose" ' +
                            'data-nota="' + f.nota_id + '" title="Ver cómo se calcula y qué redondeo se aplica" ' +
                            'style="font-size:0.72rem;"><i class="fas fa-chevron-right"></i></button></td>' +
                         '<td class="text-muted">' + (f.nro == null ? '' : f.nro) + '</td>' +
                         '<td>' + esc(f.nombre) +
                            (f.flip ? ' <i class="fas fa-exclamation-triangle text-danger ml-1" title="Cambia de situación"></i>' : '') + '</td>' +
                         '<td class="text-center"><span style="color:#7f8c8d;">' + f.actual +
                            ' <small>(' + f.crudo + ')</small></span></td>' +
                         '<td class="text-center"><strong style="font-size:1rem;">' + f.nuevo + '</strong></td>' +
                         '<td class="text-center">' + chipDelta(f.delta) + '</td>' +
                         '<td class="text-center">' + celdaSituacion(f) + '</td>' +
                         '</tr>';
                });

                h += '</tbody></table></div></div>';
            });

            h += '</div></div>';
        });

        h += '</div>';

        if (d.truncado) {
            h += '<small class="text-muted d-block mt-1">El listado se truncó por volumen; sólo se aplicará lo que ves y está marcado.</small>';
        }

        h += '<div class="text-right mt-3">' +
             '<button type="button" class="btn btn-danger" id="btnAbrirConfirm">' +
             '<i class="fas fa-calculator mr-1"></i>Aplicar recálculo</button></div>';

        return h;
    }

    // ── Selección ──
    function idsSeleccionados() {
        return $('.chk-nota:checked').map(function () { return $(this).data('nota'); }).get();
    }

    function statsSeleccion() {
        var sel = {};
        idsSeleccionados().forEach(function (id) { sel[id] = true; });
        var n = 0, aprob = 0, flips = 0, cursos = {}, mats = {}, ests = {};
        (analisis.cursos || []).forEach(function (c) {
            c.asignaciones.forEach(function (a) {
                a.filas.forEach(function (f) {
                    if (!sel[f.nota_id]) return;
                    n++;
                    if (a.estado === 2) aprob++;
                    if (f.flip) flips++;
                    cursos[c.codigo] = true;
                    mats[a.materia] = true;
                    ests[f.nombre] = true;
                });
            });
        });
        return {
            n: n, aprobadas: aprob, flips: flips,
            cursos: Object.keys(cursos).length,
            materias: Object.keys(mats).length,
            estudiantes: Object.keys(ests).length
        };
    }

    function refrescarContador() {
        var s = statsSeleccion();
        var total = analisis ? analisis.afectadas : 0;
        var txt = '<i class="fas fa-check-double mr-1"></i>' + s.n + ' de ' + total + ' nota(s) seleccionada(s)';
        if (s.flips > 0) txt += ' · <span style="color:#c0392b;">' + s.flips + ' cambian situación</span>';
        $('#selContador').html(txt);
        $('#btnAbrirConfirm').prop('disabled', s.n === 0)
            .html('<i class="fas fa-calculator mr-1"></i>Aplicar recálculo a ' + s.n + ' nota(s)');
    }

    // Sincroniza los padres a partir de los hijos
    function sincronizarPadres() {
        $('.chk-asig').each(function () {
            var k = $(this).data('asig').toString().split('-');
            var $f = $('.asig-body[data-ci="' + k[0] + '"][data-ai="' + k[1] + '"] .chk-nota');
            var m = $f.filter(':checked').length;
            this.checked = m > 0 && m === $f.length;
            this.indeterminate = m > 0 && m < $f.length;
        });
        $('.chk-curso').each(function () {
            var ci = $(this).data('curso');
            var $f = $('.curso-body[data-ci="' + ci + '"] .chk-nota');
            var m = $f.filter(':checked').length;
            this.checked = m > 0 && m === $f.length;
            this.indeterminate = m > 0 && m < $f.length;
        });
        refrescarContador();
    }

    // ── Eventos ──
    $('#btnAnalizar').on('click', function () {
        var $b = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Analizando...');
        var $out = $('#recalcResultado').hide();

        $.get("{{ route('notas.recalculo-preview') }}", {
            gestion: $('#recalc_gestion').val(),
            periodo_id: $('#recalc_periodo').val()
        }).done(function (d) {
            analisis = d;
            $out.html(pintar(d)).show();
            refrescarContador();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.mensaje) || 'No se pudo completar el análisis.';
            $out.html('<div class="alert alert-danger"><i class="fas fa-times-circle mr-2"></i>' + esc(msg) + '</div>').show();
        }).always(function () {
            $b.prop('disabled', false).html('<i class="fas fa-search mr-1"></i>Analizar notas afectadas');
        });
    });

    var $res = $('#recalcResultado');

    $res.on('click', '.curso-head', function () {
        var ci = $(this).data('ci');
        $('.curso-body[data-ci="' + ci + '"]').toggle();
        $(this).find('.curso-ico').toggleClass('fa-chevron-right fa-chevron-down');
    });

    $res.on('click', '.asig-head', function () {
        var ci = $(this).data('ci'), ai = $(this).data('ai');
        $('.asig-body[data-ci="' + ci + '"][data-ai="' + ai + '"]').toggle();
        $(this).find('.asig-ico').toggleClass('fa-chevron-right fa-chevron-down');
    });

    $res.on('change', '.chk-curso', function () {
        var ci = $(this).data('curso'), on = this.checked;
        $('.curso-body[data-ci="' + ci + '"] .chk-nota').prop('checked', on);
        sincronizarPadres();
    });

    $res.on('change', '.chk-asig', function () {
        var k = $(this).data('asig').toString().split('-'), on = this.checked;
        $('.asig-body[data-ci="' + k[0] + '"][data-ai="' + k[1] + '"] .chk-nota').prop('checked', on);
        sincronizarPadres();
    });

    $res.on('change', '.chk-nota', sincronizarPadres);

    $res.on('click', '#selTodo', function () { $('.chk-nota').prop('checked', true);  sincronizarPadres(); });
    $res.on('click', '#selNada', function () { $('.chk-nota').prop('checked', false); sincronizarPadres(); });

    $res.on('click', '#selFlips', function () {
        var flips = {};
        (analisis.cursos || []).forEach(function (c) {
            c.asignaciones.forEach(function (a) {
                a.filas.forEach(function (f) { if (f.flip) flips[f.nota_id] = true; });
            });
        });
        $('.chk-nota').each(function () { this.checked = !!flips[$(this).data('nota')]; });
        $('.curso-body, .asig-body').show();
        $('.curso-ico, .asig-ico').removeClass('fa-chevron-right').addClass('fa-chevron-down');
        sincronizarPadres();
    });

    $res.on('click', '#btnToggleTodo', function () {
        var abrir = $('.curso-body:visible').length === 0;
        $('.curso-body').toggle(abrir);
        $('.asig-body').toggle(abrir);
        $('.curso-ico, .asig-ico').toggleClass('fa-chevron-right', !abrir).toggleClass('fa-chevron-down', abrir);
        $(this).html(abrir ? '<i class="fas fa-compress-alt mr-1"></i>Contraer todo'
                           : '<i class="fas fa-expand-alt mr-1"></i>Expandir todo');
    });

    // ── Confirmación ──
    $res.on('click', '#btnAbrirConfirm', function () {
        if (!analisis) return;
        var s = statsSeleccion();
        if (s.n === 0) return;

        var ids   = idsSeleccionados();
        var todo  = ids.length === analisis.afectadas;
        var pTxt  = $('#recalc_periodo option:selected').text().trim();

        $('#conf_gestion').val(analisis.gestion);
        $('#conf_periodo').val(analisis.periodo_id || '');
        $('#conf_alcance').val(todo ? 'todo' : 'seleccion');
        $('#conf_notas').val(todo ? '' : ids.join(','));

        $('#conf_resumen').html(
            '<strong>' + s.n + '</strong> nota(s) en <strong>' + s.cursos + '</strong> curso(s), ' +
            '<strong>' + s.materias + '</strong> materia(s) y <strong>' + s.estudiantes + '</strong> estudiante(s).<br>' +
            'Alcance: ' + esc(pTxt) + (todo ? ' — todas las detectadas.' : ' — selección manual.') + '<br>' +
            (s.aprobadas > 0 ? '<strong>' + s.aprobadas + '</strong> de ellas están APROBADAS.<br>' : '') +
            (s.flips > 0
                ? '<strong style="color:#7b241c;">' + s.flips + ' cambian de aprobado/reprobado.</strong>'
                : 'Ninguna cambia de aprobado/reprobado.') +
            (todo && analisis.solo_decimal > 0
                ? '<br><small>Además se completará el decimal del cuadro de honor en ' + analisis.solo_decimal + ' nota(s) ya correctas.</small>'
                : '')
        );
        $('#conf_texto').val('');
        $('#btnConfirmarRecalc').prop('disabled', true);
        $('#modalRecalc').modal('show');
    });


    // ═══ Desglose del redondeo de una nota (tercer nivel) ═══
    var cacheDesglose = {};

    var TIPO = {
        exacto:    {txt: 'Exacto',            col: '#7f8c8d', ico: 'fa-equals',
                    ayuda: 'El promedio ya es un entero: no hubo redondeo.'},
        medio:     {txt: 'Medio punto ↑',     col: '#8e44ad', ico: 'fa-balance-scale',
                    ayuda: 'El promedio termina en .50 exacto. Por convención sube al entero siguiente.'},
        arriba:    {txt: 'Hacia arriba',      col: '#1b6b34', ico: 'fa-arrow-up',
                    ayuda: 'La fracción es mayor a .50, así que sube al entero siguiente.'},
        abajo:     {txt: 'Hacia abajo',       col: '#8c2018', ico: 'fa-arrow-down',
                    ayuda: 'La fracción es menor a .50, así que baja al entero anterior.'},
        sin_notas: {txt: 'Sin notas',         col: '#95a5a6', ico: 'fa-minus',
                    ayuda: 'No hay ninguna celda cargada en esta dimensión: cuenta como 0.'}
    };

    function num(v, dec) {
        return Number(v).toFixed(dec === undefined ? 2 : dec);
    }

    function pintarDesglose(d) {
        var h = '<div style="background:#fff;border:1px solid #d6dce1;border-radius:4px;padding:10px 12px;">';

        h += '<div class="mb-2" style="font-size:0.78rem;color:#6c757d;">' +
             '<i class="fas fa-search-plus mr-1"></i>Cómo se arma el promedio de <strong>' +
             esc(d.estudiante) + '</strong> en ' + esc(d.materia) + ' — ' + esc(d.periodo) +
             '</div>';

        h += '<table class="table table-sm mb-2" style="font-size:0.78rem;">' +
             '<thead><tr style="background:#f4f6f8;">' +
             '<th>Dimensión</th>' +
             '<th>Notas cargadas por el docente</th>' +
             '<th class="text-center" style="width:118px;">Cálculo</th>' +
             '<th class="text-center" style="width:82px;">Promedio</th>' +
             '<th class="text-center" style="width:62px;">Queda</th>' +
             '<th class="text-center" style="width:150px;">Redondeo aplicado</th>' +
             '</tr></thead><tbody>';

        d.dimensiones.forEach(function (f) {
            var t = TIPO[f.tipo] || TIPO.exacto;
            var celdas = f.celdas.length
                ? f.celdas.map(function (v) {
                      return '<span style="display:inline-block;background:#eef2f5;border:1px solid #dde3e8;' +
                             'border-radius:3px;padding:0 5px;margin:1px;font-variant-numeric:tabular-nums;">' +
                             (v % 1 === 0 ? v : num(v)) + '</span>';
                  }).join('')
                : '<span class="text-muted">— sin notas —</span>';

            var calculo = f.cuantas === 0 ? '<span class="text-muted">—</span>'
                : (f.una_columna
                    ? '<span class="text-muted">valor directo</span>'
                    : num(f.suma) + ' ÷ ' + f.cuantas);

            h += '<tr>' +
                 '<td><strong>' + esc(f.nombre) + '</strong>' +
                    '<small class="text-muted"> /' + f.maximo + '</small>' +
                    (f.vacias > 0 ? '<br><small class="text-muted">' + f.vacias + ' celda(s) vacía(s), no promedian</small>' : '') +
                 '</td>' +
                 '<td>' + celdas + '</td>' +
                 '<td class="text-center" style="font-variant-numeric:tabular-nums;">' + calculo + '</td>' +
                 '<td class="text-center" style="font-variant-numeric:tabular-nums;font-weight:600;">' +
                    num(f.promedio, 4).replace(/0+$/, '').replace(/\.$/, '') +
                    (f.topado ? '<br><small style="color:#c0392b;">topado a ' + f.maximo + '</small>' : '') +
                 '</td>' +
                 '<td class="text-center"><strong style="font-size:1rem;">' + f.redondeado + '</strong></td>' +
                 '<td class="text-center">' +
                    '<span title="' + t.ayuda + '" style="color:' + t.col + ';font-weight:600;white-space:nowrap;">' +
                    '<i class="fas ' + t.ico + ' mr-1"></i>' + t.txt + '</span>' +
                    '<br><small style="color:' + t.col + ';font-variant-numeric:tabular-nums;">' +
                    (f.delta >= 0 ? '+' : '') + num(f.delta) + '</small>' +
                 '</td>' +
                 '</tr>';
        });

        h += '</tbody></table>';

        // ── Las dos formas de sumar, lado a lado ──
        var sumaRed = d.dimensiones.map(function (f) { return f.redondeado; }).join(' + ');
        var sumaExa = d.dimensiones.map(function (f) {
            return num(f.promedio, 4).replace(/0+$/, '').replace(/\.$/, '');
        }).join(' + ');

        h += '<div class="row" style="font-size:0.78rem;">';

        h += '<div class="col-md-6 mb-2"><div style="border:1px solid #8fd3a4;background:#f2fbf5;border-radius:4px;padding:8px 10px;height:100%;">' +
             '<div style="font-weight:700;color:#1b6b34;margin-bottom:3px;">' +
               '<i class="fas fa-check-circle mr-1"></i>Método correcto — el que ve el docente</div>' +
             '<div style="font-variant-numeric:tabular-nums;">Se redondea cada dimensión y se suman:</div>' +
             '<div style="font-variant-numeric:tabular-nums;margin-top:2px;">' + sumaRed +
               ' = <strong style="font-size:1.15rem;color:#1b6b34;">' + d.oficial + '</strong></div>' +
             '</div></div>';

        h += '<div class="col-md-6 mb-2"><div style="border:1px solid #eba9a3;background:#fdf4f3;border-radius:4px;padding:8px 10px;height:100%;">' +
             '<div style="font-weight:700;color:#8c2018;margin-bottom:3px;">' +
               '<i class="fas fa-times-circle mr-1"></i>Método viejo — el que quedó guardado</div>' +
             '<div style="font-variant-numeric:tabular-nums;">Se suman los decimales y se redondea al final:</div>' +
             '<div style="font-variant-numeric:tabular-nums;margin-top:2px;">' + sumaExa +
               ' = ' + num(d.exacto) + ' → <strong style="font-size:1.15rem;color:#8c2018;">' + d.metodo_viejo + '</strong></div>' +
             '</div></div>';

        h += '</div>';

        // ── Conclusión en una línea ──
        var acum = d.dimensiones.reduce(function (s, f) { return s + f.delta; }, 0);
        h += '<div style="border-left:3px solid #e67e22;background:#fffaf4;padding:6px 10px;font-size:0.78rem;">' +
             '<i class="fas fa-lightbulb mr-1" style="color:#e67e22;"></i>' +
             'Los redondeos de cada dimensión suman <strong>' + (acum >= 0 ? '+' : '') + num(acum) + '</strong>, ' +
             'y como no se cancelan entre sí, la suma de los enteros (<strong>' + d.oficial + '</strong>) ' +
             'no coincide con el redondeo del total (<strong>' + d.metodo_viejo + '</strong>). ' +
             'El decimal exacto <strong>' + num(d.exacto) + '</strong> se conserva para el cuadro de honor.' +
             '</div>';

        h += '</div>';
        return h;
    }

    $res.on('click', '.btn-desglose', function (e) {
        e.stopPropagation();
        var $btn  = $(this);
        var id    = $btn.data('nota');
        var $fila = $btn.closest('tr');
        var $det  = $fila.next('.fila-desglose');

        if ($det.length) {                       // ya estaba cargado: alternar
            $det.toggle();
            $btn.find('i').toggleClass('fa-chevron-down fa-chevron-right');
            return;
        }

        var cols = $fila.children().length;
        $fila.after('<tr class="fila-desglose"><td colspan="' + cols + '" style="background:#f4f6f8;padding:8px;">' +
                    '<i class="fas fa-spinner fa-spin mr-1"></i>Calculando...</td></tr>');
        var $nuevo = $fila.next('.fila-desglose');
        $btn.find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');

        if (cacheDesglose[id]) {
            $nuevo.find('td').html(pintarDesglose(cacheDesglose[id]));
            return;
        }

        $.get("{{ route('notas.recalculo-desglose') }}", {nota_id: id}).done(function (d) {
            cacheDesglose[id] = d;
            $nuevo.find('td').html(pintarDesglose(d));
        }).fail(function (xhr) {
            var m = (xhr.responseJSON && xhr.responseJSON.mensaje) || 'No se pudo obtener el desglose.';
            $nuevo.find('td').html('<div class="alert alert-danger mb-0 py-2">' + esc(m) + '</div>');
        });
    });

    $('#conf_texto').on('input', function () {
        $('#btnConfirmarRecalc').prop('disabled', $(this).val().trim().toUpperCase() !== 'RECALCULAR');
    });
})();
@endif
</script>
@endsection
