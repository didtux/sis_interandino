@extends('layouts.app')

@section('content')
<style>
    .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px; color: white; margin-bottom: 24px; position: relative; overflow: hidden; }
    .profile-header::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; }
    .profile-avatar { width: 120px; height: 120px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.4); object-fit: cover; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; }
    .profile-avatar i { font-size: 3rem; color: rgba(255,255,255,0.7); }
    .info-card { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; padding: 20px; height: 100%; }
    .info-card h6 { font-weight: 700; color: #2d3436; margin-bottom: 16px; font-size: 0.95rem; }
    .info-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f3f5; }
    .info-item:last-child { border-bottom: none; }
    .info-item .info-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 0.85rem; flex-shrink: 0; }
    .info-item .info-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-item .info-value { font-weight: 600; color: #2d3436; font-size: 0.95rem; }
    .asignacion-row { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s; }
    .asignacion-row:hover { border-color: #667eea; box-shadow: 0 2px 8px rgba(102,126,234,0.1); }
    .asignacion-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; margin-right: 12px; }
    .curso-chip { display: inline-flex; align-items: center; gap: 4px; background: #e3f2fd; color: #1565c0; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="profile-header">
                <div class="d-flex align-items-center">
                    @if($docente->doc_foto)
                        <img src="{{ asset('storage/' . $docente->doc_foto) }}" alt="Foto" class="profile-avatar">
                    @else
                        <div class="profile-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    @endif
                    <div class="ml-4">
                        <h3 class="mb-1" style="font-weight:700;">{{ $docente->doc_nombres }} {{ $docente->doc_apellidos }}</h3>
                        <span style="background:rgba(255,255,255,0.2); padding:4px 14px; border-radius:20px; font-size:0.85rem;">
                            <i class="fas fa-id-badge mr-1"></i>{{ $docente->doc_codigo }}
                        </span>
                    </div>
                    <div class="ml-auto">
                        @puede('docentes', 'editar')
                        <a href="{{ route('docentes.edit', $docente->doc_id) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </a>
                        @endpuede
                        <a href="{{ route('docentes.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-arrow-left mr-1"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="info-card">
                        <h6><i class="fas fa-user text-primary mr-2"></i>Información Personal</h6>
                        <div class="info-item">
                            <div class="info-icon" style="background:#e3f2fd; color:#1976d2;"><i class="fas fa-id-card"></i></div>
                            <div>
                                <div class="info-label">Cédula de Identidad</div>
                                <div class="info-value">{{ $docente->doc_ci ?? 'No registrado' }}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-user-tag"></i></div>
                            <div>
                                <div class="info-label">Nombres</div>
                                <div class="info-value">{{ $docente->doc_nombres }}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon" style="background:#fff3e0; color:#e65100;"><i class="fas fa-user-tag"></i></div>
                            <div>
                                <div class="info-label">Apellidos</div>
                                <div class="info-value">{{ $docente->doc_apellidos ?? 'No registrado' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 mb-4">
                    <div class="info-card">
                        <h6>
                            <i class="fas fa-book-open text-success mr-2"></i>Materias y Cursos Asignados
                            <span class="modern-badge badge-primary-modern ml-2">{{ $docente->cursoMateriaDocentes->count() }}</span>
                        </h6>
                        <small class="text-muted d-block mb-3">Las asignaciones se gestionan desde el módulo de Cursos</small>
                        @forelse($docente->cursoMateriaDocentes as $cmd)
                            <div class="asignacion-row">
                                <div class="d-flex align-items-center">
                                    <div class="asignacion-icon" style="background:#fce4ec; color:#c62828;"><i class="fas fa-book"></i></div>
                                    <div>
                                        <strong style="font-size:0.95rem;">{{ $cmd->materia->mat_nombre ?? $cmd->mat_codigo }}</strong>
                                    </div>
                                </div>
                                <span class="curso-chip">
                                    <i class="fas fa-graduation-cap"></i>{{ $cmd->curso->cur_nombre ?? $cmd->cur_codigo }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle fa-2x text-muted mb-2 d-block"></i>
                                <p class="text-muted">No tiene materias ni cursos asignados.<br>Asigne desde el módulo de <strong>Cursos → Docentes por Materia</strong>.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
