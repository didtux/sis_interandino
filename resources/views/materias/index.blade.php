@extends('layouts.app')

@section('content')
@php $tab = request('tab', 'materias'); @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <ul class="nav nav-tabs card-header-tabs" id="materiasTabs" role="tablist" style="border-bottom:none;">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'materias' ? 'active' : '' }}" data-toggle="tab" href="#tabMaterias" role="tab">
                        <i class="fas fa-book mr-1"></i>Materias
                        <span class="badge badge-secondary ml-1">{{ $materias->total() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'asociar' ? 'active' : '' }}" data-toggle="tab" href="#tabAsociar" role="tab">
                        <i class="fas fa-link mr-1"></i>Asignar Campo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'grupos' ? 'active' : '' }}" data-toggle="tab" href="#tabGrupos" role="tab">
                        <i class="fas fa-layer-group mr-1"></i>Campos / Áreas
                        <span class="badge ml-1" style="background:#8e44ad;color:#fff;">{{ $grupos->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'por-curso' ? 'active' : '' }}" data-toggle="tab" href="#tabPorCurso" role="tab">
                        <i class="fas fa-graduation-cap mr-1"></i>Por Curso
                        <span class="badge badge-warning ml-1" title="Configuración específica por curso">NUEVO</span>
                    </a>
                </li>
            </ul>
            @puede('materias', 'crear')
            <a href="{{ route('materias.create') }}" class="btn btn-primary-modern" id="btnNuevaMateria">
                <i class="fas fa-plus mr-1"></i>Nueva Materia
            </a>
            @endpuede
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success-modern">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            <div class="tab-content">

            {{-- ===== TAB MATERIAS ===== --}}
            <div class="tab-pane fade {{ $tab === 'materias' ? 'show active' : '' }}" id="tabMaterias" role="tabpanel">
                <div class="card-body p-0">
                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Campo (Área)</th>
                                    <th class="text-center" title="¿Suma al promedio del campo?">Promedia</th>
                                    <th>Orden</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materias as $materia)
                                    <tr>
                                        <td><span class="modern-badge badge-primary-modern">{{ $materia->mat_codigo }}</span></td>
                                        <td>{{ $materia->mat_nombre }}</td>
                                        <td>
                                            @if($materia->mat_campo)
                                                <span class="badge badge-info">{{ $materia->mat_campo }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if((int) $materia->mat_promediable === 1)
                                                <i class="fas fa-check-circle text-success" title="Suma al promedio"></i>
                                            @else
                                                <i class="far fa-circle text-muted" title="No suma"></i>
                                            @endif
                                        </td>
                                        <td>{{ $materia->mat_orden }}</td>
                                        <td>
                                            <div class="action-buttons">
                                                @puede('materias', 'editar')
                                                <a href="{{ route('materias.edit', $materia->mat_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('materias', 'eliminar')
                                                <form action="{{ route('materias.destroy', $materia->mat_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Eliminar esta materia?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty-state"><i class="fas fa-book"></i><h5>No hay materias registradas</h5></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">{{ $materias->links() }}</div>
                </div>
            </div>
            {{-- ===== /TAB MATERIAS ===== --}}

            {{-- ===== TAB ASIGNAR CAMPO (config GLOBAL — valores base) ===== --}}
            <div class="tab-pane fade {{ $tab === 'asociar' ? 'show active' : '' }}" id="tabAsociar" role="tabpanel">
                <div class="card-body p-0">
                    <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Configuración global (valores base).</strong>
                        Estos campos se aplican como <em>plantilla</em> cuando una materia se asigna a un curso nuevo.
                        Para ajustar por curso específico, ve a la pestaña <strong>"Por Curso"</strong>.
                    </div>

                    <div class="row">
                        {{-- Columna izquierda: formulario --}}
                        <div class="col-md-6">
                            <div class="card border" style="border-color:#dee2e6 !important;">
                                <div class="card-header py-2" style="background:#f8f9fa;">
                                    <strong><i class="fas fa-plus-circle text-primary mr-1"></i>Asignar Campo a Materias</strong>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('materias.asignar-campo') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label class="small font-weight-bold">1️⃣ Campo / Área Curricular</label>
                                            <input type="text" name="mat_campo" class="form-control" list="campos_existentes" required
                                                   placeholder="Ej: COMUNIDAD Y SOCIEDAD">
                                            <datalist id="campos_existentes">
                                                @foreach($grupos as $g)
                                                    <option value="{{ $g->campo }}">
                                                @endforeach
                                                <option value="COMUNIDAD Y SOCIEDAD">
                                                <option value="CIENCIA Y TECNOLOGÍA">
                                                <option value="VIDA TIERRA Y TERRITORIO">
                                                <option value="COSMOS Y PENSAMIENTO">
                                            </datalist>
                                            <small class="text-muted">Escribe el nombre del campo o elige uno existente</small>
                                        </div>
                                        <div class="form-group">
                                            <label class="small font-weight-bold">2️⃣ Materias a Asignar</label>
                                            <select name="materias[]" id="asignar_materias" class="form-control select2-multi" multiple required style="width:100%;">
                                                @foreach($todasMaterias as $m)
                                                    <option value="{{ $m->mat_codigo }}">
                                                        {{ $m->mat_nombre }}
                                                        @if($m->mat_campo) — actualmente en {{ $m->mat_campo }}@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted"><i class="fas fa-arrow-right mr-1"></i>Las materias seleccionadas pasarán al campo elegido</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-save mr-1"></i>3️⃣ Aplicar Asignación
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- Columna derecha: vista actual --}}
                        <div class="col-md-6">
                            <div class="card border" style="border-color:#dee2e6 !important;">
                                <div class="card-header py-2" style="background:#f8f9fa;">
                                    <strong><i class="fas fa-eye text-success mr-1"></i>Estado Actual (Global)</strong>
                                </div>
                                <div class="card-body" style="max-height:380px;overflow-y:auto;">
                                    @forelse($grupos as $g)
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong style="color:#8e44ad;font-size:13px;">{{ $g->campo }}</strong>
                                                <span class="badge badge-light border">{{ $g->total }} mat.</span>
                                            </div>
                                            @foreach($g->materias as $m)
                                                <span class="badge {{ $m->mat_promediable ? 'badge-success' : 'badge-light border' }} mr-1 mb-1" style="font-size:11px;">
                                                    @if($m->mat_promediable)<i class="fas fa-check-circle mr-1"></i>@endif{{ $m->mat_nombre }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small mt-3">Aún no hay campos asignados.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ===== /TAB ASIGNAR CAMPO ===== --}}

            {{-- ===== TAB GRUPOS / CAMPOS ===== --}}
            <div class="tab-pane fade {{ $tab === 'grupos' ? 'show active' : '' }}" id="tabGrupos" role="tabpanel">
                <div class="card-body p-0">
                    <p class="text-muted small mb-3">
                        Cada <strong>Campo / Área</strong> es un grupo natural que agrupa sus materias.
                        Haz clic en un campo para ver sus materias y marcar cuáles aportan al promedio del campo.
                    </p>

                    @forelse($grupos as $g)
                        @php $collapseId = 'campo-collapse-'.\Illuminate\Support\Str::slug($g->campo); @endphp
                        <div class="card mb-2" style="border-left:4px solid #8e44ad;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center" style="cursor:pointer;" data-toggle="collapse" data-target="#{{ $collapseId }}">
                                    <div class="flex-grow-1">
                                        <strong style="color:#8e44ad;">
                                            <i class="fas fa-chevron-right mr-2 small chevron-toggle"></i>{{ $g->campo }}
                                        </strong>
                                        <small class="text-muted ml-2">
                                            {{ $g->total }} materias — promedio sobre <strong>{{ $g->promediables }}</strong>
                                        </small>
                                    </div>
                                </div>

                                <div class="collapse mt-2" id="{{ $collapseId }}">
                                    <hr class="my-2">
                                    <form action="{{ route('materias.guardar-promediables') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mat_campo" value="{{ $g->campo }}">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-info-circle mr-1"></i>Marca las materias que <strong>suman al promedio</strong> del campo:
                                        </small>
                                        <div class="row">
                                            @foreach($g->materias as $mat)
                                                @php $isProm = (int) $mat->mat_promediable === 1; @endphp
                                                <div class="col-md-6 mb-1">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="promediables[]"
                                                               value="{{ $mat->mat_codigo }}" id="prom-{{ $mat->mat_codigo }}"
                                                               {{ $isProm ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="prom-{{ $mat->mat_codigo }}">
                                                            <span class="badge {{ $isProm ? 'badge-success' : 'badge-light border' }}">{{ $mat->mat_nombre }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-success mt-2">
                                            <i class="fas fa-save mr-1"></i>Guardar promediables
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-layer-group" style="font-size:2rem;opacity:0.3;"></i>
                            <p class="mt-2 mb-0">No hay materias con campo asignado. Asigna campos desde la pestaña <em>Asignar Campo</em> o al editar cada materia.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            {{-- ===== /TAB GRUPOS ===== --}}

            {{-- ===== TAB POR CURSO ===== --}}
            <div class="tab-pane fade {{ $tab === 'por-curso' ? 'show active' : '' }}" id="tabPorCurso" role="tabpanel">
                <div class="card-body p-0">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                        <i class="fas fa-graduation-cap mr-1"></i>
                        <strong>Configuración por Curso.</strong>
                        Define para cada curso qué materias entran en qué campo, en qué orden aparecen
                        y cuáles suman al <strong>promedio</strong>. Esto es lo que aparece en boletines y centralizadores.
                    </div>

                    {{-- Selector de curso --}}
                    <form method="GET" action="{{ route('materias.index') }}" class="row mb-3">
                        <input type="hidden" name="tab" value="por-curso">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">1️⃣ Selecciona un curso para configurar</label>
                            <select name="cur_codigo" class="form-control select2-curso-config" onchange="this.form.submit()">
                                <option value="">— Seleccione un curso —</option>
                                @foreach($cursos as $c)
                                    <option value="{{ $c->cur_codigo }}" {{ $cursoSeleccionado && $cursoSeleccionado->cur_codigo === $c->cur_codigo ? 'selected' : '' }}>
                                        {{ $c->cur_nombre }} @if($c->cur_nivel)— {{ $c->cur_nivel }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($cursoSeleccionado)
                        <div class="col-md-6">
                            <label class="small font-weight-bold">¿Copiar config de otro curso? <small class="text-muted">(acelera setup)</small></label>
                            <div class="input-group">
                                <select id="copiarOrigen" class="form-control select2-curso-copiar">
                                    <option value="">— Curso origen —</option>
                                    @foreach($cursos as $c)
                                        @if($c->cur_codigo !== $cursoSeleccionado->cur_codigo)
                                            <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info" onclick="copiarConfigCurso()">
                                        <i class="fas fa-copy mr-1"></i>Copiar
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </form>

                    @if($cursoSeleccionado)
                        @if($matCursoConfig->isEmpty())
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-exclamation-circle" style="font-size:2rem;opacity:0.3;"></i>
                                <p class="mt-2">El curso <strong>{{ $cursoSeleccionado->cur_nombre }}</strong> no tiene materias asignadas todavía.</p>
                                <small>Asigna materias al curso desde el módulo <em>Cursos → Materias del Curso</em>.</small>
                            </div>
                        @else
                            <form action="{{ route('materias.guardar-por-curso') }}" method="POST" id="formPorCurso">
                                @csrf
                                <input type="hidden" name="cur_codigo" value="{{ $cursoSeleccionado->cur_codigo }}">

                                {{-- Resumen estadístico --}}
                                @php
                                    $totalMat = $matCursoConfig->count();
                                    $totalProm = $matCursoConfig->where('promediable', 1)->count();
                                    $camposUnicos = $matCursoConfig->pluck('campo')->filter()->unique()->count();
                                @endphp
                                <div class="row mb-3">
                                    <div class="col"><div class="p-2 border rounded text-center"><div class="text-muted small">Total materias</div><h4 class="mb-0">{{ $totalMat }}</h4></div></div>
                                    <div class="col"><div class="p-2 border rounded text-center" style="background:#e8f5e9;"><div class="text-muted small">Promedian</div><h4 class="mb-0 text-success">{{ $totalProm }}</h4></div></div>
                                    <div class="col"><div class="p-2 border rounded text-center" style="background:#f3e5f5;"><div class="text-muted small">Campos distintos</div><h4 class="mb-0" style="color:#8e44ad;">{{ $camposUnicos }}</h4></div></div>
                                </div>

                                {{-- Tabla editable --}}
                                <div class="alert alert-info py-2 mb-2" style="font-size:12px;">
                                    <i class="fas fa-arrows-alt mr-1"></i>
                                    Arrastra las filas desde el ícono <i class="fas fa-grip-vertical"></i> para reordenar.
                                    El número de <strong>Orden</strong> se actualiza solo de arriba hacia abajo.
                                </div>
                                <div class="table-responsive">
                                    <table class="modern-table" style="font-size:13px;">
                                        <thead>
                                            <tr style="background:#f8f9fa;">
                                                <th style="width:30px;"></th>
                                                <th style="width:70px;">Orden</th>
                                                <th>Materia</th>
                                                <th style="width:35%;">Campo / Área</th>
                                                <th class="text-center" style="width:120px;">¿Promedia?</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaPorCurso">
                                            @foreach($matCursoConfig as $row)
                                                <tr data-mat="{{ $row->mat_codigo }}">
                                                    <td class="drag-handle text-center" style="cursor:grab;color:#95a5a6;">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="config[{{ $row->mat_codigo }}][orden]"
                                                               class="form-control form-control-sm orden-input"
                                                               value="{{ $row->orden }}" min="1" max="999"
                                                               style="width:60px;" readonly>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $row->mat_nombre }}</strong>
                                                        <small class="text-muted d-block">{{ $row->mat_codigo }}</small>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="config[{{ $row->mat_codigo }}][campo]"
                                                               class="form-control form-control-sm campo-input"
                                                               value="{{ $row->campo }}" list="campos_por_curso"
                                                               placeholder="(sin campo)">
                                                    </td>
                                                    <td class="text-center">
                                                        <label class="switch-label">
                                                            <input type="checkbox" name="config[{{ $row->mat_codigo }}][promediable]"
                                                                   value="1" {{ $row->promediable ? 'checked' : '' }}
                                                                   class="prom-checkbox">
                                                            <span class="prom-tag">Suma al promedio</span>
                                                        </label>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <datalist id="campos_por_curso">
                                    @foreach($camposSugeridos as $c)<option value="{{ $c }}">@endforeach
                                </datalist>

                                <div class="card-footer text-right mt-3" style="background:#f8f9fa;border-top:1px solid #dee2e6;border-radius:4px;">
                                    <button type="button" class="btn btn-outline-secondary mr-2" onclick="marcarTodosPromediables(true)">
                                        <i class="fas fa-check-double mr-1"></i>Marcar todas como promediables
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary mr-2" onclick="marcarTodosPromediables(false)">
                                        <i class="far fa-square mr-1"></i>Desmarcar todas
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save mr-1"></i>Guardar configuración del curso
                                    </button>
                                </div>
                            </form>

                            {{-- Form oculto para copiar config --}}
                            <form id="formCopiar" action="{{ route('materias.copiar-config-curso') }}" method="POST" style="display:none;">
                                @csrf
                                <input type="hidden" name="cur_codigo_destino" value="{{ $cursoSeleccionado->cur_codigo }}">
                                <input type="hidden" name="cur_codigo_origen" id="copiarOrigenHidden">
                            </form>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-hand-pointer" style="font-size:2rem;opacity:0.3;"></i>
                            <p class="mt-2">Selecciona un curso arriba para empezar a configurar.</p>
                        </div>
                    @endif
                </div>
            </div>
            {{-- ===== /TAB POR CURSO ===== --}}

            </div>{{-- /tab-content --}}
        </div>{{-- /card-body --}}
    </div>{{-- /card --}}
</div>
@endsection

@section('scripts')
<style>
.chevron-toggle { transition: transform .2s; }
[aria-expanded="true"] .chevron-toggle { transform: rotate(90deg); }

#materiasTabs .nav-link {
    color: #555;
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    margin-right: 4px;
    font-weight: 500;
    transition: background .15s, color .15s;
}
#materiasTabs .nav-link:hover {
    background: #e9ecef;
    color: #222;
}
#materiasTabs .nav-link.active {
    color: #fff;
    background: #6f42c1;
    border-color: #6f42c1;
    font-weight: 600;
}
#materiasTabs .nav-link .badge { font-size: 11px; }

/* Switch "Promedia" */
.switch-label { cursor: pointer; margin: 0; display: inline-flex; align-items: center; gap: 6px; }
.switch-label .prom-tag {
    display: inline-block; padding: 3px 10px; border-radius: 12px;
    font-size: 11px; font-weight: 600;
    background: #ecf0f1; color: #7f8c8d; border: 1px solid #bdc3c7;
    transition: all .15s;
}
.switch-label input { display: none; }
.switch-label input:checked + .prom-tag {
    background: #2ecc71; color: #fff; border-color: #27ae60;
}
.switch-label:hover .prom-tag { transform: scale(1.04); }

/* Tabla por-curso */
#tablaPorCurso .campo-input:focus { background: #fff8e1; }
#tablaPorCurso tr:hover { background: #fafafa; }
#tablaPorCurso tr.sortable-ghost { opacity: .4; background: #fff3cd; }
#tablaPorCurso tr.sortable-chosen { background: #e3f2fd; }
#tablaPorCurso .drag-handle:hover { color: #2c3e50 !important; }
#tablaPorCurso .orden-input { background: #f1f3f5; text-align: center; font-weight: 600; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    $('#asignar_materias').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione materias...',
        closeOnSelect: false
    });

    var hash = window.location.hash;
    if (hash && $('#materiasTabs a[href="'+hash+'"]').length) {
        $('#materiasTabs a[href="'+hash+'"]').tab('show');
    }
    function toggleNuevaMateria() {
        var activo = $('#materiasTabs a.active').attr('href');
        $('#btnNuevaMateria').toggle(activo === '#tabMaterias');
    }
    toggleNuevaMateria();
    $('#materiasTabs a').on('shown.bs.tab', function(e){
        history.replaceState(null, '', $(e.target).attr('href'));
        toggleNuevaMateria();
    });

    $('[data-toggle="collapse"]').on('click', function(){
        var target = $(this).attr('data-target');
        var isOpen = $(target).hasClass('show');
        $(this).attr('aria-expanded', !isOpen);
    });

    // Select2 para Por-Curso
    if ($('.select2-curso-config').length) {
        $('.select2-curso-config').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Selecciona un curso...' });
    }
    if ($('.select2-curso-copiar').length) {
        $('.select2-curso-copiar').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Curso origen...' });
    }

    // Drag-and-drop para reordenar materias del curso
    var tbody = document.getElementById('tablaPorCurso');
    if (tbody && typeof Sortable !== 'undefined') {
        Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function() { renumerarOrden(); }
        });
    }
});

function renumerarOrden() {
    var inputs = document.querySelectorAll('#tablaPorCurso .orden-input');
    inputs.forEach(function(inp, idx){ inp.value = idx + 1; });
}

function marcarTodosPromediables(estado) {
    document.querySelectorAll('.prom-checkbox').forEach(function(c){ c.checked = estado; });
}

function copiarConfigCurso() {
    var origen = document.getElementById('copiarOrigen').value;
    if (!origen) { alert('Selecciona el curso origen'); return; }
    if (!confirm('Esto sobrescribirá la configuración actual del curso destino con la del curso origen. ¿Continuar?')) return;
    document.getElementById('copiarOrigenHidden').value = origen;
    document.getElementById('formCopiar').submit();
}
</script>
@endsection
