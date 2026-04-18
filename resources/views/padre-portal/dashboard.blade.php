@extends('layouts.app')

@section('content')
<style>
    .portal-welcome { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: #fff; padding: 24px 30px; margin-bottom: 20px; }
    .portal-welcome h2 { font-size: 1.5rem; font-weight: 700; margin: 0; }
    .portal-welcome p { opacity: 0.85; margin: 4px 0 0; font-size: 0.9rem; }
    .alerta-mora { border-radius: 10px; padding: 14px 18px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; }
    .alerta-mora.mensualidad { background: #fff3cd; border-left: 4px solid #ffc107; }
    .alerta-mora.transporte { background: #f8d7da; border-left: 4px solid #dc3545; }
    .alerta-mora i { font-size: 1.3rem; }
    .est-card { border-radius: 12px; border: 1px solid #e9ecef; transition: all 0.3s; overflow: hidden; }
    .est-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .est-card-header { padding: 16px 20px; background: linear-gradient(135deg, #f8f9fa, #fff); border-bottom: 1px solid #f0f0f0; }
    .est-card-body { padding: 16px 20px; }
    .stat-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .stat-item { flex: 1; min-width: 80px; text-align: center; padding: 10px 8px; border-radius: 8px; background: #f8f9fa; }
    .stat-item .stat-num { font-size: 1.5rem; font-weight: 700; line-height: 1; }
    .stat-item .stat-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .mora-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .mora-badge.danger { background: #f8d7da; color: #721c24; }
    .mora-badge.warning { background: #fff3cd; color: #856404; }
    .mora-badge.ok { background: #d4edda; color: #155724; }
    .quick-links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .quick-link { flex: 1; min-width: 100px; text-align: center; padding: 12px 8px; border-radius: 10px; text-decoration: none; color: #495057; background: #f8f9fa; border: 1px solid #e9ecef; transition: all 0.2s; font-size: 0.8rem; }
    .quick-link:hover { background: #667eea; color: #fff; text-decoration: none; border-color: #667eea; }
    .quick-link i { display: block; font-size: 1.2rem; margin-bottom: 4px; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">

            {{-- Bienvenida --}}
            <div class="portal-welcome">
                <h2><i class="fas fa-hand-wave mr-2"></i>Bienvenido/a, {{ $padre->pfam_nombres }}</h2>
                <p>Portal de seguimiento escolar — Gestión {{ $gestion }}</p>
            </div>

            {{-- Alertas de mora --}}
            @if(count($alertas) > 0)
                <div class="mb-3">
                    @foreach($alertas as $alerta)
                        @if($alerta['tipo'] === 'mensualidad')
                            <div class="alerta-mora mensualidad">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <div>
                                    <strong>{{ $alerta['est'] }}</strong> tiene <strong>{{ $alerta['meses'] }} mes(es)</strong> de mensualidad pendiente.
                                    <a href="{{ route('padre-portal.pagos') }}" class="ml-2">Ver detalle →</a>
                                </div>
                            </div>
                        @else
                            <div class="alerta-mora transporte">
                                <i class="fas fa-bus text-danger"></i>
                                <div>
                                    <strong>{{ $alerta['est'] }}</strong> tiene pago de transporte vencido.
                                    <a href="{{ route('padre-portal.pagos') }}" class="ml-2">Ver detalle →</a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Tarjetas por estudiante --}}
            <div class="row">
                @foreach($resumen as $info)
                    @php $est = $info['estudiante']; @endphp
                    <div class="col-md-6 mb-4">
                        <div class="est-card">
                            <div class="est-card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong style="font-size:1.05rem;">{{ mb_strtoupper($est->est_apellidos) }} {{ $est->est_nombres }}</strong>
                                    <br><span class="badge badge-primary">{{ $est->curso->cur_nombre ?? '-' }}</span>
                                </div>
                                <div class="d-flex flex-column align-items-end" style="gap:4px;">
                                    @if($info['mora_mensualidad'])
                                        <span class="mora-badge warning"><i class="fas fa-exclamation-circle"></i> Mora mensualidad</span>
                                    @else
                                        <span class="mora-badge ok"><i class="fas fa-check-circle"></i> Al día</span>
                                    @endif
                                    @if($info['mora_transporte'])
                                        <span class="mora-badge danger"><i class="fas fa-bus"></i> Mora transporte</span>
                                    @endif
                                </div>
                            </div>
                            <div class="est-card-body">
                                <div class="stat-row">
                                    <div class="stat-item">
                                        <div class="stat-num" style="color:{{ $info['promedio'] >= 51 ? '#28a745' : ($info['promedio'] > 0 ? '#dc3545' : '#6c757d') }};">
                                            {{ $info['promedio'] > 0 ? $info['promedio'] : '—' }}
                                        </div>
                                        <div class="stat-label">Promedio</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-num" style="color:{{ $info['faltas'] > 10 ? '#dc3545' : '#495057' }};">{{ $info['faltas'] }}</div>
                                        <div class="stat-label">Faltas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-num" style="color:#856404;">{{ $info['meses_pendientes'] ?? 0 }}</div>
                                        <div class="stat-label">Meses pend.</div>
                                    </div>
                                </div>

                                <div class="quick-links">
                                    <a href="{{ route('padre-portal.notas', ['est_codigo' => $est->est_codigo]) }}" class="quick-link">
                                        <i class="fas fa-star"></i>Notas
                                    </a>
                                    <a href="{{ route('padre-portal.asistencia', ['est_codigo' => $est->est_codigo]) }}" class="quick-link">
                                        <i class="fas fa-clipboard-check"></i>Asistencia
                                    </a>
                                    <a href="{{ route('padre-portal.pagos', ['est_codigo' => $est->est_codigo]) }}" class="quick-link">
                                        <i class="fas fa-money-bill"></i>Pagos
                                    </a>
                                    <a href="{{ route('padre-portal.enfermeria', ['est_codigo' => $est->est_codigo]) }}" class="quick-link">
                                        <i class="fas fa-heartbeat"></i>Enfermería
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</div>
@endsection
