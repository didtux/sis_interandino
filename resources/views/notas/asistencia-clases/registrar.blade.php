@extends('layouts.app')

@section('content')
<style>
    .estado-btn { width:42px;height:42px;border-radius:50%;font-weight:bold;font-size:14px;border:2px solid transparent;transition:all .2s;cursor:pointer; }
    .estado-btn:focus { box-shadow:0 0 0 3px rgba(0,123,255,.4); }
    .estado-btn.active-P { background:#28a745;color:#fff;border-color:#1e7e34; }
    .estado-btn.active-A { background:#ffc107;color:#000;border-color:#d39e00; }
    .estado-btn.active-F { background:#dc3545;color:#fff;border-color:#bd2130; }
    .estado-btn.active-L { background:#17a2b8;color:#fff;border-color:#117a8b; }
    .estado-btn:not([class*="active-"]) { background:#f8f9fa;color:#6c757d; }
    .estado-btn[disabled] { opacity:.5;cursor:not-allowed; }
    .fila-estudiante:hover { background:#f0f7ff !important; }
    .fecha-chip { display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;margin:3px;cursor:pointer;border:1px solid #dee2e6;text-decoration:none;transition:all .15s; }
    .fecha-chip:hover { background:#007bff;color:#fff;border-color:#007bff;text-decoration:none; }
    .fecha-chip.active { background:#007bff;color:#fff;border-color:#007bff; }
    .fecha-chip.es-hoy { border-color:#dc3545;color:#dc3545;font-weight:bold; }
    .fecha-chip.es-hoy.active { background:#dc3545;color:#fff; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h4 class="mb-1"><i class="fas fa-clipboard-check mr-2"></i>Registrar Asistencia</h4>
                            <span class="modern-badge badge-primary-modern">{{ $asignacion->curso->cur_nombre }}</span>
                            <span class="modern-badge badge-warning-modern">{{ $asignacion->materia->mat_nombre }}</span>
                        </div>
                        <div class="col-md-5">
                            {{-- Selector de fecha --}}
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <form method="GET" action="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" class="d-flex align-items-center" style="gap:8px;" id="formFecha">
                                    <label class="mb-0 font-weight-bold text-nowrap">Fecha:</label>
                                    <input type="date" name="fecha" id="inputFecha" class="form-control form-control-sm" style="width:170px;"
                                        value="{{ $fecha }}"
                                        min="{{ $periodo->periodo_fecha_inicio->format('Y-m-d') }}"
                                        max="{{ $periodo->periodo_fecha_fin->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="irAHoy()" title="Ir a hoy"><i class="fas fa-calendar-day"></i></button>
                                </form>
                            </div>
                            <div class="mt-1">
                                @if($yaRegistrada)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Editando registro existente</span>
                                @else
                                    <span class="badge badge-info"><i class="fas fa-plus mr-1"></i>Nuevo registro</span>
                                @endif
                                <small class="text-muted ml-1">{{ $periodo->periodo_nombre }}</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-right">
                            <a href="{{ route('asistencia-clases.vista-general', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-th mr-1"></i>Vista General</a>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
            @endif

            {{-- Alertas de permisos --}}
            @if(!$puedeEditar && $esDocente && $enRango)
                <div class="alert alert-warning py-2"><i class="fas fa-lock mr-2"></i>Este registro ya existe y solo puede editarse el <strong>día actual ({{ now()->format('d/m/Y') }})</strong>.</div>
            @elseif(!$enRango)
                <div class="alert alert-warning py-2"><i class="fas fa-lock mr-2"></i>Fecha fuera del rango del periodo ({{ $periodo->periodo_fecha_inicio->format('d/m/Y') }} - {{ $periodo->periodo_fecha_fin->format('d/m/Y') }}).</div>
            @endif

            <div class="row">
                {{-- Tabla principal --}}
                <div class="col-lg-9">
                    <div class="card modern-card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-day mr-1"></i>
                                {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM YYYY') }}
                                @if($fecha == now()->toDateString())
                                    <span class="badge badge-danger ml-1">HOY</span>
                                @endif
                            </h5>
                            @if($puedeEditar)
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="marcarTodos('P')"><i class="fas fa-check mr-1"></i>Todos P</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="marcarTodos('A')">Todos A</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="marcarTodos('F')">Todos F</button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <form action="{{ route('asistencia-clases.guardar') }}" method="POST" id="formAsistencia">
                                @csrf
                                <input type="hidden" name="curmatdoc_id" value="{{ $asignacion->curmatdoc_id }}">
                                <input type="hidden" name="periodo_id" value="{{ $periodo->periodo_id }}">
                                <input type="hidden" name="fecha" value="{{ $fecha }}">

                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">N°</th>
                                            <th>Estudiante</th>
                                            <th class="text-center" style="width:220px">
                                                <span class="badge badge-success">P</span>
                                                <span class="badge badge-warning">A</span>
                                                <span class="badge badge-danger">F</span>
                                                <span class="badge badge-info">L</span>
                                            </th>
                                            <th style="width:200px">Observación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($estudiantes as $i => $est)
                                            @php $asis = $asistencias[$est->est_codigo] ?? null; $estado = $asis->asiscl_estado ?? 'P'; @endphp
                                            <tr class="fila-estudiante">
                                                <td class="text-center font-weight-bold">{{ $est->lista_numero ?? ($i + 1) }}</td>
                                                <td>{{ $est->est_apellidos }} {{ $est->est_nombres }}</td>
                                                <td class="text-center">
                                                    <input type="hidden" name="estados[{{ $est->est_codigo }}]" value="{{ $estado }}" class="input-estado" data-est="{{ $est->est_codigo }}">
                                                    @foreach(['P','A','F','L'] as $key)
                                                        <button type="button" class="estado-btn {{ $estado == $key ? 'active-'.$key : '' }}" data-est="{{ $est->est_codigo }}" data-val="{{ $key }}" {{ !$puedeEditar ? 'disabled' : '' }}>{{ $key }}</button>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <input type="text" name="obs[{{ $est->est_codigo }}]" class="form-control form-control-sm" value="{{ $asis->asiscl_observacion ?? '' }}" placeholder="..." {{ !$puedeEditar ? 'readonly' : '' }}>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted py-4">No hay estudiantes en este curso</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                @if($puedeEditar && $estudiantes->count())
                                    <div class="card-footer text-right">
                                        <a href="{{ route('asistencia-clases.vista-general', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i>Cancelar</a>
                                        <button type="submit" class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar Asistencia</button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Panel lateral --}}
                <div class="col-lg-3">
                    {{-- Fechas registradas --}}
                    <div class="card modern-card mb-3">
                        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-calendar-check mr-1"></i>Fechas Registradas <span class="badge badge-secondary">{{ $fechasRegistradas->count() }}</span></h6></div>
                        <div class="card-body py-2" style="max-height:300px;overflow-y:auto;">
                            @forelse($fechasRegistradas as $f)
                                @php $esHoy = $f == now()->toDateString(); @endphp
                                <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id, 'fecha' => $f]) }}"
                                   class="fecha-chip {{ $f == $fecha ? 'active' : '' }} {{ $esHoy ? 'es-hoy' : '' }}">
                                    {{ \Carbon\Carbon::parse($f)->format('d/m') }}
                                    @if($esHoy) <small>HOY</small> @endif
                                </a>
                            @empty
                                <small class="text-muted">Sin registros aún</small>
                            @endforelse
                        </div>
                    </div>

                    {{-- Leyenda --}}
                    <div class="card modern-card mb-3">
                        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-info-circle mr-1"></i>Leyenda</h6></div>
                        <div class="card-body py-2" style="font-size:13px;">
                            <div class="mb-1"><span class="badge badge-success">P</span> Presente</div>
                            <div class="mb-1"><span class="badge badge-warning">A</span> Atraso</div>
                            <div class="mb-1"><span class="badge badge-danger">F</span> Falta</div>
                            <div class="mb-1"><span class="badge badge-info">L</span> Licencia</div>
                            <hr class="my-2">
                            <small class="text-muted">
                                @if($esDocente)
                                    <i class="fas fa-info-circle mr-1"></i>Solo puede editar el día actual. Fechas anteriores son de solo lectura.
                                @else
                                    <i class="fas fa-info-circle mr-1"></i>Como administrador puede editar cualquier fecha dentro del periodo.
                                @endif
                            </small>
                        </div>
                    </div>

                    {{-- Acceso rápido --}}
                    <div class="card modern-card">
                        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-bolt mr-1"></i>Acceso Rápido</h6></div>
                        <div class="card-body py-2">
                            <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id, 'fecha' => now()->toDateString()]) }}"
                               class="btn btn-danger btn-sm btn-block mb-2">
                                <i class="fas fa-calendar-day mr-1"></i>Hoy ({{ now()->format('d/m') }})
                            </a>
                            @php
                                $ayer = now()->subDay()->toDateString();
                                $ayerEnRango = $ayer >= $periodo->periodo_fecha_inicio->toDateString() && $ayer <= $periodo->periodo_fecha_fin->toDateString();
                            @endphp
                            @if($ayerEnRango)
                                <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id, 'fecha' => $ayer]) }}"
                                   class="btn btn-outline-secondary btn-sm btn-block mb-2">
                                    <i class="fas fa-history mr-1"></i>Ayer ({{ now()->subDay()->format('d/m') }})
                                </a>
                            @endif
                            <a href="{{ route('asistencia-clases.vista-general', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}"
                               class="btn btn-outline-primary btn-sm btn-block">
                                <i class="fas fa-th mr-1"></i>Vista General
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$('.estado-btn:not([disabled])').on('click', function() {
    var est = $(this).data('est'), val = $(this).data('val');
    $(this).siblings('.estado-btn').removeClass('active-P active-A active-F active-L');
    $(this).addClass('active-' + val);
    $('input.input-estado[data-est="' + est + '"]').val(val);
});

function marcarTodos(estado) {
    $('.estado-btn[data-val="' + estado + '"]:not([disabled])').each(function() { $(this).click(); });
}

function irAHoy() {
    var hoy = '{{ now()->toDateString() }}';
    $('#inputFecha').val(hoy);
    $('#formFecha').submit();
}
</script>
@endsection
