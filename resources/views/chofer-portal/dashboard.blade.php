@extends('layouts.app')

@section('content')
<style>
    .portal-welcome { background: linear-gradient(135deg, #2d3436 0%, #636e72 100%); border-radius: 12px; color: #fff; padding: 24px 30px; margin-bottom: 20px; }
    .portal-welcome h2 { font-size: 1.5rem; font-weight: 700; margin: 0; }
    .portal-welcome p { opacity: 0.85; margin: 4px 0 0; font-size: 0.9rem; }
    .stat-card { border-radius: 12px; border: none; overflow: hidden; transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .stat-card .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; }
    .stat-card .stat-num { font-size: 1.8rem; font-weight: 700; }
    .stat-card .stat-label { font-size: 0.8rem; color: #6c757d; }
    .asig-card { border-radius: 10px; border: 1px solid #e9ecef; padding: 16px; margin-bottom: 12px; }
    .quick-action { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-radius: 10px; background: #f8f9fa; border: 1px solid #e9ecef; text-decoration: none; color: #333; transition: all 0.2s; margin-bottom: 10px; }
    .quick-action:hover { background: #667eea; color: #fff; text-decoration: none; border-color: #667eea; }
    .quick-action i { font-size: 1.4rem; width: 40px; text-align: center; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="portal-welcome">
                <h2><i class="fas fa-bus mr-2"></i>Bienvenido, {{ $chofer->chof_nombres }}</h2>
                <p>Panel de Conductor — {{ date('d/m/Y') }}</p>
            </div>

            {{-- Estadísticas del día --}}
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2);"><i class="fas fa-users"></i></div>
                            <div>
                                <div class="stat-num">{{ $totalEstudiantes }}</div>
                                <div class="stat-label">Estudiantes en mis rutas</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#28a745,#20c997);"><i class="fas fa-arrow-right"></i></div>
                            <div>
                                <div class="stat-num">{{ $idaHoy }}</div>
                                <div class="stat-label">Asistencia IDA hoy</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#fd7e14,#e74c3c);"><i class="fas fa-arrow-left"></i></div>
                            <div>
                                <div class="stat-num">{{ $vueltaHoy }}</div>
                                <div class="stat-label">Asistencia VUELTA hoy</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Mis asignaciones --}}
                <div class="col-md-6 mb-3">
                    <div class="card modern-card">
                        <div class="card-header"><h4><i class="fas fa-route mr-2"></i>Mis Rutas Asignadas</h4></div>
                        <div class="card-body">
                            @forelse($asignaciones as $asig)
                            <div class="asig-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong style="font-size:1.05rem;">{{ $asig->ruta->ruta_nombre ?? '-' }}</strong>
                                        <br><small class="text-muted">{{ $asig->ruta->ruta_descripcion ?? '' }}</small>
                                    </div>
                                    <span class="badge badge-success">Activa</span>
                                </div>
                                <hr class="my-2">
                                <small class="text-muted">
                                    <i class="fas fa-car mr-1"></i>{{ $asig->vehiculo->veh_marca ?? '' }} {{ $asig->vehiculo->veh_placa ?? '' }}
                                    @if($asig->vehiculo && $asig->vehiculo->veh_numero_bus)
                                        — Bus #{{ $asig->vehiculo->veh_numero_bus }}
                                    @endif
                                </small>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-route fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                                <p>No tienes rutas asignadas.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Acciones rápidas --}}
                <div class="col-md-6 mb-3">
                    <div class="card modern-card">
                        <div class="card-header"><h4><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h4></div>
                        <div class="card-body">
                            <a href="{{ route('chofer-portal.asistencia', ['tipo' => 'IDA']) }}" class="quick-action">
                                <i class="fas fa-qrcode text-success"></i>
                                <div>
                                    <strong>Registrar Asistencia IDA</strong>
                                    <br><small class="text-muted">Escanear QR al recoger estudiantes</small>
                                </div>
                            </a>
                            <a href="{{ route('chofer-portal.asistencia', ['tipo' => 'VUELTA']) }}" class="quick-action">
                                <i class="fas fa-qrcode text-warning"></i>
                                <div>
                                    <strong>Registrar Asistencia VUELTA</strong>
                                    <br><small class="text-muted">Escanear QR al dejar estudiantes</small>
                                </div>
                            </a>
                            <a href="{{ route('chofer-portal.estudiantes') }}" class="quick-action">
                                <i class="fas fa-users text-primary"></i>
                                <div>
                                    <strong>Ver Mis Estudiantes</strong>
                                    <br><small class="text-muted">Lista de estudiantes en mis rutas</small>
                                </div>
                            </a>
                            <a href="{{ route('chofer-portal.historial') }}" class="quick-action">
                                <i class="fas fa-history text-info"></i>
                                <div>
                                    <strong>Historial</strong>
                                    <br><small class="text-muted">Ver registros anteriores</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
