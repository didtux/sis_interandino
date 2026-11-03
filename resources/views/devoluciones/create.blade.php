@extends('layouts.app')

@section('content')
@php use App\Models\Devolucion; @endphp
<div class="section-body">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-undo-alt mr-2"></i>Solicitar devolución</h4></div>
                <div class="card-body">
                    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                    <form method="GET" class="form-inline mb-3">
                        <label class="mr-2">N° de recibo</label>
                        <input type="text" name="recibo" class="form-control mr-2" value="{{ request('recibo', $origen['recibo'] ?? '') }}" placeholder="RSV00001, REC000123…">
                        <button class="btn btn-secondary"><i class="fas fa-search mr-1"></i>Buscar</button>
                    </form>

                    @if(request('recibo') && !$origen)
                        <div class="alert alert-warning">No se encontró un recibo vigente con ese número.</div>
                    @endif

                    @if($origen)
                        <div class="border rounded p-3 bg-light mb-3">
                            <div><strong>{{ $origen['tipo'] }}</strong> — recibo {{ $origen['recibo'] }}</div>
                            <div>{{ $origen['nombre'] }}</div>
                            <div class="small text-muted">{{ $origen['detalle'] }}</div>
                            <div class="h5 mb-0 mt-1">Bs {{ number_format($origen['monto'], 2) }}</div>
                        </div>
                        <form method="POST" action="{{ route('devoluciones.store') }}">
                            @csrf
                            <input type="hidden" name="recibo" value="{{ $origen['recibo'] }}">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Motivo</label>
                                    <select name="dev_motivo" class="form-control js-select2" required>
                                        @foreach(Devolucion::MOTIVOS as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>Retención (Bs)</label>
                                    <input type="number" step="0.01" min="0" max="{{ $origen['monto'] }}" name="monto_retenido" id="retenido" class="form-control"
                                           value="{{ $origen['tipo'] === 'RESERVA' ? round($origen['monto'] * $retencion / 100, 2) : 0 }}">
                                    @if($origen['tipo'] === 'RESERVA')<small class="text-muted">Política: {{ $retencion }}% de la reserva.</small>@endif
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>Se devuelve en</label>
                                    <select name="dev_metodo" class="form-control js-select2"><option>EFECTIVO</option><option>QR</option></select>
                                </div>
                            </div>
                            <div class="form-group"><label>Detalle</label><textarea name="dev_detalle" class="form-control" rows="2"></textarea></div>
                            <div class="mb-3">A devolver: <strong>Bs <span id="aDevolver"></span></strong></div>
                            <button class="btn btn-primary-modern"><i class="fas fa-paper-plane mr-1"></i>Solicitar</button>
                            <a href="{{ route('devoluciones.index') }}" class="btn btn-secondary">Volver</a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if($origen)
<script>
function calc() { var t = {{ (float) $origen['monto'] }}; var r = parseFloat($('#retenido').val()) || 0; $('#aDevolver').text(Math.max(0, t - r).toFixed(2)); }
$('#retenido').on('input', calc); calc();
</script>
@endif
@endsection
