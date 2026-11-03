@extends('layouts.app')

@section('content')
@php $meses = \App\Services\MoraService::MESES; @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4 class="mb-0"><i class="fas fa-user-lock mr-2"></i>Bloqueo de notas por mora — {{ $gestion }}</h4></div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <div class="alert {{ $activo ? 'alert-warning' : 'alert-secondary' }}">
                @if($activo)
                    <strong>Bloqueo ACTIVO</strong> — modo {{ $modo }}.
                    @if($modo !== 'MANUAL') Automático desde {{ $meses[$desdeMes] ?? $desdeMes }} con {{ $minimo }} o más mensualidades adeudadas. @endif
                @else
                    <strong>Bloqueo desactivado</strong>: los padres ven las notas aunque deban. Se activa en Parametrización → Parámetros del colegio.
                @endif
            </div>

            <div class="row">
                <div class="col-lg-5">
                    <h6 class="font-weight-bold">Marca manual</h6>
                    @puede('portal-bloqueos', 'crear')
                    <form method="POST" action="{{ route('bloqueos.store') }}" class="border rounded p-3 mb-3">
                        @csrf
                        <select name="est_codigo" class="form-control mb-2 select2" required>
                            <option value="">Estudiante…</option>
                            @foreach($estudiantes as $e)<option value="{{ $e->est_codigo }}">{{ $e->est_apellidos }} {{ $e->est_nombres }}</option>@endforeach
                        </select>
                        <select name="bloq_tipo" class="form-control mb-2 js-select2">
                            <option value="BLOQUEAR">Bloquear notas</option>
                            <option value="EXONERAR">Exonerar (ve las notas aunque deba)</option>
                        </select>
                        <input type="text" name="bloq_motivo" class="form-control mb-2" placeholder="Motivo" required maxlength="255">
                        <label class="small mb-0">Vigente hasta (opcional)</label>
                        <input type="date" name="vigente_hasta" class="form-control mb-2">
                        <button class="btn btn-primary btn-sm">Guardar</button>
                    </form>
                    @endpuede
                    <table class="table table-sm">
                        <thead><tr><th>Estudiante</th><th>Marca</th><th>Hasta</th><th></th></tr></thead>
                        <tbody>
                            @forelse($manuales as $m)
                                <tr>
                                    <td>{{ optional($m->estudiante)->est_apellidos }} {{ optional($m->estudiante)->est_nombres }}<div class="small text-muted">{{ $m->bloq_motivo }}</div></td>
                                    <td><span class="badge badge-{{ $m->bloq_tipo === 'BLOQUEAR' ? 'danger' : 'success' }}">{{ $m->bloq_tipo }}</span></td>
                                    <td>{{ optional($m->vigente_hasta)->format('d/m/Y') ?: '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('bloqueos.destroy', $m->bloq_id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">quitar</button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Sin marcas manuales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-7">
                    <h6 class="font-weight-bold">Alcanzados por el bloqueo automático ({{ $alcanzados->count() }})</h6>
                    <p class="small text-muted">Estudiantes con {{ $minimo }} o más mensualidades pendientes a hoy (misma cuenta que el reporte de mora). Si el bloqueo está activo y no tienen exoneración, sus padres no ven las notas.</p>
                    <div style="max-height:520px;overflow:auto;">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Estudiante</th><th>Curso</th><th class="text-center">Meses</th><th class="text-right">Deuda</th></tr></thead>
                            <tbody>
                                @foreach($alcanzados as $e)
                                    <tr><td>{{ $e->est_apellidos }} {{ $e->est_nombres }}</td><td>{{ optional($e->curso)->cur_nombre }}</td>
                                        <td class="text-center">{{ count($e->mora_meses_pendientes) }}</td><td class="text-right">{{ number_format($e->mora_deuda, 2) }}</td></tr>
                                @endforeach
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
<script>$(function () { $('.select2').select2({ theme: 'bootstrap4', width: '100%' }); });</script>
@endsection
