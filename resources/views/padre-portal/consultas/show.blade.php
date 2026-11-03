@extends('layouts.app')

@section('content')
@php use App\Models\AtencionTicket; @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-0">{{ $ticket->at_asunto }}</h4>
                <small class="text-muted">{{ $ticket->area_nombre }}{{ $ticket->estudiante ? ' · ' . $ticket->estudiante->est_nombres . ' ' . $ticket->estudiante->est_apellidos : '' }}</small>
            </div>
            <span class="badge badge-{{ AtencionTicket::ESTADOS[$ticket->at_estado][1] }}">{{ $ticket->at_estado === 'ABIERTO' ? 'Esperando respuesta' : AtencionTicket::ESTADOS[$ticket->at_estado][0] }}</span>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
            @include('atencion._hilo', [
                'lado' => 'padre',
                'urlMensajes' => route('padre-portal.consultas.mensajes', $ticket->at_id),
                'urlResponder' => route('padre-portal.consultas.responder', $ticket->at_id),
                'puedeResponder' => $ticket->at_estado !== 'CERRADO',
            ])
            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('padre-portal.consultas') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Mis consultas</a>
                @if($ticket->at_estado !== 'CERRADO')
                <form method="POST" action="{{ route('padre-portal.consultas.cerrar', $ticket->at_id) }}" onsubmit="return confirm('¿Marcar la consulta como resuelta?')">
                    @csrf <button class="btn btn-outline-success"><i class="fas fa-check mr-1"></i>Ya está resuelta</button>
                </form>
                @else
                <span class="text-muted small align-self-center">Consulta cerrada. Si necesita algo más, abra una nueva.</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
