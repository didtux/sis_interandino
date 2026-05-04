@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <h4 class="mb-1"><i class="fas fa-file-excel mr-2 text-success"></i>Preview de Importación</h4>
                            <span class="modern-badge badge-primary-modern">{{ $asignacion->curso->cur_nombre }}</span>
                            <span class="modern-badge badge-warning-modern">{{ $asignacion->materia->mat_nombre }}</span>
                            <span class="modern-badge badge-success-modern">{{ $periodo->periodo_nombre }}</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Tipo:</small>
                            <strong>
                                @if($importacion->import_tipo == 'notas') <i class="fas fa-pen text-primary mr-1"></i>Solo Notas
                                @elseif($importacion->import_tipo == 'asistencia') <i class="fas fa-user-check text-info mr-1"></i>Solo Asistencia
                                @else <i class="fas fa-layer-group text-success mr-1"></i>Notas + Asistencia
                                @endif
                            </strong><br>
                            <small class="text-muted">Archivo:</small> <strong>{{ $importacion->import_archivo }}</strong><br>
                            <small class="text-muted">Fecha:</small> <strong>{{ \Carbon\Carbon::parse($importacion->import_fecha)->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div class="col-md-3 text-right">
                            <form action="{{ route('notas.importar-cancelar', $importacion->import_id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('¿Cancelar esta importación?')">
                                    <i class="fas fa-times mr-1"></i>Cancelar
                                </button>
                            </form>
                            <form action="{{ route('notas.importar-confirmar', $importacion->import_id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('¿Confirmar la importación? Las notas se guardarán como BORRADOR.')">
                                    <i class="fas fa-check-circle mr-1"></i>Confirmar Importación
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
            @endif

            {{-- Resumen --}}
            @php $resumen = $importacion->import_resumen ?? []; @endphp
            <div class="row mb-3">
                @if(isset($resumen['notas']))
                    <div class="col-md-{{ isset($resumen['asistencia']) ? '6' : '12' }}">
                        <div class="card" style="border-left:4px solid #3498db;">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="fas fa-pen text-primary mr-1"></i>Resumen Notas</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div style="font-size:1.5rem;font-weight:700;color:#3498db;">{{ $resumen['notas']['total_excel'] }}</div>
                                        <small class="text-muted">En Excel</small>
                                    </div>
                                    <div class="col-4">
                                        <div style="font-size:1.5rem;font-weight:700;color:#27ae60;">{{ $resumen['notas']['matcheados'] }}</div>
                                        <small class="text-muted">Encontrados</small>
                                    </div>
                                    <div class="col-4">
                                        <div style="font-size:1.5rem;font-weight:700;color:#e74c3c;">{{ count($resumen['notas']['no_encontrados']) }}</div>
                                        <small class="text-muted">No encontrados</small>
                                    </div>
                                </div>
                                @if(!empty($resumen['notas']['no_encontrados']))
                                    <div class="mt-2">
                                        <small class="text-danger"><strong>No encontrados:</strong></small><br>
                                        @foreach($resumen['notas']['no_encontrados'] as $ne)
                                            <span class="badge badge-danger" style="font-size:10px;">{{ $ne }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                @if(isset($resumen['asistencia']))
                    <div class="col-md-{{ isset($resumen['notas']) ? '6' : '12' }}">
                        <div class="card" style="border-left:4px solid #17a2b8;">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="fas fa-user-check text-info mr-1"></i>Resumen Asistencia</h6>
                                <div class="row text-center">
                                    <div class="col-3">
                                        <div style="font-size:1.5rem;font-weight:700;color:#17a2b8;">{{ $resumen['asistencia']['total_excel'] }}</div>
                                        <small class="text-muted">En Excel</small>
                                    </div>
                                    <div class="col-3">
                                        <div style="font-size:1.5rem;font-weight:700;color:#27ae60;">{{ $resumen['asistencia']['matcheados'] }}</div>
                                        <small class="text-muted">Encontrados</small>
                                    </div>
                                    <div class="col-3">
                                        <div style="font-size:1.5rem;font-weight:700;color:#e74c3c;">{{ count($resumen['asistencia']['no_encontrados']) }}</div>
                                        <small class="text-muted">No encontrados</small>
                                    </div>
                                    <div class="col-3">
                                        <div style="font-size:1.5rem;font-weight:700;color:#8e44ad;">{{ $resumen['asistencia']['total_fechas'] }}</div>
                                        <small class="text-muted">Fechas</small>
                                    </div>
                                </div>
                                @if(!empty($resumen['asistencia']['no_encontrados']))
                                    <div class="mt-2">
                                        <small class="text-danger"><strong>No encontrados:</strong></small><br>
                                        @foreach($resumen['asistencia']['no_encontrados'] as $ne)
                                            <span class="badge badge-danger" style="font-size:10px;">{{ $ne }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @php $data = $importacion->import_data; @endphp

            {{-- PREVIEW NOTAS --}}
            @if(!empty($data['notas']))
                <div class="card modern-card mb-3">
                    <div class="card-header py-2" style="background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;">
                        <h5 class="mb-0"><i class="fas fa-pen mr-2"></i>Preview de Notas — {{ $periodo->periodo_nombre }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div style="overflow-x:auto;">
                            <table class="modern-table" style="font-size:0.82rem;">
                                <thead>
                                    <tr style="background:#2c3e50;color:#fff;">
                                        <th rowspan="2" style="width:35px;">N°</th>
                                        <th rowspan="2" style="min-width:200px;">ESTUDIANTE</th>
                                        @php $colores = ['#e74c3c','#3498db','#2ecc71','#9b59b6']; $idx = 0; @endphp
                                        @foreach($dimensiones as $dim)
                                            <th colspan="{{ $dim->dimension_columnas }}" style="text-align:center;background:{{ $colores[$idx % 4] }};">
                                                {{ $dim->dimension_nombre }}/{{ $dim->dimension_valor_max }}
                                            </th>
                                            <th rowspan="2" style="text-align:center;background:rgba(0,0,0,0.3);color:#fff;width:55px;">PROM</th>
                                            @php $idx++; @endphp
                                        @endforeach
                                        <th rowspan="2" style="text-align:center;background:#f39c12;color:#fff;width:65px;">PROM.<br>TRIM.</th>
                                    </tr>
                                    <tr style="background:#34495e;color:#fff;font-size:0.72rem;">
                                        @foreach($dimensiones as $dim)
                                            @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                                                <th style="text-align:center;width:50px;">N{{ $c }}</th>
                                            @endfor
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['notas'] as $i => $notaEst)
                                        <tr>
                                            <td style="text-align:center;font-weight:bold;">{{ $i + 1 }}</td>
                                            <td style="white-space:nowrap;">
                                                {{ $notaEst['nombre'] }}
                                                <br><small class="text-muted">{{ $notaEst['est_codigo'] }}</small>
                                            </td>
                                            @foreach($dimensiones as $dim)
                                                @php $dimData = $notaEst['dimensiones'][$dim->dimension_id] ?? null; @endphp
                                                @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                                                    <td style="text-align:center;">
                                                        @if($dimData && isset($dimData['valores'][$c]))
                                                            <strong>{{ $dimData['valores'][$c] }}</strong>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                @endfor
                                                <td style="text-align:center;font-weight:bold;background:rgba(0,0,0,0.03);">
                                                    {{ $dimData['promedio_excel'] ?? 0 }}
                                                </td>
                                            @endforeach
                                            <td style="text-align:center;font-weight:bold;font-size:1rem;background:#fef3cd;">
                                                {{ $notaEst['promedio_trimestral'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- PREVIEW ASISTENCIA --}}
            @if(!empty($data['asistencia']))
                @php $fechasAsis = $resumen['asistencia']['fechas'] ?? []; @endphp
                <div class="card modern-card mb-3">
                    <div class="card-header py-2" style="background:linear-gradient(135deg,#17a2b8,#138496);color:#fff;">
                        <h5 class="mb-0"><i class="fas fa-user-check mr-2"></i>Preview de Asistencia — {{ $periodo->periodo_nombre }} ({{ count($fechasAsis) }} fechas)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div style="overflow-x:auto;max-height:70vh;">
                            <table class="modern-table" style="font-size:0.8rem;">
                                <thead>
                                    <tr style="background:#2c3e50;color:#fff;">
                                        <th style="width:35px;position:sticky;left:0;z-index:3;background:#2c3e50;">N°</th>
                                        <th style="min-width:180px;position:sticky;left:35px;z-index:3;background:#2c3e50;">ESTUDIANTE</th>
                                        @foreach($fechasAsis as $f)
                                            <th style="text-align:center;min-width:40px;">
                                                <div style="writing-mode:vertical-rl;transform:rotate(180deg);font-size:0.65rem;height:65px;">
                                                    {{ \Carbon\Carbon::parse($f)->format('d/m') }}
                                                </div>
                                            </th>
                                        @endforeach
                                        <th style="text-align:center;background:#27ae60;color:#fff;">P</th>
                                        <th style="text-align:center;background:#f39c12;color:#fff;">A</th>
                                        <th style="text-align:center;background:#e74c3c;color:#fff;">F</th>
                                        <th style="text-align:center;background:#17a2b8;color:#fff;">L</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['asistencia'] as $i => $asisEst)
                                        @php
                                            $regMap = collect($asisEst['registros'])->keyBy('fecha');
                                            $totP = collect($asisEst['registros'])->where('estado', 'P')->count();
                                            $totA = collect($asisEst['registros'])->where('estado', 'A')->count();
                                            $totF = collect($asisEst['registros'])->where('estado', 'F')->count();
                                            $totL = collect($asisEst['registros'])->where('estado', 'L')->count();
                                        @endphp
                                        <tr>
                                            <td style="text-align:center;font-weight:bold;position:sticky;left:0;background:#fff;z-index:1;">{{ $i + 1 }}</td>
                                            <td style="white-space:nowrap;position:sticky;left:35px;background:#fff;z-index:1;">
                                                {{ $asisEst['nombre'] }}
                                            </td>
                                            @foreach($fechasAsis as $f)
                                                @php $reg = $regMap[$f] ?? null; $estado = $reg['estado'] ?? ''; @endphp
                                                <td style="text-align:center;font-weight:bold;
                                                    @if($estado == 'P') background:#d4edda;color:#155724;
                                                    @elseif($estado == 'A') background:#fff3cd;color:#856404;
                                                    @elseif($estado == 'F') background:#f8d7da;color:#721c24;
                                                    @elseif($estado == 'L') background:#d1ecf1;color:#0c5460;
                                                    @endif">{{ $estado }}</td>
                                            @endforeach
                                            <td style="text-align:center;font-weight:bold;color:#27ae60;">{{ $totP }}</td>
                                            <td style="text-align:center;font-weight:bold;color:#f39c12;">{{ $totA }}</td>
                                            <td style="text-align:center;font-weight:bold;color:#e74c3c;">{{ $totF }}</td>
                                            <td style="text-align:center;font-weight:bold;color:#17a2b8;">{{ $totL }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Botones finales --}}
            <div class="card modern-card">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        <small class="text-muted">Las notas se guardarán como <strong>BORRADOR</strong>. Podrá revisarlas y enviarlas para aprobación desde la vista de calificación.</small>
                    </div>
                    <div>
                        <form action="{{ route('notas.importar-cancelar', $importacion->import_id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Cancelar?')">
                                <i class="fas fa-times mr-1"></i>Cancelar
                            </button>
                        </form>
                        <form action="{{ route('notas.importar-confirmar', $importacion->import_id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('¿Confirmar la importación?')">
                                <i class="fas fa-check-circle mr-1"></i>Confirmar e Importar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
