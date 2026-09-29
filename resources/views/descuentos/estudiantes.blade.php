@extends('layouts.app')

{{--
    Listado de ESTUDIANTES con descuento. La pestaña Descuentos es el catálogo
    de tipos (Hermanos, Docente…), no la lista de quiénes lo tienen: para saber
    a qué alumnos se les aplicó había que ir a Inscripciones y filtrar de a uno.
--}}

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="mb-0"><i class="fas fa-percent mr-2"></i>Estudiantes con descuento</h4>
                    <div>
                        <a href="{{ route('descuentos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-tags mr-1"></i>Catálogo de descuentos
                        </a>
                        <a href="{{ route('descuentos.estudiantes', array_merge(request()->query(), ['formato' => 'pdf'])) }}"
                           class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <label class="small mb-1">Gestión</label>
                                <select name="gestion" class="form-control">
                                    @foreach($gestiones as $g)
                                        <option value="{{ $g }}" {{ (string) $gestion === (string) $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                    <option value="todas" {{ $gestion === 'todas' ? 'selected' : '' }}>Todas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small mb-1">Tipo de descuento</label>
                                <select name="desc_id" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($catalogo as $d)
                                        <option value="{{ $d->desc_id }}" {{ request('desc_id') == $d->desc_id ? 'selected' : '' }}>
                                            {{ $d->desc_nombre }} ({{ $d->desc_porcentaje }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small mb-1">Curso</label>
                                <select name="cur_codigo" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}" {{ request('cur_codigo') == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="small mb-1">Buscar</label>
                                <input type="text" name="buscar" class="form-control" value="{{ request('buscar') }}" placeholder="Nombre o CI">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                                <a href="{{ route('descuentos.estudiantes') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-info py-2">
                        <strong>{{ $inscripciones->count() }}</strong> estudiante(s) con descuento
                        @if($gestion !== 'todas') en la gestión {{ $gestion }} @endif
                        &nbsp;|&nbsp; Total descontado: <strong>Bs. {{ number_format($totalDescuento, 2) }}</strong>
                        <br><small>No se cuentan las inscripciones anuladas.</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Estudiante</th>
                                    <th>CI</th>
                                    <th>Curso</th>
                                    <th>Padre/Tutor</th>
                                    <th>Descuento</th>
                                    <th class="text-right">Monto anual</th>
                                    <th class="text-right">Descontado</th>
                                    <th class="text-right">Monto final</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inscripciones as $i => $insc)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $insc->estudiante->est_apellidos ?? '' }} {{ $insc->estudiante->est_nombres ?? '' }}</td>
                                        <td>{{ $insc->estudiante->est_ci ?? '' }}</td>
                                        <td><span class="badge badge-info">{{ $insc->estudiante->curso->cur_nombre ?? $insc->cur_codigo }}</span></td>
                                        <td>{{ $insc->padreFamilia->pfam_nombres ?? '' }}</td>
                                        <td>
                                            @foreach($insc->descuentos as $d)
                                                <span class="badge badge-warning">{{ $d->desc_nombre }} {{ $d->desc_porcentaje }}%</span>
                                            @endforeach
                                        </td>
                                        <td class="text-right">{{ number_format($insc->insc_monto_total, 2) }}</td>
                                        <td class="text-right text-danger">{{ number_format($insc->insc_monto_descuento, 2) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($insc->insc_monto_final, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">No hay estudiantes con descuento para este filtro.</td></tr>
                                @endforelse
                            </tbody>
                            @if($inscripciones->count())
                                <tfoot>
                                    <tr class="table-info">
                                        <td colspan="7" class="text-right"><strong>TOTAL DESCONTADO</strong></td>
                                        <td class="text-right"><strong>{{ number_format($totalDescuento, 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() { $('.select2').select2({ width: '100%' }); });
</script>
@endsection
