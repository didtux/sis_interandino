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

            {{-- Totales (mismo modelo que boletín/centralizador: DT = Pres+Lic, TOT = días hábiles calendario) --}}
            @php
                $totDT  = ($totales['presencias'] ?? 0) + ($totales['permisos_dias'] ?? 0);
                $totTOT = $totales['dias_habiles_calendario'] ?? 0;
            @endphp
            <div class="row text-center mb-3">
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">ATR</div><h4 class="mb-0 text-warning">{{ $totales['atrasos'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">TL (Licencias)</div><h4 class="mb-0 text-info">{{ $totales['permisos_dias'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">TF (Faltas)</div><h4 class="mb-0 text-danger">{{ $totales['faltas'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded bg-light"><div class="text-muted small">DT (Pres+Lic)</div><h4 class="mb-0 text-success">{{ $totDT }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">TOT Días Hábiles</div><h4 class="mb-0">{{ $totTOT }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Presencias</div><h4 class="mb-0 text-success">{{ $totales['presencias'] }}</h4></div></div>
                <div class="col"><div class="p-2 border rounded"><div class="text-muted small">Solicitudes permiso</div><h4 class="mb-0">{{ $totales['permisos_solicitudes'] }}</h4></div></div>
            </div>
            <p class="small text-muted mb-3"><i class="fas fa-info-circle"></i> <b>DT + TF = TOT</b>. Atrasos cuentan como asistencia (incluidos en DT).</p>

            {{-- Por periodo --}}
            @foreach($detallePeriodos as $d)
                @php
                    $p   = $d['periodo'];
                    $dt  = $d['presencias']->count() + $d['dias_con_permiso']->count();
                    $tot = $d['total_calendario'] ?? ($dt + $d['faltas']->count());
                @endphp
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <strong>{{ $p->periodo_nombre ?? 'Trimestre '.$p->periodo_numero }}</strong>
                        <small class="ml-2">{{ $p->periodo_fecha_inicio->format('d/m/Y') }} — {{ $p->periodo_fecha_fin->format('d/m/Y') }}</small>
                        <span class="float-right">
                            <span class="badge badge-warning">ATR: {{ $d['atrasos']->count() }}</span>
                            <span class="badge badge-info">TL: {{ $d['dias_con_permiso']->count() }}</span>
                            <span class="badge badge-danger">TF: {{ $d['faltas']->count() }}</span>
                            <span class="badge badge-success">DT: {{ $dt }}</span>
                            <span class="badge badge-light">TOT: {{ $tot }}</span>
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
                                    <ul class="list-unstyled small" style="max-height:340px;overflow-y:auto;">
                                        @foreach($d['permisos'] as $p2)
                                            <li class="mb-2 pb-2 border-bottom">
                                                <span class="badge badge-{{ $p2->permiso_tipo == 'LICENCIA' ? 'info' : 'secondary' }}">{{ $p2->permiso_tipo }}</span>
                                                <strong>{{ \Carbon\Carbon::parse($p2->permiso_fecha_inicio)->format('d/m/Y') }}</strong>
                                                @if($p2->permiso_fecha_inicio !== $p2->permiso_fecha_fin)
                                                    → <strong>{{ \Carbon\Carbon::parse($p2->permiso_fecha_fin)->format('d/m/Y') }}</strong>
                                                @endif
                                                <br><span class="text-muted">{{ $p2->permiso_motivo }} <em>({{ $p2->permiso_origen ?? 'PERSONAL' }})</em></span>
                                                @if(!empty($p2->desglose))
                                                    <div class="mt-1" style="font-size:11px;">
                                                        @foreach($p2->desglose as $dg)
                                                            @php
                                                                $badgeCls = ['cuenta'=>'badge-success','asistio'=>'badge-success','fin_semana'=>'badge-light border','sin_clases'=>'badge-light border','no_cuenta'=>'badge-light border'][$dg['estado']] ?? 'badge-light border';
                                                                $icon = ['cuenta'=>'fa-check','asistio'=>'fa-user-check','fin_semana'=>'fa-calendar-week','sin_clases'=>'fa-calendar-times','no_cuenta'=>'fa-minus'][$dg['estado']] ?? 'fa-minus';
                                                            @endphp
                                                            <div class="d-flex justify-content-between">
                                                                <span><i class="fas {{ $icon }} mr-1"></i>{{ \Carbon\Carbon::parse($dg['fecha'])->format('d/m/Y') }} <small class="text-muted">({{ $dg['dow'] }})</small></span>
                                                                <span class="badge {{ $badgeCls }}" title="{{ $dg['label'] }}">{{ $dg['label'] }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
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
