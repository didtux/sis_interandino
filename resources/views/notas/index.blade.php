@extends('layouts.app')

@section('content')
@php
    $esDocenteVinculado = auth()->user()->us_entidad_tipo === 'docente' && auth()->user()->us_entidad_id;
    $esAdmin = auth()->user()->rol_id == 1;
    $tabActivo = request('tab', 'inicio');
@endphp

<div class="section-body">
    <div class="row">
        <div class="col-12">

            {{-- ── Cabecera ──────────────────────────────────────── --}}
            <div class="card modern-card mb-0" style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Módulo de Notas — {{ $gestion }}</h4>
                    <div class="d-flex" style="gap:6px;">
                        @if($esAdmin)
                            <a href="{{ route('notas.configuracion') }}" class="btn btn-sm btn-primary-modern">
                                <i class="fas fa-cog mr-1"></i>Configuración
                            </a>
                        @endif
                    </div>
                </div>

                {{-- ── Tabs de navegación ────────────────────────── --}}
                <div class="card-body pb-0 pt-2">
                    <ul class="nav nav-tabs" id="notasTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $tabActivo == 'inicio' ? 'active' : '' }}"
                               href="{{ route('notas.index', array_merge(request()->query(), ['tab' => 'inicio'])) }}">
                                <i class="fas fa-home mr-1"></i>Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tabActivo == 'notas' ? 'active' : '' }}"
                               href="{{ route('notas.index', array_merge(request()->query(), ['tab' => 'notas'])) }}">
                                <i class="fas fa-pen mr-1"></i>Notas
                                @if($statEnviadas > 0 && $esAdmin)
                                    <span class="badge badge-warning ml-1" style="font-size:9px;">{{ $statEnviadas }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tabActivo == 'asistencia' ? 'active' : '' }}"
                               href="{{ route('notas.index', array_merge(request()->query(), ['tab' => 'asistencia'])) }}">
                                <i class="fas fa-user-check mr-1"></i>Asistencia Clases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tabActivo == 'reportes' ? 'active' : '' }}"
                               href="{{ route('notas.index', array_merge(request()->query(), ['tab' => 'reportes'])) }}">
                                <i class="fas fa-file-pdf mr-1"></i>Reportes
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ── Contenido de tabs ─────────────────────────────── --}}
            <div class="card modern-card" style="border-top-left-radius:0;border-top-right-radius:0;border-top:none;">
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
                    @endif

                    {{-- ════════════════════════════════════════════════════ --}}
                    {{-- TAB INICIO - DASHBOARD                               --}}
                    {{-- ════════════════════════════════════════════════════ --}}
                    @if($tabActivo == 'inicio')

                    {{-- Stats cards --}}
                    <div class="row mb-4">
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card text-center" style="border-left:4px solid #28a745;">
                                <div class="card-body py-3">
                                    <div style="font-size:2rem;font-weight:700;color:#28a745;">{{ $statAprobadas }}</div>
                                    <div class="text-muted" style="font-size:0.8rem;"><i class="fas fa-check-circle mr-1 text-success"></i>Notas Aprobadas</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card text-center" style="border-left:4px solid #ffc107;">
                                <div class="card-body py-3">
                                    <div style="font-size:2rem;font-weight:700;color:#ffc107;">{{ $statEnviadas }}</div>
                                    <div class="text-muted" style="font-size:0.8rem;"><i class="fas fa-clock mr-1 text-warning"></i>Pendientes Aprobación</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card text-center" style="border-left:4px solid #6c757d;">
                                <div class="card-body py-3">
                                    <div style="font-size:2rem;font-weight:700;color:#6c757d;">{{ $statBorradores }}</div>
                                    <div class="text-muted" style="font-size:0.8rem;"><i class="fas fa-save mr-1"></i>En Borrador</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card text-center" style="border-left:4px solid #dc3545;">
                                <div class="card-body py-3">
                                    <div style="font-size:2rem;font-weight:700;color:#dc3545;">{{ $statRechazadas }}</div>
                                    <div class="text-muted" style="font-size:0.8rem;"><i class="fas fa-times-circle mr-1 text-danger"></i>Rechazadas</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Ranking --}}
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2" style="background:#2c3e50;color:#fff;">
                                    <i class="fas fa-trophy mr-2" style="color:#f39c12;"></i>
                                    <strong>Ranking — Mejores Estudiantes {{ $gestion }}</strong>
                                </div>
                                <div class="card-body p-0">
                                    @if($ranking->isEmpty())
                                        <div class="text-center text-muted py-4" style="font-size:0.85rem;">
                                            <i class="fas fa-info-circle mr-1"></i>Sin datos de notas aprobadas aún.
                                        </div>
                                    @else
                                        <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                                            <thead style="background:#f8f9fa;">
                                                <tr>
                                                    <th style="width:35px;" class="text-center">#</th>
                                                    <th>Estudiante</th>
                                                    <th>Curso</th>
                                                    <th class="text-center">Prom.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ranking as $i => $r)
                                                    <tr>
                                                        <td class="text-center font-weight-bold">
                                                            @if($i == 0) <span style="color:#f39c12;font-size:1rem;">🥇</span>
                                                            @elseif($i == 1) <span style="font-size:1rem;">🥈</span>
                                                            @elseif($i == 2) <span style="font-size:1rem;">🥉</span>
                                                            @else {{ $i + 1 }}
                                                            @endif
                                                        </td>
                                                        <td><strong>{{ mb_strtoupper($r->est_apellidos) }}</strong> {{ $r->est_nombres }}</td>
                                                        <td><span class="badge badge-primary" style="font-size:10px;">{{ $r->cur_nombre }}</span></td>
                                                        <td class="text-center">
                                                            <strong class="{{ $r->promedio >= 71 ? 'text-success' : ($r->promedio >= 51 ? 'text-warning' : 'text-danger') }}">
                                                                {{ $r->promedio }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Estudiantes en peligro --}}
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2" style="background:#c0392b;color:#fff;">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Estudiantes en Peligro (promedio &lt; 51)</strong>
                                </div>
                                <div class="card-body p-0">
                                    @if($enPeligro->isEmpty())
                                        <div class="text-center text-muted py-4" style="font-size:0.85rem;">
                                            <i class="fas fa-check-circle mr-1 text-success"></i>Ningún estudiante en situación de riesgo.
                                        </div>
                                    @else
                                        <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                                            <thead style="background:#f8f9fa;">
                                                <tr>
                                                    <th>Estudiante</th>
                                                    <th>Curso</th>
                                                    <th class="text-center">Prom.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($enPeligro as $r)
                                                    <tr class="{{ $r->promedio < 40 ? 'table-danger' : 'table-warning' }}">
                                                        <td><strong>{{ mb_strtoupper($r->est_apellidos) }}</strong> {{ $r->est_nombres }}</td>
                                                        <td><span class="badge badge-secondary" style="font-size:10px;">{{ $r->cur_nombre }}</span></td>
                                                        <td class="text-center font-weight-bold text-danger">{{ $r->promedio }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Periodos info --}}
                    @if($periodos->isNotEmpty())
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0" style="font-size:0.85rem;">
                                <i class="fas fa-calendar-alt mr-2"></i><strong>Periodos {{ $gestion }}:</strong>
                                @foreach($periodos as $p)
                                    @php $hoy = now()->toDateString(); $act = $hoy >= $p->periodo_fecha_inicio->toDateString() && $hoy <= $p->periodo_fecha_fin->toDateString(); @endphp
                                    <span class="badge {{ $act ? 'badge-success' : 'badge-secondary' }} ml-2">
                                        {{ $p->periodo_nombre }}: {{ $p->periodo_fecha_inicio->format('d/m') }} – {{ $p->periodo_fecha_fin->format('d/m/Y') }}
                                        @if($act) <i class="fas fa-circle ml-1" style="font-size:8px;"></i> @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ════════════════════════════════════════════════════ --}}
                    {{-- TAB NOTAS                                            --}}
                    {{-- ════════════════════════════════════════════════════ --}}
                    @elseif($tabActivo == 'notas')

                    @if($periodos->isEmpty())
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>No hay periodos configurados para {{ $gestion }}.</div>
                    @endif

                    {{-- Filtros --}}
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="tab" value="notas">
                        <div class="row">
                            @if(!$esDocenteVinculado)
                            <div class="col-md-3 mb-2">
                                <label class="small text-muted mb-1">Docente</label>
                                <input type="text" name="buscar" class="form-control" placeholder="Nombre o apellido..." value="{{ request('buscar') }}">
                            </div>
                            @endif
                            <div class="{{ $esDocenteVinculado ? 'col-md-4' : 'col-md-3' }} mb-2">
                                <label class="small text-muted mb-1">Curso</label>
                                <select name="cur_codigo[]" class="form-control select2-multi" multiple style="width:100%">
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}" {{ in_array($c->cur_codigo, (array)request('cur_codigo', [])) ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="{{ $esDocenteVinculado ? 'col-md-4' : 'col-md-3' }} mb-2">
                                <label class="small text-muted mb-1">Materia</label>
                                <select name="mat_codigo[]" class="form-control select2-multi" multiple style="width:100%">
                                    @foreach($materias as $m)
                                        <option value="{{ $m->mat_codigo }}" {{ in_array($m->mat_codigo, (array)request('mat_codigo', [])) ? 'selected' : '' }}>{{ $m->mat_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Borrador</option>
                                    <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Enviado</option>
                                    <option value="2" {{ request('estado') === '2' ? 'selected' : '' }}>Aprobado</option>
                                    <option value="3" {{ request('estado') === '3' ? 'selected' : '' }}>Rechazado</option>
                                </select>
                            </div>
                            <div class="col-md-1 mb-2 d-flex align-items-end" style="gap:4px;">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
                                <a href="{{ route('notas.index', ['tab' => 'notas']) }}" class="btn btn-secondary btn-block mt-0"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                        <div>
                            @if($dimensiones->isNotEmpty())
                                <small class="text-muted">Dimensiones:</small>
                                @foreach($dimensiones as $dim)
                                    <span class="modern-badge badge-primary-modern" style="font-size:11px;">{{ $dim->dimension_nombre }}/{{ $dim->dimension_valor_max }} ({{ $dim->dimension_columnas }}col)</span>
                                @endforeach
                            @endif
                        </div>
                        <span class="badge badge-secondary p-1">{{ $asignaciones->count() }} asignación(es)</span>
                    </div>

                    <div class="table-responsive-modern">
                        <table class="modern-table" id="tablaNotas">
                            <thead>
                                <tr>
                                    @if(!$esDocenteVinculado)<th>Docente</th>@endif
                                    <th>Curso</th>
                                    <th>Materia</th>
                                    @foreach($periodos as $periodo)
                                        @php
                                            $hoy = now()->toDateString();
                                            $activo = $hoy >= $periodo->periodo_fecha_inicio->toDateString() && $hoy <= $periodo->periodo_fecha_fin->toDateString();
                                        @endphp
                                        <th class="text-center">
                                            {{ $periodo->periodo_nombre }}
                                            @if($activo)<br><span class="badge badge-success" style="font-size:9px;">Activo</span>@endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $asig)
                                    <tr>
                                        @if(!$esDocenteVinculado)
                                            <td data-label="Docente">
                                                <strong>{{ $asig->docente->doc_apellidos ?? '' }}</strong> {{ $asig->docente->doc_nombres ?? '' }}
                                            </td>
                                        @endif
                                        <td data-label="Curso">
                                            <span class="modern-badge badge-primary-modern">{{ $asig->curso->cur_nombre ?? $asig->cur_codigo }}</span>
                                        </td>
                                        <td data-label="Materia">
                                            <span class="modern-badge badge-warning-modern">{{ $asig->materia->mat_nombre ?? $asig->mat_codigo }}</span>
                                        </td>
                                        @foreach($periodos as $periodo)
                                            @php
                                                $nota = \App\Models\Nota::where('curmatdoc_id', $asig->curmatdoc_id)
                                                    ->where('periodo_id', $periodo->periodo_id)->first();
                                                $estado = $nota->nota_estado ?? -1;
                                                $btnClass = match($estado) { 2=>'btn-success', 1=>'btn-warning', 3=>'btn-danger', 0=>'btn-secondary', default=>'btn-outline-primary' };
                                                $iconClass = match($estado) { 2=>'fa-check-circle', 1=>'fa-clock', 3=>'fa-times-circle', 0=>'fa-save', default=>'fa-edit' };
                                                $label = match($estado) { 2=>'Aprobado', 1=>'Enviado', 3=>'Rechazado', 0=>'Borrador', default=>'Calificar' };
                                            @endphp
                                            <td class="text-center" data-label="{{ $periodo->periodo_nombre }}">
                                                <a href="{{ route('notas.calificar', [$asig->curmatdoc_id, $periodo->periodo_id]) }}"
                                                   class="btn btn-sm {{ $btnClass }}" style="min-width:105px;">
                                                    <i class="fas {{ $iconClass }} mr-1"></i>{{ $label }}
                                                </a>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $periodos->count() + (!$esDocenteVinculado ? 3 : 2) }}">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list"></i>
                                                <h5>No hay resultados</h5>
                                                <p>Ajuste los filtros o verifique las asignaciones en el módulo de Cursos</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- ════════════════════════════════════════════════════ --}}
                    {{-- TAB ASISTENCIA CLASES                                --}}
                    {{-- ════════════════════════════════════════════════════ --}}
                    @elseif($tabActivo == 'asistencia')

                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                El control de asistencia por clase se gestiona desde el módulo de <strong>Asistencia de Clases</strong>.
                                Puede registrar y visualizar la asistencia por materia, periodo y estudiante.
                            </div>
                            <a href="{{ route('asistencia-clases.index') }}" class="btn btn-primary-modern btn-lg">
                                <i class="fas fa-user-check mr-2"></i>Ir a Asistencia de Clases
                            </a>
                        </div>
                        <div class="col-md-4">
                            <div class="card" style="border-left:4px solid #17a2b8;">
                                <div class="card-body py-3">
                                    <h6 class="text-muted mb-2"><i class="fas fa-info-circle mr-1"></i>Información</h6>
                                    <ul class="mb-0" style="font-size:0.85rem;">
                                        <li>Registro de asistencia por fecha y clase</li>
                                        <li>Estados: Presente, Atraso, Falta, Licencia</li>
                                        <li>Vista general en grilla (Excel-style)</li>
                                        <li>Filtros por materia y periodo</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ════════════════════════════════════════════════════ --}}
                    {{-- TAB REPORTES                                         --}}
                    {{-- ════════════════════════════════════════════════════ --}}
                    @elseif($tabActivo == 'reportes')

                    <div class="row">

                        {{-- ── Reporte 1: Personal del Estudiante ───────── --}}
                        <div class="col-md-4 mb-4">
                            <div class="card h-100" style="border-top:4px solid #3498db;">
                                <div class="card-header py-2" style="background:#3498db;color:#fff;">
                                    <i class="fas fa-user-graduate mr-2"></i>
                                    <strong>Reporte Personal del Estudiante</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">
                                        Genera el boletín individual con materias por trimestre, promedio anual,
                                        asistencia, enfermería y control de seguimiento.
                                    </p>
                                    <form action="{{ route('notas.reporte-personal') }}" method="GET" target="_blank">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Gestión</label>
                                            <input type="number" name="gestion" class="form-control form-control-sm"
                                                   value="{{ $gestion }}" min="2020" max="2099">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Curso</label>
                                            <select name="_curso_est" id="filtro_curso_personal" class="form-control form-control-sm rpt-select2">
                                                <option value="">— Seleccione curso —</option>
                                                @foreach($cursos as $c)
                                                    <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold">Estudiante</label>
                                            <select name="est_codigo" id="select_estudiante_personal" class="form-control form-control-sm rpt-select2">
                                                <option value="">— Seleccione primero el curso —</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block btn-sm">
                                            <i class="fas fa-file-pdf mr-1"></i>Generar PDF
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ── Reporte 2: Centralizador ─────────────────── --}}
                        <div class="col-md-4 mb-4">
                            <div class="card h-100" style="border-top:4px solid #27ae60;">
                                <div class="card-header py-2" style="background:#27ae60;color:#fff;">
                                    <i class="fas fa-table mr-2"></i>
                                    <strong>Reporte Centralizador</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">
                                        Muestra todas las materias de un curso con las notas por trimestre,
                                        promedio anual y resumen de enfermería y psicopedagogía.
                                    </p>
                                    <form action="{{ route('notas.reporte-centralizador') }}" method="GET" target="_blank">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Gestión</label>
                                            <input type="number" name="gestion" class="form-control form-control-sm"
                                                   value="{{ $gestion }}" min="2020" max="2099">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Curso</label>
                                            <select name="cur_codigo" id="select_curso_centralizador" class="form-control form-control-sm rpt-select2" required>
                                                <option value="">— Seleccione —</option>
                                                @foreach($cursos as $c)
                                                    <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold">Periodo (opcional)</label>
                                            <select name="periodo_id" class="form-control form-control-sm rpt-select2">
                                                <option value="">Año completo</option>
                                                @foreach($periodos as $p)
                                                    <option value="{{ $p->periodo_id }}">{{ $p->periodo_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-block btn-sm">
                                            <i class="fas fa-file-pdf mr-1"></i>Generar PDF
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ── Reporte 3: General Trimestral ────────────── --}}
                        <div class="col-md-4 mb-4">
                            <div class="card h-100" style="border-top:4px solid #8e44ad;">
                                <div class="card-header py-2" style="background:#8e44ad;color:#fff;">
                                    <i class="fas fa-chart-bar mr-2"></i>
                                    <strong>Reporte General de Notas</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">
                                        Registro general de todos los estudiantes del curso con notas por materia
                                        y resumen de asistencia por trimestre.
                                    </p>
                                    <form action="{{ route('notas.reporte-general') }}" method="GET" target="_blank">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Gestión</label>
                                            <input type="number" name="gestion" class="form-control form-control-sm"
                                                   value="{{ $gestion }}" min="2020" max="2099">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Curso</label>
                                            <select name="cur_codigo" id="select_curso_general" class="form-control form-control-sm rpt-select2" required>
                                                <option value="">— Seleccione —</option>
                                                @foreach($cursos as $c)
                                                    <option value="{{ $c->cur_codigo }}">{{ $c->cur_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold">Periodo (opcional)</label>
                                            <select name="periodo_id" id="select_periodo_general" class="form-control form-control-sm rpt-select2">
                                                <option value="">Todos los trimestres</option>
                                                @foreach($periodos as $p)
                                                    <option value="{{ $p->periodo_id }}">{{ $p->periodo_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-block btn-sm" style="background:#8e44ad;color:#fff;">
                                            <i class="fas fa-file-pdf mr-1"></i>Generar PDF
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /row reportes --}}
                    @endif

                </div>{{-- /card-body --}}
            </div>{{-- /card --}}

        </div>
    </div>
</div>
@endsection

@php
$estudiantesJs = $todosEstudiantes->map(function($e) {
    return [
        'id'  => $e->est_codigo,
        'text'=> mb_strtoupper($e->est_apellidos) . ' ' . $e->est_nombres,
        'cur' => $e->cur_codigo,
    ];
})->values();
@endphp

@section('scripts')
<script>
// Datos de estudiantes para filtrado dinámico
var todosEstudiantes = @json($estudiantesJs);

$(document).ready(function() {

    // ── Select2 filtros de notas (tab notas) ──
    $('.select2-multi').select2({
        theme: 'bootstrap4', width: '100%',
        allowClear: true, placeholder: 'Seleccione...',
        closeOnSelect: false
    });

    // ── Select2 para todos los selects del tab reportes ──
    var s2Opts = { theme: 'bootstrap4', width: '100%', allowClear: true };

    $('#filtro_curso_personal').select2($.extend({}, s2Opts, {
        placeholder: '— Seleccione curso —'
    }));

    $('#select_curso_centralizador').select2($.extend({}, s2Opts, {
        placeholder: '— Seleccione —'
    }));

    $('#select_curso_general').select2($.extend({}, s2Opts, {
        placeholder: '— Seleccione —'
    }));

    $('#select_periodo_general').select2($.extend({}, s2Opts, {
        placeholder: 'Todos los trimestres',
        allowClear: true
    }));

    // ── Estudiante: inicializar vacío ──
    inicializarEstudiantesSelect([]);

    // ── Cuando cambia el curso, recargar estudiantes ──
    $('#filtro_curso_personal').on('change', function() {
        var curCodigo = $(this).val();
        var filtrados = curCodigo
            ? todosEstudiantes.filter(function(e) { return e.cur == curCodigo; })
            : todosEstudiantes;
        inicializarEstudiantesSelect(filtrados);
    });

    function inicializarEstudiantesSelect(lista) {
        var $sel = $('#select_estudiante_personal');
        // Destruir Select2 si ya está activo
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        // Limpiar y reconstruir opciones
        $sel.empty().append('<option value="">— Seleccione estudiante —</option>');
        $.each(lista, function(i, e) {
            $sel.append(new Option(e.text, e.id, false, false));
        });
        // Reinicializar Select2
        $sel.select2($.extend({}, s2Opts, {
            placeholder: lista.length === 0 ? '— Seleccione primero el curso —' : '— Buscar estudiante —',
            language: {
                noResults: function() { return 'Sin resultados'; }
            }
        }));
    }

});
</script>
@endsection
