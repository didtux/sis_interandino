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

            {{-- ===== TAB ASIGNAR CAMPO ===== --}}
            <div class="tab-pane fade {{ $tab === 'asociar' ? 'show active' : '' }}" id="tabAsociar" role="tabpanel">
                <div class="card-body p-0">
                    <p class="text-muted small mb-3">
                        Selecciona varias materias y asígnales un mismo <strong>Campo / Área Curricular</strong>.
                        El campo es el grupo natural: ej. <em>COMUNIDAD Y SOCIEDAD</em> reúne Ciencias Sociales, Religión, Música y Artes.
                    </p>

                    <form action="{{ route('materias.asignar-campo') }}" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Campo / Área</label>
                            <input type="text" name="mat_campo" class="form-control form-control-sm" list="campos_existentes" required
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
                        </div>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Materias a asignar</label>
                            <select name="materias[]" id="asignar_materias" class="form-control select2-multi" multiple required style="width:100%;">
                                @foreach($todasMaterias as $m)
                                    <option value="{{ $m->mat_codigo }}">
                                        {{ $m->mat_nombre }}
                                        @if($m->mat_campo) — actualmente en {{ $m->mat_campo }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Las materias seleccionadas pasarán al campo elegido. Las que ya tenían otro campo serán reasignadas.</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-save mr-1"></i>Asignar
                        </button>
                    </form>
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
</style>
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
});
</script>
@endsection
