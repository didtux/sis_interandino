@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-star mr-2"></i>Notas — Gestión {{ $gestion }}</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado && ($bloqueo['bloqueado'] ?? false))
                        {{-- Bloqueo por mora (Parámetros del colegio / Caja) --}}
                        <div class="text-center py-5">
                            <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                            <h5>Calificaciones no disponibles</h5>
                            <p class="text-muted mb-1">{{ parametro('bloqueo_mensaje') }}</p>
                            @if(!empty($bloqueo['meses']))
                                <p class="small text-muted">Meses pendientes: {{ implode(', ', array_map(fn($m) => \App\Services\MoraService::MESES[$m] ?? $m, $bloqueo['meses'])) }}</p>
                            @endif
                            <a href="{{ route('padre-portal.pagos', ['est_codigo' => $estSeleccionado->est_codigo]) }}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-money-bill-wave mr-1"></i>Ir a Pagos
                            </a>
                        </div>
                    @elseif($estSeleccionado && count($notasData) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" style="font-size:0.85rem;">
                            <thead style="background:#2c3e50;color:#fff;">
                                <tr>
                                    <th>MATERIA</th>
                                    @foreach($periodos as $p)
                                        <th class="text-center">{{ $p->periodo_nombre }}</th>
                                    @endforeach
                                    <th class="text-center" style="background:#28a745;color:#fff;">PROMEDIO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notasData as $mat)
                                    <tr>
                                        <td><strong>{{ $mat['materia'] }}</strong></td>
                                        @foreach($periodos as $p)
                                            @php $val = $mat['trimestres'][$p->periodo_numero] ?? 0; @endphp
                                            <td class="text-center {{ $val > 0 && $val < 51 ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $val > 0 ? $val : '—' }}
                                            </td>
                                        @endforeach
                                        <td class="text-center font-weight-bold" style="background:#d4edda;">
                                            {{ $mat['promedio'] > 0 ? $mat['promedio'] : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($estSeleccionado)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clipboard fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay notas aprobadas registradas aún.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
