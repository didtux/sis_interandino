@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-history mr-2"></i>Historial de Asistencia</h4>
                </div>
                <div class="card-body">
                    @include('chofer-portal._selector-ruta')

                    <form method="GET" class="form-inline mb-3" style="gap:8px;">
                        <input type="hidden" name="ruta_codigo" value="{{ $rutaSeleccionada }}">
                        <label class="mr-1">Desde:</label>
                        <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control form-control-sm">
                        <label class="mx-1">Hasta:</label>
                        <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control form-control-sm">
                        <button class="btn btn-primary btn-sm ml-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
                    </form>

                    @if($registros->count() > 0)
                    @php $porFecha = $registros->groupBy(fn($r) => $r->tasis_fecha->format('Y-m-d')); @endphp
                    @foreach($porFecha as $fecha => $regs)
                        <h6 class="mt-3 mb-2" style="color:#667eea;">
                            <i class="fas fa-calendar-day mr-1"></i>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y — l') }}
                            <span class="badge badge-secondary">{{ $regs->count() }}</span>
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-2" style="font-size:0.83rem;">
                                <thead style="background:#f8f9fa;"><tr><th>Estudiante</th><th>Curso</th><th>Tipo</th><th>Hora</th></tr></thead>
                                <tbody>
                                    @foreach($regs as $r)
                                    <tr>
                                        <td>{{ $r->estudiante->est_apellidos ?? '' }} {{ $r->estudiante->est_nombres ?? '' }}</td>
                                        <td><span class="badge badge-primary">{{ $r->estudiante->curso->cur_nombre ?? '' }}</span></td>
                                        <td>
                                            <span class="badge badge-{{ $r->tasis_tipo === 'IDA' ? 'success' : 'warning' }}">{{ $r->tasis_tipo }}</span>
                                        </td>
                                        <td>{{ $r->tasis_hora }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-history fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay registros en el rango seleccionado.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
