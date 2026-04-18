@extends('layouts.app')

@section('content')
<style>
    .hijo-card { border-radius: 12px; border: 1px solid #e9ecef; overflow: hidden; margin-bottom: 20px; }
    .hijo-card-header { padding: 18px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; display: flex; align-items: center; gap: 16px; }
    .hijo-foto { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; }
    .hijo-foto i { font-size: 2rem; color: rgba(255,255,255,0.7); }
    .hijo-nombre { font-size: 1.15rem; font-weight: 700; }
    .hijo-curso { opacity: 0.85; font-size: 0.85rem; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; padding: 20px 24px; }
    .info-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 8px; background: #f8f9fa; }
    .info-item i { color: #667eea; margin-top: 2px; width: 18px; text-align: center; }
    .info-item .info-label { font-size: 0.7rem; text-transform: uppercase; color: #6c757d; letter-spacing: 0.5px; }
    .info-item .info-value { font-size: 0.9rem; font-weight: 600; color: #333; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-child mr-2"></i>Mis Hijos</h4></div>
                <div class="card-body p-0">
                    @forelse($estudiantes as $est)
                    <div class="hijo-card mx-3 mt-3 @if($loop->last) mb-3 @endif">
                        <div class="hijo-card-header">
                            <div class="hijo-foto">
                                @if($est->est_foto)
                                    <img src="{{ asset('storage/' . $est->est_foto) }}" style="width:70px;height:70px;border-radius:50%;object-fit:cover;">
                                @else
                                    <i class="fas fa-user-graduate"></i>
                                @endif
                            </div>
                            <div>
                                <div class="hijo-nombre">{{ mb_strtoupper($est->est_apellidos) }} {{ $est->est_nombres }}</div>
                                <div class="hijo-curso">{{ $est->curso->cur_nombre ?? 'Sin curso asignado' }}</div>
                                <div class="hijo-curso">Código: {{ $est->est_codigo }}</div>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <i class="fas fa-id-card"></i>
                                <div>
                                    <div class="info-label">Cédula de Identidad</div>
                                    <div class="info-value">{{ $est->est_ci ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-birthday-cake"></i>
                                <div>
                                    <div class="info-label">Fecha de Nacimiento</div>
                                    <div class="info-value">{{ $est->est_fechanac ? \Carbon\Carbon::parse($est->est_fechanac)->format('d/m/Y') : '—' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="info-label">Lugar de Nacimiento</div>
                                    <div class="info-value">{{ $est->est_lugarnac ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-barcode"></i>
                                <div>
                                    <div class="info-label">RUDE</div>
                                    <div class="info-value">{{ $est->est_rude ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <div class="info-label">Celular</div>
                                    <div class="info-value">{{ $est->est_celular ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-school"></i>
                                <div>
                                    <div class="info-label">U.E. de Procedencia</div>
                                    <div class="info-value">{{ $est->est_ueprocedencia ?: '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-child fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay estudiantes vinculados.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
