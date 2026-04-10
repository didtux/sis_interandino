@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-1"><i class="fas fa-calendar-check mr-2"></i>{{ $actividad->act_nombre }}</h4>
                            <small class="text-muted">{{ $actividad->act_descripcion }}</small><br>
                            <span class="modern-badge badge-primary-modern">{{ $actividad->act_fecha->format('d/m/Y') }}</span>
                            <span class="badge badge-{{ $actividad->act_estado ? 'success' : 'secondary' }}">{{ $actividad->act_estado ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('actividades-asistencia.reporte-pdf', [$actividad->act_id, 'tab' => request('tab', 'registros'), 'cur_codigo' => request('cur_codigo'), 'actcat_id' => request('actcat_id'), 'buscar_est' => request('buscar_est'), 'cur_codigo_faltas' => request('cur_codigo_faltas')]) }}" class="btn btn-danger btn-sm" target="_blank"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                            <a href="{{ route('actividades-asistencia.edit', $actividad->act_id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
                            <a href="{{ route('actividades-asistencia.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif

            {{-- Categorías --}}
            <div class="card modern-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0"><i class="fas fa-tags mr-2"></i>Categorías</h5>
                    <button class="btn btn-primary-modern btn-sm" data-toggle="modal" data-target="#modalCategoria"><i class="fas fa-plus mr-1"></i>Nueva Categoría</button>
                </div>
                <div class="card-body py-2">
                    @forelse($actividad->categoriasActivas as $cat)
                        <div class="d-inline-flex align-items-center mr-3 mb-2" style="background:#f8f9fa;padding:6px 12px;border-radius:8px;">
                            <span class="font-weight-bold mr-2">{{ $cat->actcat_nombre }}</span>
                            <span class="badge badge-info mr-2">{{ $cat->registros->count() }} reg.</span>
                            <a href="{{ route('actividades-asistencia.registrar', $cat->actcat_id) }}" class="btn btn-sm btn-success mr-1" title="Registrar"><i class="fas fa-qrcode"></i></a>
                            <form action="{{ route('actividades-asistencia.destroy-categoria', $cat->actcat_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar categoría y sus registros?')"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No hay categorías. Cree una para comenzar a registrar asistencia.</p>
                    @endforelse
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link {{ $tab == 'registros' ? 'active' : '' }}" href="{{ route('actividades-asistencia.show', [$actividad->act_id, 'tab' => 'registros']) }}"><i class="fas fa-list mr-1"></i>Registros <span class="badge badge-success">{{ $registros->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link {{ $tab == 'faltas' ? 'active' : '' }}" href="{{ route('actividades-asistencia.show', [$actividad->act_id, 'tab' => 'faltas']) }}"><i class="fas fa-user-times mr-1"></i>Faltas <span class="badge badge-danger">{{ $faltas->count() }}</span></a></li>
            </ul>

            <div class="card modern-card" style="border-top-left-radius:0;border-top-right-radius:0;">
                <div class="card-body">
                    @if($tab == 'registros')
                        {{-- Filtros registros --}}
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="tab" value="registros">
                            <div class="row">
                                <div class="col-md-3"><input type="text" name="buscar_est" class="form-control form-control-sm" placeholder="Buscar estudiante..." value="{{ request('buscar_est') }}"></div>
                                <div class="col-md-3">
                                    <select name="cur_codigo" class="form-control form-control-sm">
                                        <option value="">Todos los cursos</option>
                                        @foreach($cursos as $c)<option value="{{ $c->cur_codigo }}" {{ request('cur_codigo') == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="actcat_id" class="form-control form-control-sm">
                                        <option value="">Todas las categorías</option>
                                        @foreach($actividad->categoriasActivas as $c)<option value="{{ $c->actcat_id }}" {{ request('actcat_id') == $c->actcat_id ? 'selected' : '' }}>{{ $c->actcat_nombre }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex" style="gap:4px;"><button class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button><a href="{{ route('actividades-asistencia.show', [$actividad->act_id, 'tab' => 'registros']) }}" class="btn btn-secondary btn-sm btn-block mt-0"><i class="fas fa-times"></i></a></div>
                            </div>
                        </form>
                        <div class="table-responsive-modern">
                            <table class="modern-table" style="font-size:0.85rem;">
                                <thead><tr><th>N°</th><th>Estudiante</th><th>Curso</th><th>Categoría</th><th>Hora</th><th>Obs.</th><th>Acc.</th></tr></thead>
                                <tbody>
                                    @forelse($registros as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong>{{ $r->estudiante->est_apellidos ?? '' }} {{ $r->estudiante->est_nombres ?? '' }}</strong></td>
                                        <td><span class="modern-badge badge-primary-modern" style="font-size:10px;">{{ $r->estudiante->curso->cur_nombre ?? 'N/A' }}</span></td>
                                        <td><span class="badge badge-info">{{ $r->categoria->actcat_nombre ?? '' }}</span></td>
                                        <td>{{ $r->actreg_hora }}</td>
                                        <td>
                                            <span class="obs-text-{{ $r->actreg_id }}">{{ $r->actreg_observacion }}</span>
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 ml-1" onclick="editarObs({{ $r->actreg_id }}, '{{ addslashes($r->actreg_observacion) }}')" title="Editar obs."><i class="fas fa-pen" style="font-size:9px;"></i></button>
                                        </td>
                                        <td>
                                            <form action="{{ route('actividades-asistencia.eliminar-registro', $r->actreg_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">No hay registros</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- Filtros faltas --}}
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="tab" value="faltas">
                            <div class="row">
                                <div class="col-md-6">
                                    <select name="cur_codigo_faltas" class="form-control form-control-sm">
                                        <option value="">Todos los cursos</option>
                                        @foreach($cursos as $c)<option value="{{ $c->cur_codigo }}" {{ request('cur_codigo_faltas') == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex" style="gap:4px;"><button class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button><a href="{{ route('actividades-asistencia.show', [$actividad->act_id, 'tab' => 'faltas']) }}" class="btn btn-secondary btn-sm btn-block mt-0"><i class="fas fa-times"></i></a></div>
                            </div>
                        </form>
                        <div class="table-responsive-modern">
                            <table class="modern-table" style="font-size:0.85rem;">
                                <thead><tr><th>N°</th><th>Estudiante</th><th>Curso</th></tr></thead>
                                <tbody>
                                    @forelse($faltas as $i => $est)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong>{{ $est->est_apellidos }} {{ $est->est_nombres }}</strong></td>
                                        <td><span class="modern-badge badge-primary-modern" style="font-size:10px;">{{ $est->curso->cur_nombre ?? 'N/A' }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Todos los estudiantes tienen registro</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal nueva categoría --}}
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header" style="background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;">
            <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Nueva Categoría</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <form action="{{ route('actividades-asistencia.store-categoria', $actividad->act_id) }}" method="POST">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Nombre <span class="text-danger">*</span></label><input type="text" name="actcat_nombre" class="form-control" required placeholder="Ej: Turno Mañana, Grupo A..."></div>
                <div class="form-group"><label>Descripción</label><textarea name="actcat_descripcion" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button></div>
        </form>
    </div></div>
</div>
@endsection

@section('scripts')
<script>
function editarObs(id, obsActual) {
    var nuevo = prompt('Observación:', obsActual || '');
    if (nuevo === null) return;
    $.ajax({
        url: '{{ url("actividades-asistencia/registro") }}/' + id + '/observacion',
        method: 'PUT',
        data: { _token: '{{ csrf_token() }}', observacion: nuevo },
        success: function(data) {
            if (data.success) {
                $('.obs-text-' + id).text(nuevo);
            }
        }
    });
}
</script>
@endsection
