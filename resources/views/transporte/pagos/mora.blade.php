@extends('layouts.app')

@section('content')
@php use App\Services\TransporteMoraService as TM; @endphp
<div class="section-body">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Mora de transporte</h4>
            @puede('pagos-transporte', 'exportar')
            <a href="{{ route('pagos-transporte.mora-pdf', request()->query()) }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
            @endpuede
        </div>
        <div class="card-body">
            <form method="GET" class="form-row align-items-end mb-3">
                <div class="col-md-3 mb-2">
                    <label class="small mb-1">Curso</label>
                    <select name="cur_codigo" class="form-control form-control-sm js-select2">
                        <option value="">Todos los cursos</option>
                        @foreach($cursos as $c)<option value="{{ $c->cur_codigo }}" {{ $filtros['cur_codigo'] == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small mb-1">Ruta</label>
                    <select name="ruta_codigo" class="form-control form-control-sm js-select2">
                        <option value="">Todas</option>
                        @foreach($rutas as $r)<option value="{{ $r->ruta_codigo }}" {{ $filtros['ruta_codigo'] == $r->ruta_codigo ? 'selected' : '' }}>{{ $r->ruta_nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Bus</label>
                    <select name="veh_codigo" class="form-control form-control-sm js-select2">
                        <option value="">Todos</option>
                        @foreach($buses as $b)<option value="{{ $b->veh_codigo }}" {{ $filtros['veh_codigo'] == $b->veh_codigo ? 'selected' : '' }}>{{ $b->veh_numero_bus ?: $b->veh_placa }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Vencido hasta</label>
                    <select name="mes" class="form-control form-control-sm js-select2">
                        @for($m = TM::PRIMER_MES + 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $mesCorte == $m ? 'selected' : '' }}>{{ TM::MESES[$m - 1] }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                    <a href="{{ route('pagos-transporte.mora') }}" class="btn btn-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            <div class="alert alert-light small py-2">
                Meses de {{ TM::MESES[TM::PRIMER_MES] }} a {{ TM::MESES[$mesCorte - 1] }} sin pagar, de estudiantes con ruta activa (los meses desde una suspensión no cuentan).
                La deuda se estima con la última cuota cobrada a cada estudiante.
            </div>

            <div class="row mb-3">
                <div class="col-md-4"><div class="border rounded p-2 text-center"><div class="h4 mb-0">{{ $filas->count() }}</div><small class="text-muted">estudiantes en mora</small></div></div>
                <div class="col-md-4"><div class="border rounded p-2 text-center"><div class="h4 mb-0">{{ $filas->sum(fn($f) => count($f->meses)) }}</div><small class="text-muted">cuotas vencidas</small></div></div>
                <div class="col-md-4"><div class="border rounded p-2 text-center"><div class="h4 mb-0">Bs {{ number_format($filas->sum('deuda_estimada'), 2) }}</div><small class="text-muted">deuda estimada</small></div></div>
            </div>

            <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>N°</th><th>Estudiante</th><th>Curso</th><th>Ruta</th><th>Bus</th><th>Meses en mora</th><th class="text-center">Cant.</th><th class="text-right">Deuda est.</th><th></th></tr></thead>
                <tbody>
                @forelse($filas as $i => $f)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $f->estudiante }}</td>
                        <td>{{ $f->curso }}</td>
                        <td class="small">{{ $f->ruta }}</td>
                        <td>{{ $f->bus }}</td>
                        <td class="small">{{ $f->meses_texto }}</td>
                        <td class="text-center"><span class="badge badge-{{ count($f->meses) >= 2 ? 'danger' : 'warning' }}">{{ count($f->meses) }}</span></td>
                        <td class="text-right">{{ $f->deuda_estimada !== null ? number_format($f->deuda_estimada, 2) : 'sin pagos' }}</td>
                        <td>
                            @puede('pagos-transporte', 'crear')
                            <a href="{{ route('pagos-transporte.create') }}" class="btn btn-sm btn-outline-success" title="Cobrar"><i class="fas fa-cash-register"></i></a>
                            @endpuede
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay estudiantes en mora con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
