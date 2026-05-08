@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h4 class="mb-0"><i class="fas fa-gavel mr-2"></i>Concejo Educativo</h4>
            <small class="text-muted">Gestión {{ $gestion }}</small>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-3">
                    <label>Curso</label>
                    <select name="curso" class="form-control select2-curso" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->cur_codigo }}" {{ $cursoCod == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Buscar estudiante</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido, código o CI..." value="{{ $buscar ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label>Gestión</label>
                    <input type="number" name="gestion" class="form-control" value="{{ $gestion }}" min="2020" max="2099">
                </div>
                <div class="col-md-3 d-flex align-items-end" style="gap:6px;">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Cargar</button>
                    @if($cursoCod)
                        <a href="{{ route('concejo.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                    @endif
                </div>
            </form>

            @if($cursoCod)
                {{-- Tabs --}}
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'documentos' ? 'active' : '' }}"
                           href="{{ route('concejo.index', ['curso'=>$cursoCod,'gestion'=>$gestion,'tab'=>'documentos']) }}">
                            <i class="fas fa-file-pdf mr-1"></i>Documentos
                            <span class="badge badge-secondary ml-1">{{ $estudiantes->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'mejores' ? 'active' : '' }}"
                           href="{{ route('concejo.index', ['curso'=>$cursoCod,'gestion'=>$gestion,'tab'=>'mejores']) }}">
                            <i class="fas fa-trophy mr-1 text-warning"></i>Mejores Estudiantes
                            <span class="badge badge-success ml-1">{{ $mejores->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'riesgo' ? 'active' : '' }}"
                           href="{{ route('concejo.index', ['curso'=>$cursoCod,'gestion'=>$gestion,'tab'=>'riesgo']) }}">
                            <i class="fas fa-exclamation-triangle mr-1 text-danger"></i>En Riesgo
                            <span class="badge badge-danger ml-1">{{ $enRiesgo->count() }}</span>
                        </a>
                    </li>
                </ul>

                {{-- ─── TAB DOCUMENTOS ─── --}}
                @if($tab === 'documentos')
                    @if($estudiantes->count())
                        <div class="table-responsive-modern">
                            <table class="modern-table">
                                <thead><tr><th>#</th><th>Estudiante</th><th>CI</th><th>Estado</th><th>Documento</th></tr></thead>
                                <tbody>
                                    @foreach($estudiantes as $i => $e)
                                        <tr style="{{ $e->est_visible == 0 ? 'background:#ffe6e6;' : '' }}">
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $e->est_apellidos }} {{ $e->est_nombres }}</td>
                                            <td>{{ $e->est_ci }}</td>
                                            <td>
                                                @if($e->est_visible == 0)<span class="modern-badge badge-danger-modern">RETIRADO</span>
                                                @else<span class="modern-badge badge-success-modern">ACTIVO</span>@endif
                                            </td>
                                            <td>
                                                <a href="{{ route('concejo.documento', $e->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-danger" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Documento Concejo
                                                </a>
                                                <a href="{{ route('concejo.detalle', $e->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-info" target="_blank" title="Ver detalle de faltas, atrasos y permisos por día">
                                                    <i class="fas fa-search-plus"></i> Detalle
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">No hay estudiantes en este curso.</div>
                    @endif
                @endif

                {{-- ─── TAB MEJORES ─── --}}
                @if($tab === 'mejores')
                    @if($mejores->count())
                        <p class="text-muted small mb-2"><i class="fas fa-info-circle mr-1"></i>Top 10 estudiantes ordenados por promedio anual descendente.</p>
                        <div class="table-responsive-modern">
                            <table class="modern-table">
                                <thead style="background:#1c4789;color:#fff;">
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Estudiante</th>
                                        <th class="text-center">Promedio</th>
                                        <th class="text-center">Materias</th>
                                        <th class="text-center">Atrasos</th>
                                        <th class="text-center">Faltas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mejores as $i => $m)
                                        @php
                                            $bg = $i === 0 ? '#fff3cd' : ($i === 1 ? '#e7f1ff' : ($i === 2 ? '#e6ffe6' : ''));
                                            $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ($i + 1).'°'));
                                        @endphp
                                        <tr style="background:{{ $bg }};">
                                            <td class="text-center font-weight-bold" style="font-size:1.1rem;">{{ $medal }}</td>
                                            <td><strong>{{ $m->nombre }}</strong></td>
                                            <td class="text-center">
                                                <span class="modern-badge badge-success-modern" style="font-size:1rem;">{{ number_format($m->promedio, 2) }}</span>
                                            </td>
                                            <td class="text-center">{{ $m->materias_total }}</td>
                                            <td class="text-center">{{ $m->atrasos }}</td>
                                            <td class="text-center">{{ $m->faltas }}</td>
                                            <td>
                                                <a href="{{ route('concejo.documento', $m->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-danger" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Documento
                                                </a>
                                                <a href="{{ route('concejo.detalle', $m->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-info" target="_blank" title="Ver detalle">
                                                    <i class="fas fa-search-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning"><i class="fas fa-info-circle mr-2"></i>Aún no hay notas registradas para este curso.</div>
                    @endif
                @endif

                {{-- ─── TAB EN RIESGO ─── --}}
                @if($tab === 'riesgo')
                    @if($enRiesgo->count())
                        <p class="text-muted small mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Estudiantes con al menos uno de: promedio &lt; 51, materias reprobadas, ≥5 faltas o atrasos, o compromisos escritos.
                            Ordenados por gravedad (score de riesgo descendente).
                        </p>
                        <div class="table-responsive-modern">
                            <table class="modern-table">
                                <thead style="background:#c0392b;color:#fff;">
                                    <tr>
                                        <th>#</th>
                                        <th>Estudiante</th>
                                        <th class="text-center">Promedio</th>
                                        <th class="text-center" title="Materias reprobadas / total">Reprob.</th>
                                        <th class="text-center">Faltas</th>
                                        <th class="text-center">Atrasos</th>
                                        <th class="text-center" title="Compromisos verbales">Comp.V</th>
                                        <th class="text-center" title="Compromisos escritos">Comp.E</th>
                                        <th class="text-center" title="Score de riesgo (mayor = más crítico)">Riesgo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enRiesgo as $i => $r)
                                        @php
                                            $promRojo = $r->promedio !== null && $r->promedio < 51;
                                            $rowBg = $r->riesgo_score >= 60 ? '#ffe6e6' : ($r->riesgo_score >= 30 ? '#fff3cd' : '');
                                        @endphp
                                        <tr style="background:{{ $rowBg }};">
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td><strong>{{ $r->nombre }}</strong></td>
                                            <td class="text-center">
                                                <span class="modern-badge {{ $promRojo ? 'badge-danger-modern' : 'badge-warning-modern' }}">
                                                    {{ $r->promedio !== null ? number_format($r->promedio, 2) : '—' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span style="color:{{ $r->materias_reprobadas > 0 ? '#c0392b' : '' }};font-weight:{{ $r->materias_reprobadas > 0 ? '700' : '400' }};">
                                                    {{ $r->materias_reprobadas }}/{{ $r->materias_total }}
                                                </span>
                                            </td>
                                            <td class="text-center" style="color:{{ $r->faltas >= 5 ? '#c0392b' : '' }};">{{ $r->faltas }}</td>
                                            <td class="text-center" style="color:{{ $r->atrasos >= 5 ? '#c0392b' : '' }};">{{ $r->atrasos }}</td>
                                            <td class="text-center">{{ $r->comp_verbales }}</td>
                                            <td class="text-center" style="color:{{ $r->comp_escritos > 0 ? '#c0392b' : '' }};font-weight:{{ $r->comp_escritos > 0 ? '700' : '400' }};">{{ $r->comp_escritos }}</td>
                                            <td class="text-center">
                                                <span class="badge" style="background:{{ $r->riesgo_score >= 60 ? '#c0392b' : ($r->riesgo_score >= 30 ? '#f39c12' : '#6c757d') }};color:#fff;font-size:0.85rem;">
                                                    {{ $r->riesgo_score }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('concejo.documento', $r->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-danger" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Documento
                                                </a>
                                                <a href="{{ route('concejo.detalle', $r->est_codigo) }}?gestion={{ $gestion }}" class="btn btn-sm btn-info" target="_blank" title="Ver detalle">
                                                    <i class="fas fa-search-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Ningún estudiante en situación de riesgo en este curso.</div>
                    @endif
                @endif
            @else
                <div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>Seleccione un curso para ver los estudiantes y métricas.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>$(document).ready(function(){ $('.select2-curso').select2({theme:'bootstrap4',width:'100%'}); });</script>
@endsection
