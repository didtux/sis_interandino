@extends('layouts.app')

@section('content')
@php
    use App\Models\Preinscripcion;
    [$et, $ec] = Preinscripcion::ESTADOS[$pre->preinsc_estado] ?? [$pre->preinsc_estado, 'light'];
    $vigente = in_array($pre->preinsc_estado, ['RESERVADA', 'CONFIRMADA', 'AJUSTAR']);
@endphp
<div class="section-body">
    @if(session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>{{ session('success') }}</span>
            @if(session('abrir_recibo'))
                <span>
                    <a href="{{ session('abrir_recibo') }}" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-print mr-1"></i>Recibo</a>
                    <a href="{{ session('abrir_recibo') }}?formato=termica" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-receipt mr-1"></i>Térmico</a>
                </span>
            @endif
        </div>
    @endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        <div class="col-lg-7">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Pre-inscripción {{ $pre->preinsc_codigo }}</h4>
                    <span class="badge badge-{{ $ec }} p-2">{{ $et }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Estudiante</dt><dd class="col-sm-8">{{ $pre->nombre_completo }} <span class="badge badge-light">{{ $pre->preinsc_tipo }}</span></dd>
                        @if($pre->preinsc_tipo === 'NUEVO')
                            <dt class="col-sm-4">CI / nacimiento</dt><dd class="col-sm-8">{{ $pre->pre_ci ?: '—' }} · {{ optional($pre->pre_fecha_nac)->format('d/m/Y') ?: '—' }}</dd>
                            <dt class="col-sm-4">Procedencia</dt><dd class="col-sm-8">{{ $pre->pre_ue_procedencia ?: '—' }}</dd>
                        @endif
                        <dt class="col-sm-4">Padre / tutor</dt><dd class="col-sm-8">{{ optional($pre->padre)->pfam_nombres ?: '—' }}</dd>
                        <dt class="col-sm-4">Curso actual</dt><dd class="col-sm-8">{{ optional($pre->cursoOrigen)->cur_nombre ?: '—' }}</dd>
                        <dt class="col-sm-4">Curso {{ $pre->ges_anio }}</dt><dd class="col-sm-8"><strong>{{ optional($pre->cursoDestino)->cur_nombre }}</strong></dd>
                        <dt class="col-sm-4">Tarifa {{ $pre->ges_anio }}</dt><dd class="col-sm-8">{{ $tarifa ? 'Bs ' . number_format($tarifa->tar_monto_anual, 2) : 'sin tarifa cargada' }}</dd>
                        <dt class="col-sm-4">Descuento propuesto</dt><dd class="col-sm-8">{{ optional($pre->descuento)->desc_nombre ?: '—' }} {{ $pre->monto_desc_ref ? '(Bs ' . number_format($pre->monto_desc_ref, 2) . ')' : '' }}</dd>
                        @if($pre->insc_codigo)<dt class="col-sm-4">Inscripción</dt><dd class="col-sm-8">{{ $pre->insc_codigo }}</dd>@endif
                        @if($pre->preinsc_condicional && $vigente)
                            <dt class="col-sm-4">Condición</dt><dd class="col-sm-8">Sujeta al resultado de la gestión {{ $pre->ges_anio - 1 }}</dd>
                        @endif
                        @if($pre->preinsc_obs)<dt class="col-sm-4">Observaciones</dt><dd class="col-sm-8" style="white-space:pre-line;">{{ $pre->preinsc_obs }}</dd>@endif
                    </dl>
                </div>
            </div>

            <div class="card modern-card">
                <div class="card-header"><h4 class="mb-0">Pagos de reserva</h4></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Recibo</th><th>Fecha</th><th>Forma</th><th class="text-right">Monto</th><th></th></tr></thead>
                        <tbody>
                            @foreach($pre->pagos as $pp)
                                <tr class="{{ $pp->prepago_estado ? '' : 'text-muted' }}">
                                    <td>{{ $pp->prepago_recibo }} @if(!$pp->prepago_estado)<span class="badge badge-secondary">ANULADO</span>@endif</td>
                                    <td>{{ $pp->prepago_fecha->format('d/m/Y H:i') }}</td>
                                    <td>{{ $pp->prepago_metodo }}</td>
                                    <td class="text-right">{{ number_format($pp->prepago_monto, 2) }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('recibos.mostrar', ['reserva', $pp->prepago_recibo]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Media carta"><i class="fas fa-file-pdf"></i></a>
                                        <a href="{{ route('recibos.mostrar', ['reserva', $pp->prepago_recibo]) }}?formato=termica" target="_blank" class="btn btn-sm btn-outline-secondary" title="Térmico"><i class="fas fa-receipt"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @foreach($devoluciones as $d)
                        <div class="small">Devolución {{ $d->dev_codigo ?: '(sin recibo aún)' }} — {{ \App\Models\Devolucion::ESTADOS[$d->dev_estado][0] }} — Bs {{ number_format($d->monto_devuelto, 2) }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($vigente)
            <div class="card modern-card">
                <div class="card-header"><h4 class="mb-0">Acciones</h4></div>
                <div class="card-body">
                    @if($pre->preinsc_estado === 'AJUSTAR')
                        <div class="alert alert-warning small">El estudiante reprobó: el destino pasó al mismo grado. Confirme con la familia si sigue (elija el curso) o si pide la devolución.</div>
                    @endif
                    @puede('preinscripciones', 'editar')
                    <form method="POST" action="{{ route('preinscripciones.destino', $pre->preinsc_id) }}" class="mb-3">
                        @csrf @method('PUT')
                        <label class="small font-weight-bold">Cambiar curso {{ $pre->ges_anio }}</label>
                        <div class="input-group">
                            <select name="cur_destino" class="form-control form-control-sm js-select2">
                                @foreach($cursos as $c)
                                    <option value="{{ $c->cur_codigo }}" {{ $pre->cur_destino === $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-append"><button class="btn btn-sm btn-outline-primary">Guardar</button></div>
                        </div>
                    </form>
                    @endpuede

                    @if($pre->preinsc_tipo === 'ANTIGUO')
                        @puede('preinscripciones', 'crear')
                        <form method="POST" action="{{ route('preinscripciones.convertir', $pre->preinsc_id) }}" class="mb-3"
                              onsubmit="return confirm('Se creará la inscripción {{ $pre->ges_anio }} con la tarifa del curso. ¿Continuar?')">
                            @csrf
                            <button class="btn btn-success btn-block"><i class="fas fa-user-check mr-1"></i>Inscribir para {{ $pre->ges_anio }}</button>
                            @if($pre->preinsc_estado === 'RESERVADA')<small class="text-muted">Todavía no tiene resultado de fin de año: inscríbalo sólo si ya está decidido.</small>@endif
                        </form>
                        @endpuede
                    @else
                        <div class="alert alert-light border small">Alumno nuevo: se inscribe automáticamente al activar la gestión {{ $pre->ges_anio }}.</div>
                    @endif

                    @puede('devoluciones', 'crear')
                        <a href="{{ route('devoluciones.create', ['preinsc' => $pre->preinsc_id]) }}" class="btn btn-outline-warning btn-block mb-3"><i class="fas fa-undo-alt mr-1"></i>Solicitar devolución</a>
                    @endpuede

                    @puede('preinscripciones', 'anular')
                    <form method="POST" action="{{ route('preinscripciones.anular', $pre->preinsc_id) }}"
                          onsubmit="var m = prompt('Motivo de la anulación (error de caja):'); if (!m) return false; this.motivo.value = m; return true;">
                        @csrf <input type="hidden" name="motivo">
                        <button class="btn btn-outline-danger btn-block btn-sm"><i class="fas fa-ban mr-1"></i>Anular (error de caja)</button>
                        <small class="text-muted">Para devolver dinero use "Solicitar devolución": anular es sólo para corregir un cobro mal hecho.</small>
                    </form>
                    @endpuede
                </div>
            </div>
            @endif
            <a href="{{ route('preinscripciones.index', ['gestion' => $pre->ges_anio]) }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(session('abrir_recibo'))
<script>window.open(@json(session('abrir_recibo')), '_blank');</script>
@endif
@endsection
