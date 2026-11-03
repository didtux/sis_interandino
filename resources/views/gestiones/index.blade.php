@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-calendar-alt mr-2"></i>Gestiones</h4>
            <a href="{{ route('gestiones.create') }}" class="btn btn-primary-modern">
                <i class="fas fa-plus mr-1"></i>Nueva Gestión
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}</div>
            @endif
            <p class="text-muted small">
                La gestión <strong>vigente</strong> es la que usa todo el sistema (caja, listas, notas, asistencia).
                Ya no cambia sola el 1 de enero: se cambia con el asistente de <strong>Activar</strong>, cuando el colegio lo decide.
            </p>
            <div class="table-responsive-modern">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Pre-inscripción</th>
                            <th>Reserva</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gestiones as $g)
                            <tr>
                                <td><strong>{{ $g->ges_anio }}</strong></td>
                                <td>{{ $g->ges_nombre }}</td>
                                <td>
                                    @if($g->ges_estado == 1)
                                        <span class="modern-badge badge-success-modern">VIGENTE</span>
                                    @elseif($g->ges_anio > gestion_actual())
                                        <span class="modern-badge badge-info">PRÓXIMA</span>
                                    @else
                                        <span class="modern-badge badge-secondary">ANTERIOR</span>
                                    @endif
                                </td>
                                <td>
                                    @if($g->ges_inscripcion_abierta)
                                        <span class="badge badge-warning">ABIERTA</span>
                                        @if($g->ges_preinsc_desde || $g->ges_preinsc_hasta)
                                            <small class="d-block text-muted">{{ $g->ges_preinsc_desde ? \Carbon\Carbon::parse($g->ges_preinsc_desde)->format('d/m') : '…' }} – {{ $g->ges_preinsc_hasta ? \Carbon\Carbon::parse($g->ges_preinsc_hasta)->format('d/m/Y') : '…' }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>Bs {{ number_format($g->ges_monto_reserva ?? (float) parametro('preinsc_monto_reserva', 300), 2) }}@if($g->ges_monto_reserva === null) <small class="text-muted">(defecto)</small>@endif</td>
                                <td>
                                    <div class="action-buttons">
                                        @if($g->ges_estado != 1 && $g->ges_anio > gestion_actual() && auth()->user()->tieneCapacidad('gestion.activar'))
                                            <a href="{{ route('gestiones.asistente', $g->ges_id) }}" class="btn btn-sm btn-warning" title="Paso de año">
                                                <i class="fas fa-forward mr-1"></i>Activar
                                            </a>
                                        @endif
                                        @puede('gestiones', 'editar')
                                        <a href="{{ route('gestiones.edit', $g->ges_id) }}" class="btn btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                                        @endpuede
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h5>Sin gestiones</h5></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
