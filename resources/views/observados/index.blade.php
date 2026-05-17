@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-user-slash mr-2 text-danger"></i>Lista de Observados — Inscripciones</h4>
            <div>
                <a href="{{ route('observados.reporte-pdf', ['gestion' => $gestion]) }}" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i>Imprimir lista
                </a>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNuevoObservado">
                    <i class="fas fa-plus mr-1"></i>Agregar estudiante
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                <i class="fas fa-shield-alt mr-1"></i>
                Los estudiantes en esta lista <strong>no podrán inscribirse</strong> en la gestión seleccionada
                hasta que la dirección los libere.
            </div>

            <form method="GET" class="row mb-3">
                <div class="col-md-3">
                    <label class="small">Gestión</label>
                    <input type="number" name="gestion" class="form-control form-control-sm" value="{{ $gestion }}">
                </div>
                <div class="col-md-3">
                    <label class="small">Estado</label>
                    <select name="estado" class="form-control form-control-sm">
                        <option value="activos"   {{ $estado=='activos'   ? 'selected':'' }}>Activos (bloqueados)</option>
                        <option value="liberados" {{ $estado=='liberados' ? 'selected':'' }}>Liberados</option>
                        <option value="todos"     {{ $estado=='todos'     ? 'selected':'' }}>Todos</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Tipo</th>
                            <th>Motivo</th>
                            <th>Registrado por</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($observados as $o)
                            <tr style="{{ $o->obs_activo ? 'background:#fff5f5;' : '' }}">
                                <td>
                                    <strong>{{ $o->estudiante->est_apellidos ?? '' }} {{ $o->estudiante->est_nombres ?? '' }}</strong>
                                    <small class="text-muted d-block">{{ $o->est_codigo }}</small>
                                </td>
                                <td>{{ optional($o->estudiante->curso)->cur_nombre ?? '-' }}</td>
                                <td><span class="badge badge-dark">{{ $o->obs_motivo_tipo }}</span></td>
                                <td>{{ $o->obs_motivo }}</td>
                                <td>
                                    {{ $o->obs_registrado_por_nombre ?: '-' }}
                                    @if(!$o->obs_activo)
                                        <small class="d-block text-muted">Liberado por: {{ $o->obs_liberado_por_nombre }}<br>{{ $o->obs_motivo_liberacion }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $o->obs_fecha_registro ? $o->obs_fecha_registro->format('d/m/Y') : '-' }}
                                    @if(!$o->obs_activo)
                                        <br><small class="text-success">Liberado: {{ optional($o->obs_fecha_liberacion)->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($o->obs_activo)
                                        <span class="badge badge-danger"><i class="fas fa-ban"></i> BLOQUEADO</span>
                                    @else
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Liberado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($o->obs_activo)
                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalLiberar{{ $o->obs_id }}">
                                            <i class="fas fa-unlock"></i> Liberar
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Modal Liberar --}}
                            @if($o->obs_activo)
                            <div class="modal fade" id="modalLiberar{{ $o->obs_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('observados.liberar', $o->obs_id) }}" method="POST">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header" style="background:#27ae60;color:#fff;">
                                                <h5>Liberar a {{ $o->estudiante->est_apellidos ?? '' }} {{ $o->estudiante->est_nombres ?? '' }}</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Motivo de liberación <span class="text-danger">*</span></label>
                                                    <textarea name="obs_motivo_liberacion" class="form-control" rows="3" required maxlength="255"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                <button class="btn btn-success">Confirmar liberación</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No hay registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $observados->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- Modal Nuevo --}}
<div class="modal fade" id="modalNuevoObservado" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('observados.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background:#c0392b;color:#fff;">
                    <h5><i class="fas fa-user-slash mr-1"></i>Agregar a Lista de Observados</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Gestión *</label>
                        <input type="number" name="obs_gestion" class="form-control" value="{{ $gestion }}" required>
                    </div>
                    <div class="form-group">
                        <label>Estudiante *</label>
                        <select name="est_codigo" class="form-control select2-obs" required style="width:100%;">
                            <option value="">— Seleccionar —</option>
                            @foreach($estudiantes as $e)
                                <option value="{{ $e->est_codigo }}">{{ $e->est_codigo }} — {{ $e->est_apellidos }} {{ $e->est_nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo de motivo *</label>
                        <select name="obs_motivo_tipo" class="form-control" required>
                            <option value="PENSIONES">Incumplimiento de pensiones</option>
                            <option value="FALTAS">Muchas faltas</option>
                            <option value="DISCIPLINARIO">Problemas disciplinarios</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Motivo / Detalle *</label>
                        <textarea name="obs_motivo" class="form-control" rows="3" required maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger">Agregar a la lista</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function(){
    $('.select2-obs').select2({ theme:'bootstrap4', width:'100%', dropdownParent: $('#modalNuevoObservado') });
});
</script>
@endsection
