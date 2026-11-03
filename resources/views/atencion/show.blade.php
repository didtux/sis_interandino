@extends('layouts.app')

@section('content')
@php use App\Models\AtencionTicket; @endphp
<div class="section-body">
    <div class="row">
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $ticket->at_asunto }}</h4>
                    <span class="badge badge-{{ AtencionTicket::ESTADOS[$ticket->at_estado][1] }}">{{ AtencionTicket::ESTADOS[$ticket->at_estado][0] }}</span>
                </div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
                    @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
                    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
                    @include('atencion._hilo', [
                        'lado' => 'personal',
                        'urlMensajes' => route('atencion.mensajes', $ticket->at_id),
                        'urlResponder' => route('atencion.responder', $ticket->at_id),
                        'puedeResponder' => $permisos['responder'] && $ticket->at_estado !== 'CERRADO',
                    ])
                    @if($ticket->at_estado === 'CERRADO')<div class="text-muted small mt-2">La consulta está cerrada; reábrala para responder.</div>@endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body small">
                    <p class="mb-1"><strong>Área:</strong> {{ $ticket->area_nombre }}</p>
                    <p class="mb-1"><strong>Padre/madre:</strong> {{ optional($ticket->padre)->pfam_nombres ?: '—' }}
                        @if(optional($ticket->padre)->pfam_numeroscelular) · <a href="https://wa.me/591{{ preg_replace('/\D/', '', $ticket->padre->pfam_numeroscelular) }}" target="_blank"><i class="fab fa-whatsapp"></i> {{ $ticket->padre->pfam_numeroscelular }}</a>@endif
                    </p>
                    @if($ticket->estudiante)
                        <p class="mb-1"><strong>Estudiante:</strong> {{ $ticket->estudiante->est_apellidos }} {{ $ticket->estudiante->est_nombres }}</p>
                        <p class="mb-1"><strong>Curso:</strong> {{ optional($ticket->estudiante->curso)->cur_nombre }}</p>
                    @endif
                    <p class="mb-0"><strong>Abierta:</strong> {{ $ticket->at_fecha->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            @if($permisos['gestionar'])
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('atencion.estado', $ticket->at_id) }}" class="mb-3">
                        @csrf
                        @if($ticket->at_estado === 'CERRADO')
                            <input type="hidden" name="accion" value="reabrir">
                            <button class="btn btn-outline-primary btn-block"><i class="fas fa-undo mr-1"></i>Reabrir</button>
                        @else
                            <input type="hidden" name="accion" value="cerrar">
                            <button class="btn btn-outline-secondary btn-block" onclick="return confirm('¿Cerrar la consulta?')"><i class="fas fa-check mr-1"></i>Cerrar consulta</button>
                        @endif
                    </form>
                    <form method="POST" action="{{ route('atencion.derivar', $ticket->at_id) }}">
                        @csrf
                        <label class="small mb-1">Derivar a otra área</label>
                        <div class="input-group input-group-sm">
                            <select name="area" class="form-control js-select2">
                                @foreach($areas as $k => $a)
                                    @if($k !== $ticket->at_area)<option value="{{ $k }}">{{ $a['nombre'] }}</option>@endif
                                @endforeach
                            </select>
                            <div class="input-group-append"><button class="btn btn-outline-primary">Derivar</button></div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            <a href="{{ route('atencion.index') }}" class="btn btn-light btn-block"><i class="fas fa-arrow-left mr-1"></i>Volver a la bandeja</a>
        </div>
    </div>
</div>
@endsection
