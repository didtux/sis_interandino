@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h4 class="mb-0">
                <i class="fas fa-search-plus mr-2"></i>Detalle de Asistencia / Permisos
            </h4>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Estudiante:</strong> {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</p>
                    <p class="mb-1"><strong>Curso:</strong> {{ optional($estudiante->curso)->cur_nombre ?? '-' }}</p>
                    <p class="mb-1"><strong>CI:</strong> {{ $estudiante->est_ci ?? '-' }}</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-1"><strong>Gestión:</strong> {{ $gestion }}</p>
                    <p class="mb-1 text-muted small">
                        <i class="fas fa-info-circle"></i>
                        Las faltas se calculan desde <strong>colegio_asistencia</strong> (no del registro de docentes).
                        Los días con permiso vigente <strong>no</strong> cuentan como falta.
                    </p>
                </div>
            </div>

            {{-- Totales --}}
            <div class="row text-center mb-3">
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Días trabajados</div><h4 class="mb-0">{{ $totales['dias_trabajados'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded bg-light"><div class="text-muted small">Presencias</div><h4 class="mb-0 text-success">{{ $totales['presencias'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Faltas</div><h4 class="mb-0 text-danger">{{ $totales['faltas'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Atrasos</div><h4 class="mb-0 text-warning">{{ $totales['atrasos'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Permisos (solicitudes)</div><h4 class="mb-0">{{ $totales['permisos_solicitudes'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Días con permiso</div><h4 class="mb-0 text-info">{{ $totales['permisos_dias'] }}</h4></div></div>
            </div>

            {{-- Por periodo --}}
            @foreach($detallePeriodos as $d)
                @php $p = $d['periodo']; @endphp
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <strong>{{ $p->periodo_nombre ?? 'Trimestre '.$p->periodo_numero }}</strong>
                        <small class="ml-2">{{ $p->periodo_fecha_inicio->format('d/m/Y') }} — {{ $p->periodo_fecha_fin->format('d/m/Y') }}</small>
                        <span class="float-right">
                            <span class="badge badge-light">DT: {{ $d['dias_trabajados']->count() }}</span>
                            <span class="badge badge-success">Pres: {{ $d['presencias']->count() }}</span>
                            <span class="badge badge-danger">Faltas: {{ $d['faltas']->count() }}</span>
                            <span class="badge badge-warning">Atrasos: {{ $d['atrasos']->count() }}</span>
                            <span class="badge badge-info">Permisos: {{ $d['dias_con_permiso']->count() }}d / {{ $d['permisos']->count() }}sol.</span>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Faltas --}}
                            <div class="col-md-4">
                                <h6 class="text-danger"><i class="fas fa-times-circle"></i> Faltas ({{ $d['faltas']->count() }})</h6>
                                @if($d['faltas']->count())
                                    <ul class="list-unstyled small" style="max-height:200px;overflow-y:auto;">
                                        @foreach($d['faltas'] as $f)
                                            <li>· {{ \Carbon\Carbon::parse($f)->format('d/m/Y (D)') }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small">Sin faltas en el periodo.</p>
                                @endif
                            </div>

                            {{-- Atrasos --}}
                            <div class="col-md-4">
                                <h6 class="text-warning"><i class="fas fa-clock"></i> Atrasos ({{ $d['atrasos']->count() }})</h6>
                                @if($d['atrasos']->count())
                                    <ul class="list-unstyled small" style="max-height:200px;overflow-y:auto;">
                                        @foreach($d['atrasos'] as $a)
                                            <li>· {{ \Carbon\Carbon::parse($a->atraso_fecha)->format('d/m/Y') }} @if(!empty($a->atraso_hora)) — {{ substr($a->atraso_hora,0,5) }}@endif</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small">Sin atrasos en el periodo.</p>
                                @endif
                            </div>

                            {{-- Permisos --}}
                            <div class="col-md-4">
                                <h6 class="text-info"><i class="fas fa-file-signature"></i> Permisos / Licencias ({{ $d['permisos']->count() }})</h6>
                                @if($d['permisos']->count())
                                    <ul class="list-unstyled small" style="max-height:200px;overflow-y:auto;">
                                        @foreach($d['permisos'] as $p2)
                                            <li class="mb-1">
                                                <span class="badge badge-{{ $p2->permiso_tipo == 'LICENCIA' ? 'info' : 'secondary' }}">{{ $p2->permiso_tipo }}</span>
                                                <strong>{{ \Carbon\Carbon::parse($p2->permiso_fecha_inicio)->format('d/m/Y') }}</strong>
                                                @if($p2->permiso_fecha_inicio !== $p2->permiso_fecha_fin)
                                                    → <strong>{{ \Carbon\Carbon::parse($p2->permiso_fecha_fin)->format('d/m/Y') }}</strong>
                                                @endif
                                                <br><span class="text-muted">{{ $p2->permiso_motivo }} <em>({{ $p2->permiso_origen ?? 'PERSONAL' }})</em></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="small text-muted mt-2 mb-0">Días efectivos del periodo cubiertos por permiso: <strong>{{ $d['dias_con_permiso']->count() }}</strong></p>
                                @else
                                    <p class="text-muted small">Sin permisos en el periodo.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="alert alert-light border small mt-3">
                <strong>Cálculo:</strong>
                <ul class="mb-0">
                    <li><strong>Faltas</strong> = días trabajados del curso donde el estudiante NO tiene presencia en <code>colegio_asistencia</code> y NO tiene permiso vigente.</li>
                    <li><strong>Días con permiso</strong> = días hábiles del periodo cubiertos por al menos un permiso aprobado (no se cuentan como falta).</li>
                    <li><strong>Solicitudes de permiso</strong> = cantidad de registros de permiso/licencia que se traslapan con el periodo.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
