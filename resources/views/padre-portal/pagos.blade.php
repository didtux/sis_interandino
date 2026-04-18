@extends('layouts.app')

@section('content')
<style>
    .mes-badge { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; font-size: 0.75rem; font-weight: 700; }
    .mes-badge.pagado { background: #d4edda; color: #155724; }
    .mes-badge.pendiente { background: #f8d7da; color: #721c24; }
    .mes-badge.futuro { background: #e9ecef; color: #6c757d; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-money-bill-wave mr-2"></i>Pagos</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado)

                    {{-- Estado de mensualidades visual --}}
                    <h6 class="font-weight-bold mb-2"><i class="fas fa-calendar-alt mr-1 text-primary"></i>Estado de Mensualidades {{ date('Y') }}</h6>
                    @php
                        $mesesNombres = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                        $mesActual = (int)date('m');
                        $mesesPagados = [];
                        foreach($mensualidades as $p) {
                            foreach($p->meses_cubiertos as $m) $mesesPagados[] = $m;
                        }
                        $mesesPagados = array_unique($mesesPagados);
                    @endphp
                    <div class="d-flex flex-wrap mb-4" style="gap:8px;">
                        @for($m = 2; $m <= 11; $m++)
                            @php
                                $estado = in_array($m, $mesesPagados) ? 'pagado' : ($m <= $mesActual ? 'pendiente' : 'futuro');
                            @endphp
                            <div class="text-center">
                                <div class="mes-badge {{ $estado }}">{{ $m }}</div>
                                <div style="font-size:0.65rem;color:#6c757d;">{{ $mesesNombres[$m] }}</div>
                            </div>
                        @endfor
                    </div>

                    {{-- Inscripción --}}
                    @if($inscripcion)
                    <div class="card mb-3" style="border-left:4px solid #17a2b8;">
                        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong><i class="fas fa-user-plus mr-1 text-info"></i>Inscripción {{ $inscripcion->insc_gestion }}</strong>
                                <span class="ml-2">Monto: <strong>{{ number_format($inscripcion->insc_monto_final ?? $inscripcion->insc_monto_total, 2) }} Bs</strong></span>
                            </div>
                            <div>
                                Pagado: <strong class="text-success">{{ number_format($inscripcion->insc_monto_pagado, 2) }} Bs</strong>
                                @if($inscripcion->insc_saldo > 0)
                                    — Saldo: <strong class="text-danger">{{ number_format($inscripcion->insc_saldo, 2) }} Bs</strong>
                                @else
                                    <span class="badge badge-success ml-1">Completo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Historial mensualidades --}}
                    <h6 class="font-weight-bold mb-2 mt-3"><i class="fas fa-receipt mr-1 text-success"></i>Historial de Mensualidades</h6>
                    @if($mensualidades->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:0.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr><th>Fecha</th><th>Concepto</th><th class="text-right">Monto</th></tr>
                            </thead>
                            <tbody>
                                @foreach($mensualidades as $pago)
                                <tr>
                                    <td>{{ $pago->pagos_fecha ? $pago->pagos_fecha->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $pago->concepto }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($pago->pagos_precio, 2) }} Bs</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <p class="text-muted small">No hay pagos de mensualidad registrados.</p>
                    @endif

                    {{-- Transporte --}}
                    @if($pagosTransporte->count() > 0)
                    <h6 class="font-weight-bold mb-2 mt-3"><i class="fas fa-bus mr-1 text-warning"></i>Pagos de Transporte</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:0.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr><th>Fecha pago</th><th>Vigencia</th><th>Estado</th><th class="text-right">Monto</th></tr>
                            </thead>
                            <tbody>
                                @foreach($pagosTransporte as $pt)
                                <tr>
                                    <td>{{ $pt->tpago_fecha_pago }}</td>
                                    <td>{{ $pt->tpago_fecha_inicio }} — {{ $pt->tpago_fecha_fin }}</td>
                                    <td>
                                        <span class="badge {{ $pt->tpago_estado === 'vigente' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($pt->tpago_estado) }}
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">{{ number_format($pt->tpago_monto, 2) }} Bs</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
