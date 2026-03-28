@extends('layouts.app')

@section('content')
<style>
    .tab-custom .nav-link { color: #6c757d; font-weight: 600; border: none; padding: 12px 24px; font-size: 0.9rem; }
    .tab-custom .nav-link.active { color: #667eea; border-bottom: 3px solid #667eea; background: transparent; }
    .tab-custom .nav-link:hover:not(.active) { color: #495057; background: #f8f9fa; border-radius: 6px 6px 0 0; }
    .stat-mini { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; padding: 16px; text-align: center; transition: all 0.3s; }
    .stat-mini:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .stat-mini .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 8px; }
    .stat-mini h3 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #2d3436; }
    .stat-mini p { font-size: 0.8rem; color: #6c757d; margin: 4px 0 0; text-transform: uppercase; letter-spacing: 0.5px; }
    .lista-input { width: 65px; text-align: center; font-weight: 600; }
    .materia-row { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 14px 18px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s; }
    .materia-row:hover { border-color: #667eea; box-shadow: 0 2px 8px rgba(102,126,234,0.1); }
    .materia-row .materia-info { display: flex; align-items: center; gap: 12px; }
    .materia-row .materia-icon { width: 36px; height: 36px; border-radius: 8px; background: #e3f2fd; color: #1976d2; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
    .materia-row .docente-section { display: flex; align-items: center; gap: 6px; }
    .docente-chip { display: inline-flex; align-items: center; gap: 6px; background: #e8f5e9; color: #2e7d32; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .docente-chip.empty { background: #fff3e0; color: #e65100; }
    .materia-tag { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; margin: 3px; background: #d4edda; color: #155724; }
    /* Fix select2 multi-select */
    #select-materias + .select2-container { width: 100% !important; }
    #select-materias + .select2-container .select2-selection--multiple { min-height: 44px !important; padding: 4px 8px !important; border: 1px solid #dee2e6 !important; border-radius: 8px !important; }
    #select-materias + .select2-container .select2-selection--multiple .select2-selection__rendered { display: flex !important; flex-wrap: wrap !important; gap: 4px; padding: 2px 0 !important; }
    #select-materias + .select2-container .select2-selection--multiple .select2-selection__choice { background: #667eea !important; border: none !important; color: #fff !important; border-radius: 15px !important; padding: 4px 10px !important; font-size: 0.82rem !important; margin: 0 !important; }
    #select-materias + .select2-container .select2-selection--multiple .select2-selection__choice__remove { color: #fff !important; margin-right: 5px !important; }
    #select-materias + .select2-container .select2-search--inline .select2-search__field { margin-top: 4px !important; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-school mr-2"></i>{{ $curso->cur_nombre }} <small style="opacity:0.7; font-size:0.7em;">{{ $curso->cur_codigo }}</small></h4>
                    <div>
                        <a href="{{ route('cursos.edit', $curso->cur_id) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-edit mr-1"></i>Editar Curso
                        </a>
                        <a href="{{ route('cursos.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left mr-1"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    {{-- Stats --}}
                    <div class="row mb-4">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-mini">
                                <div class="stat-icon" style="background:#e3f2fd; color:#1976d2;"><i class="fas fa-users"></i></div>
                                <h3>{{ $curso->estudiantes->count() }}</h3>
                                <p>Estudiantes</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-mini">
                                <div class="stat-icon" style="background:#fce4ec; color:#c62828;"><i class="fas fa-book"></i></div>
                                <h3>{{ $curso->cursoMaterias->count() }}</h3>
                                <p>Materias</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-mini">
                                <div class="stat-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-chalkboard-teacher"></i></div>
                                <h3>{{ $curso->cursoMateriaDocentes->count() }}</h3>
                                <p>Docentes</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-mini">
                                <div class="stat-icon" style="background:#fff3e0; color:#e65100;"><i class="fas fa-calendar-alt"></i></div>
                                <h3>{{ $gestion }}</h3>
                                <p>Gestión</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs tab-custom mb-4" id="cursoTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabLista">
                                <i class="fas fa-list-ol mr-1"></i>Lista de Curso
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabMaterias">
                                <i class="fas fa-book mr-1"></i>Materias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabDocentes">
                                <i class="fas fa-chalkboard-teacher mr-1"></i>Docentes por Materia
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- TAB: Lista de Curso --}}
                        <div class="tab-pane fade show active" id="tabLista">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-0" style="font-weight:600; color:#2d3436;">
                                        <i class="fas fa-clipboard-list text-primary mr-2"></i>Lista de Curso
                                    </h5>
                                    <small class="text-muted">Gestión {{ $gestion }} &middot; {{ $curso->estudiantes->count() }} estudiantes</small>
                                </div>
                                <form action="{{ route('cursos.auto-lista', $curso->cur_id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm" onclick="return confirm('¿Generar lista alfabética? Esto reemplazará los números actuales.')">
                                        <i class="fas fa-sort-alpha-down mr-1"></i>Auto-generar Alfabético
                                    </button>
                                </form>
                            </div>

                            <form action="{{ route('cursos.guardar-lista', $curso->cur_id) }}" method="POST">
                                @csrf
                                <div class="table-responsive-modern">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th style="width:80px">N°</th>
                                                <th>Apellidos</th>
                                                <th>Nombres</th>
                                                <th>CI</th>
                                                <th>Código</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($curso->estudiantes as $est)
                                                <tr>
                                                    <td data-label="N°">
                                                        <input type="number" name="numeros[{{ $est->est_codigo }}]"
                                                            class="form-control form-control-sm lista-input"
                                                            value="{{ $lista[$est->est_codigo] ?? '' }}"
                                                            min="1" max="99" placeholder="-">
                                                    </td>
                                                    <td data-label="Apellidos"><strong>{{ $est->est_apellidos }}</strong></td>
                                                    <td data-label="Nombres">{{ $est->est_nombres }}</td>
                                                    <td data-label="CI">{{ $est->est_ci ?? '-' }}</td>
                                                    <td data-label="Código">
                                                        <span class="modern-badge badge-primary-modern">{{ $est->est_codigo }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="empty-state" style="padding:30px;">
                                                            <i class="fas fa-users" style="font-size:2rem;"></i>
                                                            <h5>No hay estudiantes en este curso</h5>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($curso->estudiantes->count() > 0)
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary-modern">
                                            <i class="fas fa-save mr-1"></i>Guardar Lista
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>

                        {{-- TAB: Materias --}}
                        <div class="tab-pane fade" id="tabMaterias">
                            <h5 style="font-weight:600; color:#2d3436;">
                                <i class="fas fa-book text-danger mr-2"></i>Materias del Curso
                            </h5>
                            <small class="text-muted mb-3 d-block">Seleccione las materias que se imparten en este curso</small>

                            <form action="{{ route('cursos.asignar-materias', $curso->cur_id) }}" method="POST">
                                @csrf
                                <div class="row align-items-end">
                                    <div class="col-md-9 mb-3">
                                        <select name="materias[]" id="select-materias" multiple style="width:100%;">
                                            @foreach($materias as $mat)
                                                <option value="{{ $mat->mat_codigo }}"
                                                    {{ in_array($mat->mat_codigo, $materiasAsignadas) ? 'selected' : '' }}>
                                                    {{ $mat->mat_nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary-modern btn-block">
                                            <i class="fas fa-save mr-1"></i>Guardar Materias
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <hr>
                            <h6 class="mb-3">Materias actuales:</h6>
                            <div>
                                @forelse($curso->cursoMaterias as $cm)
                                    <span class="materia-tag">
                                        <i class="fas fa-book mr-1"></i>{{ $cm->materia->mat_nombre ?? $cm->mat_codigo }}
                                    </span>
                                @empty
                                    <p class="text-muted">No hay materias asignadas</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- TAB: Docentes por Materia --}}
                        <div class="tab-pane fade" id="tabDocentes">
                            <h5 style="font-weight:600; color:#2d3436;">
                                <i class="fas fa-chalkboard-teacher text-success mr-2"></i>Docentes por Materia
                            </h5>
                            <small class="text-muted mb-3 d-block">Asigne un docente a cada materia del curso</small>

                            @if($curso->cursoMaterias->count() > 0)
                                @foreach($curso->cursoMaterias as $cm)
                                    @php
                                        $docAsignado = $curso->cursoMateriaDocentes->where('mat_codigo', $cm->mat_codigo)->first();
                                    @endphp
                                    <div class="materia-row">
                                        <div class="materia-info">
                                            <div class="materia-icon"><i class="fas fa-book-open"></i></div>
                                            <div>
                                                <strong style="font-size:0.95rem;">{{ $cm->materia->mat_nombre ?? $cm->mat_codigo }}</strong>
                                                <br>
                                                @if($docAsignado && $docAsignado->docente)
                                                    <span class="docente-chip">
                                                        <i class="fas fa-user-check"></i>
                                                        {{ $docAsignado->docente->doc_nombres }} {{ $docAsignado->docente->doc_apellidos }}
                                                    </span>
                                                @else
                                                    <span class="docente-chip empty">
                                                        <i class="fas fa-user-clock"></i> Sin docente asignado
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="docente-section">
                                            <form action="{{ route('cursos.asignar-docente', $curso->cur_id) }}" method="POST" class="d-flex align-items-center" style="gap:6px;">
                                                @csrf
                                                <input type="hidden" name="mat_codigo" value="{{ $cm->mat_codigo }}">
                                                <select name="doc_codigo" class="form-control form-control-sm select-docente" required>
                                                    <option value="">Seleccionar docente...</option>
                                                    @foreach($docentes as $doc)
                                                        <option value="{{ $doc->doc_codigo }}"
                                                            {{ $docAsignado && $docAsignado->doc_codigo == $doc->doc_codigo ? 'selected' : '' }}>
                                                            {{ $doc->doc_apellidos }} {{ $doc->doc_nombres }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success" title="Asignar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            @if($docAsignado && $docAsignado->docente)
                                                <form action="{{ route('cursos.quitar-docente', [$curso->cur_id, $cm->mat_codigo]) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar docente" onclick="return confirm('¿Quitar este docente?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2 d-block"></i>
                                    <p class="text-muted">Primero asigne materias al curso en la pestaña "Materias".</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Select2 materias - inicializar al mostrar el tab para evitar problemas de ancho
    var materiasInit = false;
    function initSelectMaterias() {
        if (materiasInit) return;
        $('#select-materias').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Buscar y seleccionar materias...',
            allowClear: true,
            closeOnSelect: false
        });
        materiasInit = true;
    }

    // Select2 docentes
    $('.select-docente').each(function() {
        $(this).select2({
            theme: 'bootstrap4',
            width: '220px',
            placeholder: 'Seleccionar...',
            allowClear: true
        });
    });

    // Mantener tab activo + inicializar select2 al mostrar tab
    var hash = window.location.hash;
    if (hash) {
        $('#cursoTabs a[href="' + hash + '"]').tab('show');
    }
    $('#cursoTabs a').on('shown.bs.tab', function(e) {
        history.replaceState(null, null, e.target.hash);
        if (e.target.hash === '#tabMaterias') {
            initSelectMaterias();
        }
        if (e.target.hash === '#tabDocentes') {
            $('.select-docente').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({ theme: 'bootstrap4', width: '220px', placeholder: 'Seleccionar...', allowClear: true });
                }
            });
        }
    });

    // Si ya estamos en el tab de materias al cargar
    if (hash === '#tabMaterias') initSelectMaterias();
});
</script>
@endsection
