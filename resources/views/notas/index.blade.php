@extends('layouts.app')

@section('content')
@php
    $esDocenteVinculado = auth()->user()->us_entidad_tipo === 'docente' && auth()->user()->us_entidad_id;
    $esAdmin = auth()->user()->rol_id == 1;
@endphp
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-clipboard-list mr-2"></i>Registro de Notas</h4>
                    <div>
                        @if($esAdmin)
                            <a href="{{ route('notas.configuracion') }}" class="btn btn-primary-modern btn-sm">
                                <i class="fas fa-cog mr-1"></i>Configuración
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
                    @endif

                    @if($periodos->isEmpty())
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No hay periodos configurados para {{ date('Y') }}.</div>
                    @endif

                    {{-- Filtros --}}
                    <form method="GET" class="mb-3">
                        <div class="row">
                            @if(!$esDocenteVinculado)
                                <div class="col-md-3 mb-2">
                                    <label class="small text-muted mb-1">Docente</label>
                                    <input type="text" name="buscar" class="form-control" placeholder="Nombre o apellido..." value="{{ request('buscar') }}">
                                </div>
                            @endif
                            <div class="{{ $esDocenteVinculado ? 'col-md-4' : 'col-md-3' }} mb-2">
                                <label class="small text-muted mb-1">Curso</label>
                                <select name="cur_codigo[]" class="form-control select2-multi" multiple style="width:100%">
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}" {{ in_array($c->cur_codigo, (array)request('cur_codigo', [])) ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="{{ $esDocenteVinculado ? 'col-md-4' : 'col-md-3' }} mb-2">
                                <label class="small text-muted mb-1">Materia</label>
                                <select name="mat_codigo[]" class="form-control select2-multi" multiple style="width:100%">
                                    @foreach($materias as $m)
                                        <option value="{{ $m->mat_codigo }}" {{ in_array($m->mat_codigo, (array)request('mat_codigo', [])) ? 'selected' : '' }}>{{ $m->mat_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="{{ $esDocenteVinculado ? 'col-md-2' : 'col-md-2' }} mb-2">
                                <label class="small text-muted mb-1">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Borrador</option>
                                    <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Enviado</option>
                                    <option value="2" {{ request('estado') === '2' ? 'selected' : '' }}>Aprobado</option>
                                    <option value="3" {{ request('estado') === '3' ? 'selected' : '' }}>Rechazado</option>
                                </select>
                            </div>
                            <div class="{{ $esDocenteVinculado ? 'col-md-2' : 'col-md-1' }} mb-2 d-flex align-items-end" style="gap:4px;">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
                                <a href="{{ route('notas.index') }}" class="btn btn-secondary btn-block mt-0"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </form>

                    {{-- Dimensiones y contadores --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                        <div>
                            @if($dimensiones->isNotEmpty())
                                <small class="text-muted">Dimensiones:</small>
                                @foreach($dimensiones as $dim)
                                    <span class="modern-badge badge-primary-modern" style="font-size:11px;">{{ $dim->dimension_nombre }}/{{ $dim->dimension_valor_max }} ({{ $dim->dimension_columnas }}col)</span>
                                @endforeach
                            @endif
                        </div>
                        <div>
                            <span class="badge badge-secondary p-1">{{ $asignaciones->count() }} asignación(es)</span>
                        </div>
                    </div>

                    {{-- Tabla --}}
                    <div class="table-responsive-modern">
                        <table class="modern-table" id="tablaNotas">
                            <thead>
                                <tr>
                                    @if(!$esDocenteVinculado)
                                        <th>Docente</th>
                                    @endif
                                    <th>Curso</th>
                                    <th>Materia</th>
                                    @foreach($periodos as $periodo)
                                        @php
                                            $hoy = now()->toDateString();
                                            $activo = $hoy >= $periodo->periodo_fecha_inicio->toDateString() && $hoy <= $periodo->periodo_fecha_fin->toDateString();
                                        @endphp
                                        <th class="text-center">
                                            {{ $periodo->periodo_nombre }}
                                            @if($activo)
                                                <br><span class="badge badge-success" style="font-size:9px;">Activo</span>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $asig)
                                    <tr>
                                        @if(!$esDocenteVinculado)
                                            <td data-label="Docente">
                                                <strong>{{ $asig->docente->doc_apellidos ?? '' }}</strong> {{ $asig->docente->doc_nombres ?? '' }}
                                            </td>
                                        @endif
                                        <td data-label="Curso">
                                            <span class="modern-badge badge-primary-modern">{{ $asig->curso->cur_nombre ?? $asig->cur_codigo }}</span>
                                        </td>
                                        <td data-label="Materia">
                                            <span class="modern-badge badge-warning-modern">{{ $asig->materia->mat_nombre ?? $asig->mat_codigo }}</span>
                                        </td>
                                        @foreach($periodos as $periodo)
                                            @php
                                                $nota = \App\Models\Nota::where('curmatdoc_id', $asig->curmatdoc_id)
                                                    ->where('periodo_id', $periodo->periodo_id)->first();
                                                $estado = $nota->nota_estado ?? -1;
                                                $btnClass = match($estado) {
                                                    2 => 'btn-success',
                                                    1 => 'btn-warning',
                                                    3 => 'btn-danger',
                                                    0 => 'btn-secondary',
                                                    default => 'btn-outline-primary'
                                                };
                                                $iconClass = match($estado) {
                                                    2 => 'fa-check-circle',
                                                    1 => 'fa-clock',
                                                    3 => 'fa-times-circle',
                                                    0 => 'fa-save',
                                                    default => 'fa-edit'
                                                };
                                                $label = match($estado) {
                                                    2 => 'Aprobado',
                                                    1 => 'Enviado',
                                                    3 => 'Rechazado',
                                                    0 => 'Borrador',
                                                    default => 'Calificar'
                                                };
                                            @endphp
                                            <td class="text-center" data-label="{{ $periodo->periodo_nombre }}">
                                                <a href="{{ route('notas.calificar', [$asig->curmatdoc_id, $periodo->periodo_id]) }}"
                                                   class="btn btn-sm {{ $btnClass }}" style="min-width:105px;">
                                                    <i class="fas {{ $iconClass }} mr-1"></i>{{ $label }}
                                                </a>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $periodos->count() + (!$esDocenteVinculado ? 3 : 2) }}">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list"></i>
                                                <h5>No hay resultados</h5>
                                                <p>Ajuste los filtros o verifique las asignaciones en el módulo de Cursos</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
    $('.select2-multi').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: 'Seleccione...',
        closeOnSelect: false
    });
});
</script>
@endsection
