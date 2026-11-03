@extends('layouts.app')

@section('content')
@php use App\Models\AtencionTicket; $cfg = config('atencion.areas'); @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-comments mr-2"></i>Atención a padres</h4>
            <small class="text-muted">Se actualiza cada {{ config('atencion.refresco_segundos') }} s</small>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

            <ul class="nav nav-pills mb-3 flex-wrap">
                <li class="nav-item"><a class="nav-link {{ !request('area') ? 'active' : '' }}" href="{{ route('atencion.index', ['estado' => $estado]) }}">Todas mis áreas
                    @if($pendientesPorArea->sum())<span class="badge badge-warning ml-1">{{ $pendientesPorArea->sum() }}</span>@endif</a></li>
                @foreach($areas as $k => $p)
                    <li class="nav-item"><a class="nav-link {{ request('area') === $k ? 'active' : '' }}" href="{{ route('atencion.index', ['area' => $k, 'estado' => $estado]) }}">
                        <i class="{{ $cfg[$k]['icono'] }} mr-1"></i>{{ $cfg[$k]['nombre'] }}
                        @if($pendientesPorArea[$k] ?? 0)<span class="badge badge-warning ml-1">{{ $pendientesPorArea[$k] }}</span>@endif
                    </a></li>
                @endforeach
            </ul>

            <form method="GET" class="form-inline mb-3">
                @if(request('area'))<input type="hidden" name="area" value="{{ request('area') }}">@endif
                <select name="estado" class="form-control form-control-sm mr-2 js-select2" onchange="this.form.submit()">
                    <option value="pendientes" {{ $estado === 'pendientes' ? 'selected' : '' }}>Pendientes de responder</option>
                    <option value="RESPONDIDO" {{ $estado === 'RESPONDIDO' ? 'selected' : '' }}>Respondidas</option>
                    <option value="CERRADO" {{ $estado === 'CERRADO' ? 'selected' : '' }}>Cerradas</option>
                    <option value="todas" {{ $estado === 'todas' ? 'selected' : '' }}>Todas</option>
                </select>
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control form-control-sm mr-2" placeholder="Asunto o estudiante">
                <button class="btn btn-sm btn-secondary"><i class="fas fa-search"></i></button>
            </form>

            <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>Asunto</th><th>Área</th><th>Estudiante</th><th>Padre/madre</th><th>Estado</th><th>Último mensaje</th></tr></thead>
                <tbody>
                @forelse($tickets as $t)
                    @php $nl = $noLeidos[$t->at_id] ?? 0; @endphp
                    <tr style="cursor:pointer" onclick="location.href='{{ route('atencion.show', $t->at_id) }}'" class="{{ $nl ? 'font-weight-bold' : '' }}">
                        <td>
                            <a href="{{ route('atencion.show', $t->at_id) }}">{{ $t->at_asunto }}</a>
                            @if($nl)<span class="badge badge-danger ml-1" title="Mensajes sin leer">{{ $nl }}</span>@endif
                        </td>
                        <td><i class="{{ $cfg[$t->at_area]['icono'] ?? '' }} mr-1 text-muted"></i>{{ $t->area_nombre }}</td>
                        <td>{{ $t->estudiante ? $t->estudiante->est_apellidos . ' ' . $t->estudiante->est_nombres : '—' }}
                            @if($t->estudiante && $t->estudiante->curso)<small class="text-muted d-block">{{ $t->estudiante->curso->cur_nombre }}</small>@endif</td>
                        <td>{{ $t->padre ? trim($t->padre->pfam_nombres) : '—' }}</td>
                        <td><span class="badge badge-{{ AtencionTicket::ESTADOS[$t->at_estado][1] }}">{{ AtencionTicket::ESTADOS[$t->at_estado][0] }}</span></td>
                        <td class="small text-nowrap">{{ $t->at_ultimo_mensaje->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay consultas {{ $estado === 'pendientes' ? 'pendientes' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// La bandeja se recarga sola si llegaron consultas nuevas (sin perder lo que se está escribiendo: aquí no hay formularios largos)
(function () {
    var antes = null;
    setInterval(function () {
        if (document.hidden) return;
        fetch('{{ route('atencion.contador') }}', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (!d) return; if (antes !== null && d.pendientes !== antes) location.reload(); antes = d.pendientes; })
            .catch(function () {});
    }, {{ (int) config('atencion.refresco_segundos', 30) }} * 1000);
})();
</script>
@endsection
