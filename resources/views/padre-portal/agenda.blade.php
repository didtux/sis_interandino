@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header"><h4><i class="fas fa-calendar-alt mr-2"></i>Agenda</h4></div>
        <div class="card-body">
            @include('padre-portal._selector-estudiante')
            @foreach($institucional as $p)
                <div class="alert alert-light border">
                    <strong>{{ $p->pub_titulo }}</strong>
                    @if($p->url_archivo)<a href="{{ $p->url_archivo }}" target="_blank" class="ml-2"><i class="fas fa-file-pdf mr-1"></i>Ver agenda institucional</a>@endif
                </div>
            @endforeach
            @forelse($eventos as $e)
                <div class="d-flex border-bottom py-2">
                    <div class="text-center mr-3" style="min-width:60px;">
                        <div class="h5 mb-0">{{ \Carbon\Carbon::parse($e->age_fechahora)->format('d') }}</div>
                        <small class="text-muted text-uppercase">{{ \Carbon\Carbon::parse($e->age_fechahora)->locale('es')->isoFormat('MMM') }}</small>
                    </div>
                    <div>
                        <strong>{{ $e->age_titulo }}</strong>
                        <span class="badge badge-{{ $e->age_tipo == 2 ? 'warning' : 'info' }} ml-1">{{ $e->age_tipo == 2 ? 'Aviso' : 'Agenda' }}</span>
                        <div class="small text-muted">{{ \Carbon\Carbon::parse($e->age_fechahora)->format('H:i') }}</div>
                        @if($e->age_detalles)<div class="small" style="white-space:pre-line;">{{ $e->age_detalles }}</div>@endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No hay actividades en la agenda del curso.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
