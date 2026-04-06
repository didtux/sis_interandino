@extends('layouts.app')

@section('content')
<style>
    .tabla-asistencia th, .tabla-asistencia td { padding:4px 5px!important;text-align:center;vertical-align:middle;font-size:0.8rem; }
    .tabla-asistencia .col-nombre { text-align:left;white-space:nowrap;min-width:180px;position:sticky;left:0;background:#fff;z-index:1; }
    .tabla-asistencia thead th { position:sticky;top:0;z-index:2;background:#2c3e50;color:#fff; }
    .tabla-asistencia thead th.col-nombre { z-index:3; }
    .tabla-asistencia .col-num { width:30px; }
    .fecha-col { writing-mode:vertical-rl;transform:rotate(180deg);font-size:0.7rem;white-space:nowrap;height:80px; }
    .cell-P { background:#d4edda;color:#155724;font-weight:bold; }
    .cell-A { background:#fff3cd;color:#856404;font-weight:bold; }
    .cell-F { background:#f8d7da;color:#721c24;font-weight:bold; }
    .cell-L { background:#d1ecf1;color:#0c5460;font-weight:bold; }
    .col-total { background:#f8f9fa;font-weight:bold;font-size:0.75rem; }
    .col-total-header { background:#34495e!important;color:#fff;font-size:0.7rem; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <h4 class="mb-1"><i class="fas fa-clipboard-check mr-2"></i>Control de Asistencia - {{ $periodo->periodo_nombre }}</h4>
                            <span class="modern-badge badge-primary-modern">{{ $asignacion->curso->cur_nombre }}</span>
                            <span class="modern-badge badge-warning-modern">{{ $asignacion->materia->mat_nombre }}</span>
                            <span class="modern-badge badge-success-modern">{{ $asignacion->docente->doc_nombres }} {{ $asignacion->docente->doc_apellidos }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Inicio:</small> <strong>{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }}</strong><br>
                            <small class="text-muted">Fin:</small> <strong>{{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</strong><br>
                            <small class="text-muted">Fecha actual:</small> <strong class="text-danger">{{ now()->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-4 text-right">
                            @if($enRangoPeriodo && $esDocente)
                                <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id, 'fecha' => now()->toDateString()]) }}" class="btn btn-primary-modern">
                                    <i class="fas fa-plus-circle mr-1"></i>Registrar Asistencia Hoy
                                </a>
                            @endif
                            <a href="{{ route('asistencia-clases.index') }}" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif

            {{-- Filtros --}}
            <form method="GET" class="mb-2">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="buscar_est" class="form-control form-control-sm" placeholder="Buscar estudiante..." value="{{ request('buscar_est') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}" min="{{ $periodo->periodo_fecha_inicio->format('Y-m-d') }}" max="{{ $periodo->periodo_fecha_fin->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}" min="{{ $periodo->periodo_fecha_inicio->format('Y-m-d') }}" max="{{ $periodo->periodo_fecha_fin->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 d-flex" style="gap:6px;">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="{{ route('asistencia-clases.vista-general', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" class="btn btn-secondary btn-sm btn-block mt-0"><i class="fas fa-times"></i></a>
                    </div>
                </div>
            </form>

            {{-- Leyenda --}}
            <div class="mb-2">
                <span class="badge badge-success p-1">P = Presente</span>
                <span class="badge badge-warning p-1">A = Atraso</span>
                <span class="badge badge-danger p-1">F = Falta</span>
                <span class="badge badge-info p-1">L = Licencia</span>
                <span class="text-muted ml-2" style="font-size:12px;">{{ $fechas->count() }} día(s) registrado(s) | {{ $estudiantes->count() }} estudiantes</span>
            </div>

            {{-- Tabla tipo Excel --}}
            <div class="card modern-card">
                <div class="card-body p-0">
                    <div style="overflow-x:auto;max-height:75vh;">
                        <table class="table table-bordered tabla-asistencia mb-0">
                            <thead>
                                <tr>
                                    <th class="col-num">N°</th>
                                    <th class="col-nombre">NÓMINA DE ESTUDIANTES</th>
                                    @foreach($fechas as $f)
                                        @php $esHoy = $f == now()->toDateString(); @endphp
                                        <th style="{{ $esHoy ? 'background:#e74c3c;color:#fff;' : '' }}">
                                            <div class="fecha-col" style="{{ $esHoy ? 'color:#fff;' : '' }}">{{ \Carbon\Carbon::parse($f)->format('d/m/Y') }}</div>
                                        </th>
                                    @endforeach
                                    <th class="col-total-header text-white">Asist.<br>(P)</th>
                                    <th class="col-total-header text-white">Atrasos<br>(A)</th>
                                    <th class="col-total-header text-white">Faltas<br>(F)</th>
                                    <th class="col-total-header text-white">Licenc.<br>(L)</th>
                                    <th class="col-total-header text-white">Días<br>Trab.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estudiantes as $i => $est)
                                    @php
                                        $estAsis = $asistencias[$est->est_codigo] ?? collect();
                                        $tot = $totales[$est->est_codigo] ?? collect();
                                        $tP = $tot['P'] ?? 0;
                                        $tA = $tot['A'] ?? 0;
                                        $tF = $tot['F'] ?? 0;
                                        $tL = $tot['L'] ?? 0;
                                        $dias = $tP + $tA;
                                    @endphp
                                    <tr>
                                        <td class="col-num"><strong>{{ $est->lista_numero ?? ($i + 1) }}</strong></td>
                                        <td class="col-nombre">{{ $est->est_apellidos }} {{ $est->est_nombres }}</td>
                                        @foreach($fechas as $f)
                                            @php $a = $estAsis[$f] ?? null; $estado = $a ? $a->asiscl_estado : ''; $colHoy = $f == now()->toDateString(); @endphp
                                            <td class="{{ $estado ? 'cell-'.$estado : '' }}" style="{{ $colHoy && !$estado ? 'background:#fce4e4;' : '' }}">
                                                @if($estado) {{ $estado }} @endif
                                            </td>
                                        @endforeach
                                        <td class="col-total text-success">{{ $tP }}</td>
                                        <td class="col-total text-warning">{{ $tA }}</td>
                                        <td class="col-total text-danger">{{ $tF }}</td>
                                        <td class="col-total text-info">{{ $tL }}</td>
                                        <td class="col-total"><strong>{{ $dias }}</strong></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $fechas->count() + 7 }}" class="text-center text-muted py-4">No hay estudiantes</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Fechas registradas como chips clickeables --}}
            @if($fechas->count())
                <div class="card modern-card mt-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-calendar mr-2"></i>Fechas Registradas (click para editar)</h6></div>
                    <div class="card-body py-2">
                        @foreach($fechas as $f)
                            @php
                                $esHoy = $f == now()->toDateString();
                                $puedeEditar = $esDocente ? $esHoy : true;
                            @endphp
                            <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id, 'fecha' => $f]) }}"
                               class="btn btn-sm {{ $esHoy ? 'btn-danger' : ($puedeEditar ? 'btn-outline-primary' : 'btn-outline-secondary') }} m-1"
                               title="{{ $puedeEditar ? 'Editar' : 'Solo lectura' }}">
                                <i class="fas {{ $puedeEditar ? 'fa-edit' : 'fa-eye' }} mr-1"></i>{{ \Carbon\Carbon::parse($f)->format('d/m') }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
