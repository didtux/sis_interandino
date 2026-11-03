@extends('layouts.app')

@section('content')
@php use App\Models\Devolucion; $aprueba = auth()->user()->tieneCapacidad('devoluciones.aprobar'); @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-undo-alt mr-2"></i>Devoluciones</h4>
            @puede('devoluciones', 'crear')
                <a href="{{ route('devoluciones.create') }}" class="btn btn-primary-modern"><i class="fas fa-plus mr-1"></i>Nueva devolución</a>
            @endpuede
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span>{{ session('success') }}</span>
                    @if(session('abrir_recibo'))<a href="{{ session('abrir_recibo') }}" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-print mr-1"></i>Recibo</a>@endif
                </div>
            @endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            <p class="text-muted small">Una devolución no anula el recibo original: es una salida de dinero con su propio recibo (serie DEV) y aparece como egreso en la caja del día en que se paga.</p>
            <form method="GET" class="form-inline mb-3">
                <select name="estado" class="form-control form-control-sm mr-2 js-select2">
                    <option value="">Todos los estados</option>
                    @foreach(Devolucion::ESTADOS as $k => [$t])<option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-secondary"><i class="fas fa-filter"></i></button>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Solicitada</th><th>Estudiante</th><th>Origen</th><th>Motivo</th><th class="text-right">Original</th><th class="text-right">Retenido</th><th class="text-right">A devolver</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($devoluciones as $d)
                            @php [$et, $ec] = Devolucion::ESTADOS[$d->dev_estado]; @endphp
                            <tr>
                                <td>{{ optional($d->solicitado_fecha)->format('d/m/Y') }}</td>
                                <td>{{ $d->estudiante ? $d->estudiante->est_apellidos . ' ' . $d->estudiante->est_nombres : optional($d->preinscripcion)->nombre_completo }}</td>
                                <td>{{ $d->origen_tipo }} {{ $d->recibo_origen }}</td>
                                <td>{{ Devolucion::MOTIVOS[$d->dev_motivo] ?? $d->dev_motivo }}</td>
                                <td class="text-right">{{ number_format($d->monto_original, 2) }}</td>
                                <td class="text-right">{{ number_format($d->monto_retenido, 2) }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($d->monto_devuelto, 2) }}</td>
                                <td><span class="badge badge-{{ $ec }}" title="{{ $d->rechazo_motivo }}">{{ $et }}</span></td>
                                <td class="text-nowrap">
                                    @if($d->dev_estado === 'SOLICITADA' && $aprueba)
                                        <form method="POST" action="{{ route('devoluciones.aprobar', $d->dev_id) }}" class="d-inline">@csrf <button class="btn btn-sm btn-success" title="Aprobar"><i class="fas fa-check"></i></button></form>
                                    @endif
                                    @if(in_array($d->dev_estado, ['SOLICITADA', 'APROBADA']) && $aprueba)
                                        <form method="POST" action="{{ route('devoluciones.rechazar', $d->dev_id) }}" class="d-inline" onsubmit="var m = prompt('Motivo del rechazo:'); if (!m) return false; this.motivo.value = m; return true;">
                                            @csrf <input type="hidden" name="motivo"><button class="btn btn-sm btn-outline-danger" title="Rechazar"><i class="fas fa-times"></i></button>
                                        </form>
                                    @endif
                                    @if($d->dev_estado === 'APROBADA')
                                        @puede('devoluciones', 'editar')
                                        <form method="POST" action="{{ route('devoluciones.pagar', $d->dev_id) }}" class="d-inline" onsubmit="return confirm('Se entrega Bs {{ number_format($d->monto_devuelto, 2) }} y se emite el recibo de devolución. ¿Continuar?')">
                                            @csrf <button class="btn btn-sm btn-primary"><i class="fas fa-hand-holding-usd mr-1"></i>Pagar</button>
                                        </form>
                                        @endpuede
                                    @endif
                                    @if($d->dev_codigo)
                                        <a href="{{ route('recibos.mostrar', ['devolucion', $d->dev_codigo]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Recibo"><i class="fas fa-file-pdf"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Sin devoluciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $devoluciones->links() }}
        </div>
    </div>
</div>
@endsection
