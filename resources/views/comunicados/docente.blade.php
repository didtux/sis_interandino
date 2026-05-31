@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4 class="mb-0"><i class="fas fa-inbox mr-2 text-primary"></i>Mis Comunicados / Documentación requerida</h4></div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

            @forelse($items as $d)
                @php
                    $com = $d->comunicado;
                    $limite = $com->com_fecha_limite;
                    $estado = $d->estadoEntrega($limite);
                    $cls = ['EN FECHA'=>'success','FUERA DE FECHA'=>'warning','NO ENTREGÓ'=>'danger','PENDIENTE'=>'secondary'][$estado] ?? 'secondary';
                @endphp
                <div class="card mb-2" style="border-left:4px solid var(--primary,#3a57e8);">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:8px;">
                            <div>
                                <strong>{{ $com->com_titulo }}</strong>
                                <span class="badge badge-{{ $cls }} ml-1">{{ $estado }}</span>
                                <small class="text-muted d-block">
                                    Límite: {{ $limite ? $limite->format('d/m/Y') : 'sin fecha' }}
                                    @if($d->cd_fecha_entrega) · Entregado: {{ $d->cd_fecha_entrega->format('d/m/Y H:i') }}@endif
                                </small>
                                @if($com->com_descripcion)<p class="mb-1 small mt-1">{{ $com->com_descripcion }}</p>@endif
                                @if($com->com_archivo)
                                    <a href="{{ asset('storage/'.$com->com_archivo) }}" target="_blank" class="small"><i class="fas fa-paperclip"></i> Material adjunto de dirección</a>
                                @endif
                                @if($d->cd_observacion)
                                    <div class="alert alert-warning py-1 px-2 mt-1 mb-0 small"><i class="fas fa-comment-dots"></i> Observación: {{ $d->cd_observacion }}</div>
                                @endif
                            </div>
                            <div style="min-width:260px;">
                                @if($com->com_requiere_archivo)
                                    @if($d->cd_archivo)
                                        <a href="{{ asset('storage/'.$d->cd_archivo) }}" target="_blank" class="btn btn-sm btn-outline-success mb-1"><i class="fas fa-file-download"></i> Ver mi entrega</a>
                                    @endif
                                    <form action="{{ route('comunicados.subir', $d->cd_id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="file" name="cd_archivo" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-upload"></i> {{ $d->cd_archivo ? 'Reemplazar' : 'Entregar' }}</button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-muted small">Solo informativo (no requiere archivo)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-4">No tienes comunicados pendientes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
