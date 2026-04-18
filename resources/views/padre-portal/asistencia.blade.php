@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-clipboard-check mr-2"></i>Asistencia — Gestión {{ $gestion }}</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado && count($asistData) > 0)
                    <div class="row">
                        @foreach($periodos as $p)
                            @php $d = $asistData[$p->periodo_numero] ?? null; @endphp
                            @if($d)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100" style="border-top:3px solid #667eea;">
                                    <div class="card-body text-center">
                                        <h6 class="font-weight-bold mb-3" style="color:#667eea;">{{ $p->periodo_nombre }}</h6>
                                        <div class="row text-center" style="font-size:0.8rem;">
                                            <div class="col-6 mb-2">
                                                <div style="font-size:1.5rem;font-weight:700;color:#28a745;">{{ $d['presencias'] }}</div>
                                                <small class="text-muted">Asistencias</small>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div style="font-size:1.5rem;font-weight:700;color:#dc3545;">{{ $d['faltas'] }}</div>
                                                <small class="text-muted">Faltas</small>
                                            </div>
                                            <div class="col-6">
                                                <div style="font-size:1.5rem;font-weight:700;color:#ffc107;">{{ $d['atrasos'] }}</div>
                                                <small class="text-muted">Atrasos</small>
                                            </div>
                                            <div class="col-6">
                                                <div style="font-size:1.5rem;font-weight:700;color:#17a2b8;">{{ $d['licencias'] }}</div>
                                                <small class="text-muted">Licencias</small>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <small class="text-muted">Días trabajados: <strong>{{ $d['diasTrab'] }}</strong></small>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                    @elseif($estSeleccionado)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay datos de asistencia registrados.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
