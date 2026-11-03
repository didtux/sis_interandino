@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-bus-alt mr-2"></i>Choferes y buses</h4>
            <div>
                @puede('choferes', 'exportar')
                <a href="{{ route('choferes.flota-pdf') }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                @endpuede
                <a href="{{ route('choferes.index') }}" class="btn btn-outline-primary"><i class="fas fa-id-card mr-1"></i>Choferes</a>
                <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-primary"><i class="fas fa-car mr-1"></i>Vehículos</a>
                <a href="{{ route('asignaciones-transporte.index') }}" class="btn btn-outline-primary"><i class="fas fa-tasks mr-1"></i>Asignaciones</a>
            </div>
        </div>
        <div class="card-body">
            @php
                // Usuario del chofer: rol_usuarios.us_entidad_id = chof_codigo. Se cuentan choferes, no asignaciones.
                $sinUsuario = $asignaciones->pluck('chofer')->filter()->unique('chof_codigo')
                    ->filter(fn($c) => !isset($conUsuario[$c->chof_codigo]))->count();
                $repetidos = $asignaciones->groupBy('veh_codigo')->filter(fn($g) => $g->count() > 1);
            @endphp
            @if($repetidos->isNotEmpty())
                <div class="alert alert-light border py-2 small">
                    <i class="fas fa-info-circle mr-1"></i>Buses con más de una asignación activa:
                    @foreach($repetidos as $g) <strong>{{ optional($g->first()->vehiculo)->veh_numero_bus }}</strong> ({{ $g->count() }})@if(!$loop->last),@endif @endforeach
                    — revise en <a href="{{ route('asignaciones-transporte.index') }}">Asignaciones</a> si alguna quedó duplicada.
                </div>
            @endif
            @if($sinUsuario)
                <div class="alert alert-warning py-2">
                    <i class="fas fa-user-lock mr-1"></i>{{ $sinUsuario }} chofer{{ $sinUsuario > 1 ? 'es' : '' }} con bus asignado no tiene{{ $sinUsuario > 1 ? 'n' : '' }} usuario:
                    no puede{{ $sinUsuario > 1 ? 'n' : '' }} registrar la asistencia con el QR. Créelo desde <a href="{{ route('choferes.index') }}">Choferes</a> (botón morado).
                </div>
            @endif
            <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr><th>Bus</th><th>Placa</th><th>Vehículo</th><th class="text-center">Capac.</th><th>Chofer</th><th>CI</th><th>Licencia</th><th>Teléfono</th><th>Ruta</th><th class="text-center">Estud.</th><th>Usuario</th></tr>
                </thead>
                <tbody>
                @forelse($asignaciones as $a)
                    @php $v = $a->vehiculo; $c = $a->chofer; $n = $estudiantesPorRuta[$a->ruta_codigo] ?? 0; @endphp
                    <tr>
                        <td><strong>{{ optional($v)->veh_numero_bus ?: '—' }}</strong></td>
                        <td>{{ optional($v)->veh_placa }}</td>
                        <td class="small">{{ trim(optional($v)->veh_marca . ' ' . optional($v)->veh_modelo) }} {{ optional($v)->veh_color ? '· ' . $v->veh_color : '' }}</td>
                        <td class="text-center {{ $v && $v->veh_capacidad && $n > $v->veh_capacidad ? 'text-danger font-weight-bold' : '' }}">{{ optional($v)->veh_capacidad }}</td>
                        <td>{{ $c ? $c->chof_apellidos . ' ' . $c->chof_nombres : '—' }}</td>
                        <td>{{ optional($c)->chof_ci }}</td>
                        <td>{{ optional($c)->chof_licencia }}</td>
                        <td>{{ optional($c)->chof_telefono }}</td>
                        <td>{{ optional($a->ruta)->ruta_nombre }}</td>
                        <td class="text-center">
                            <a href="{{ route('estudiantes-rutas.index', ['ruta_codigo' => $a->ruta_codigo]) }}" title="Ver estudiantes de la ruta">{{ $n }}</a>
                        </td>
                        <td>
                            @if($c && isset($conUsuario[$c->chof_codigo]))<span class="badge badge-success">{{ $conUsuario[$c->chof_codigo] }}</span>
                            @elseif($c)<span class="badge badge-warning">sin usuario</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No hay asignaciones activas.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-muted"><i class="fas fa-user mr-1"></i>Choferes sin bus asignado ({{ $choferesSinBus->count() }})</h6>
                    <ul class="small mb-0">
                        @forelse($choferesSinBus as $c)<li>{{ $c->chof_apellidos }} {{ $c->chof_nombres }} · CI {{ $c->chof_ci }} · {{ $c->chof_telefono }}</li>
                        @empty<li class="text-muted">Ninguno</li>@endforelse
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted"><i class="fas fa-bus mr-1"></i>Buses sin chofer asignado ({{ $busesSinChofer->count() }})</h6>
                    <ul class="small mb-0">
                        @forelse($busesSinChofer as $v)<li>{{ $v->veh_numero_bus ?: "—" }} · {{ $v->veh_placa }} · {{ trim($v->veh_marca . ' ' . $v->veh_modelo) }}</li>
                        @empty<li class="text-muted">Ninguno</li>@endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
