@extends('layouts.app')

@section('content')
@php use App\Models\ContactoMensaje; @endphp
<div class="section-body">
    <div class="row">
        <div class="col-lg-7">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-envelope-open mr-2"></i>Mensaje de {{ $mensaje->cm_nombre }}</h4>
                    <a href="{{ route('contacto-mensajes.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                </div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Recibido</dt>
                        <dd class="col-sm-8">{{ $mensaje->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4">Motivo</dt>
                        <dd class="col-sm-8">{{ ContactoMensaje::MOTIVOS[$mensaje->cm_motivo] ?? $mensaje->cm_motivo }}</dd>
                        @if($mensaje->cm_curso_interes)
                            <dt class="col-sm-4">Curso de interés</dt>
                            <dd class="col-sm-8">{{ $mensaje->cm_curso_interes }}</dd>
                        @endif
                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8">
                            {{ $mensaje->cm_telefono ?: '—' }}
                            @if($mensaje->whatsapp)
                                <a href="https://wa.me/{{ $mensaje->whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-success ml-2">
                                    <i class="fab fa-whatsapp mr-1"></i>Responder por WhatsApp
                                </a>
                            @endif
                        </dd>
                        <dt class="col-sm-4">Correo</dt>
                        <dd class="col-sm-8">
                            @if($mensaje->cm_correo)<a href="mailto:{{ $mensaje->cm_correo }}">{{ $mensaje->cm_correo }}</a>@else — @endif
                        </dd>
                    </dl>
                    <hr>
                    <h6>Mensaje</h6>
                    <p style="white-space:pre-line;">{{ $mensaje->cm_mensaje }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card modern-card">
                <div class="card-header"><h4 class="mb-0"><i class="fas fa-tasks mr-2"></i>Seguimiento</h4></div>
                <div class="card-body">
                    @if($mensaje->cm_atendido_at)
                        <p class="small text-muted">
                            Último cambio: {{ $mensaje->cm_atendido_at->format('d/m/Y H:i') }}
                            por {{ optional($mensaje->atendidoPor)->us_nombres ?? optional($mensaje->atendidoPor)->us_user ?? '—' }}
                        </p>
                    @endif
                    @puede('contacto-mensajes', 'editar')
                    <form method="POST" action="{{ route('contacto-mensajes.update', $mensaje->cm_id) }}">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="cm_estado" class="form-control js-select2">
                                @foreach(ContactoMensaje::ESTADOS as $k => $t)
                                    <option value="{{ $k }}" {{ $mensaje->cm_estado === $k ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nota interna <small class="text-muted">(no la ve quien escribió)</small></label>
                            <textarea name="cm_nota_interna" class="form-control" rows="4" maxlength="2000">{{ $mensaje->cm_nota_interna }}</textarea>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
                    </form>
                    @else
                        <p class="mb-1"><strong>Estado:</strong> {{ ContactoMensaje::ESTADOS[$mensaje->cm_estado] ?? $mensaje->cm_estado }}</p>
                        @if($mensaje->cm_nota_interna)<p style="white-space:pre-line;">{{ $mensaje->cm_nota_interna }}</p>@endif
                    @endpuede
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
