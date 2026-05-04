@extends('layouts.app')

@section('content')
@php
    $esDocenteVinculado = auth()->user()->us_entidad_tipo === 'docente' && auth()->user()->us_entidad_id;
    $esAdmin = auth()->user()->rol_id == 1;
@endphp

{{-- Fix Select2 overflow --}}
<style>
    .select2-container { z-index: 1050; }
    .select2-container .select2-selection--multiple {
        min-height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: .25rem !important;
        padding: 2px 6px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #3498db !important;
        border: none !important;
        color: #fff !important;
        border-radius: 3px !important;
        padding: 2px 8px !important;
        margin: 2px 4px 2px 0 !important;
        font-size: 0.82rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff !important;
        margin-right: 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffd !important;
    }
    .select2-dropdown {
        border: 1px solid #ced4da !important;
        border-radius: 0 0 .25rem .25rem !important;
        box-shadow: 0 4px 12px rgba(0,0,0,.15) !important;
        z-index: 1060 !important;
    }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #3498db !important;
    }
    .select2-container--default .select2-search--inline .select2-search__field {
        margin-top: 4px;
    }
    .select2-container .select2-search--inline .select2-search__field {
        font-size: 0.85rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
    }
</style>
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-clipboard-check mr-2"></i>Asistencia de Clases</h4>
                    <span class="badge badge-secondary p-2">{{ $asignaciones->count() }} asignación(es)</span>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif

                    @if($periodos->isEmpty())
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No hay periodos configurados para {{ date('Y') }}.</div>
                    @endif

                    {{-- Filtros --}}
                    <form method="GET" class="mb-3">
                        <div class="row align-items-end">
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
                            <div class="{{ $esDocenteVinculado ? 'col-md-4' : 'col-md-3' }} mb-2">
                                <div class="d-flex" style="gap:6px;">
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Filtrar</button>
                                    <a href="{{ route('asistencia-clases.index') }}" class="btn btn-secondary btn-block mt-0"><i class="fas fa-times"></i></a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Tabla --}}
                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    @if(!$esDocenteVinculado)
                                        <th>Docente</th>
                                    @endif
                                    <th>Curso</th>
                                    <th>Materia</th>
                                    @foreach($periodos as $p)
                                        @php
                                            $hoy = now()->toDateString();
                                            $activo = $hoy >= $p->periodo_fecha_inicio->toDateString() && $hoy <= $p->periodo_fecha_fin->toDateString();
                                        @endphp
                                        <th class="text-center">
                                            {{ $p->periodo_nombre }}
                                            @if($activo)
                                                <br><span class="badge badge-success" style="font-size:9px;">Activo</span>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $a)
                                    <tr>
                                        @if(!$esDocenteVinculado)
                                            <td><strong>{{ $a->docente->doc_apellidos ?? '' }}</strong> {{ $a->docente->doc_nombres ?? '' }}</td>
                                        @endif
                                        <td><span class="modern-badge badge-primary-modern">{{ $a->curso->cur_nombre ?? $a->cur_codigo }}</span></td>
                                        <td><span class="modern-badge badge-warning-modern">{{ $a->materia->mat_nombre ?? $a->mat_codigo }}</span></td>
                                        @foreach($periodos as $p)
                                            @php
                                                $hoy = now()->toDateString();
                                                $activo = $hoy >= $p->periodo_fecha_inicio->toDateString() && $hoy <= $p->periodo_fecha_fin->toDateString();
                                                $stats = \App\Models\AsistenciaClase::where('curmatdoc_id', $a->curmatdoc_id)
                                                    ->where('periodo_id', $p->periodo_id)
                                                    ->selectRaw("asiscl_estado, COUNT(*) as total")
                                                    ->groupBy('asiscl_estado')->pluck('total', 'asiscl_estado');
                                                $totalReg = $stats->sum();
                                                $diasReg = \App\Models\AsistenciaClase::where('curmatdoc_id', $a->curmatdoc_id)
                                                    ->where('periodo_id', $p->periodo_id)
                                                    ->distinct('asiscl_fecha')->count('asiscl_fecha');
                                            @endphp
                                            <td class="text-center">
                                                <a href="{{ route('asistencia-clases.vista-general', [$a->curmatdoc_id, $p->periodo_id]) }}"
                                                   class="btn btn-sm {{ $activo ? ($totalReg > 0 ? 'btn-success' : 'btn-primary') : 'btn-outline-secondary' }}" style="min-width:110px;">
                                                    <i class="fas {{ $activo ? ($totalReg > 0 ? 'fa-chart-bar' : 'fa-plus-circle') : 'fa-eye' }} mr-1"></i>
                                                    @if($totalReg > 0)
                                                        {{ $diasReg }} día(s)
                                                    @else
                                                        {{ $activo ? 'Registrar' : 'Ver' }}
                                                    @endif
                                                </a>
                                                @if($totalReg > 0)
                                                    <div style="font-size:10px;margin-top:3px;">
                                                        <span class="text-success" title="Presentes">P:{{ $stats['P'] ?? 0 }}</span>
                                                        <span class="text-warning" title="Atrasos">A:{{ $stats['A'] ?? 0 }}</span>
                                                        <span class="text-danger" title="Faltas">F:{{ $stats['F'] ?? 0 }}</span>
                                                        <span class="text-info" title="Licencias">L:{{ $stats['L'] ?? 0 }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $periodos->count() + (!$esDocenteVinculado ? 3 : 2) }}">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-check"></i>
                                                <h5>No hay resultados</h5>
                                                <p>Ajuste los filtros o verifique las asignaciones</p>
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
        width: '100%',
        allowClear: true,
        placeholder: 'Seleccione...',
        closeOnSelect: false,
        language: {
            noResults: function() { return 'Sin resultados'; },
            searching: function() { return 'Buscando...'; }
        }
    });
});
</script>
@endsection
