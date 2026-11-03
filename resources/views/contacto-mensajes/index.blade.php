@extends('layouts.app')

{{--
    Bandeja de los mensajes del formulario de contacto de la página pública.
    Antes ese formulario no guardaba nada y las consultas de los padres se
    perdían; "Solicitar inscripción" también llega acá con motivo INSCRIPCIÓN.
--}}

@section('content')
@php
    use App\Models\ContactoMensaje;
    $colorEstado = ['NUEVO' => 'danger', 'EN_PROCESO' => 'warning', 'ATENDIDO' => 'success', 'DESCARTADO' => 'secondary'];
@endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-envelope-open-text mr-2"></i>Mensajes de contacto</h4>
            <div>
                <span class="badge badge-danger">Nuevos: {{ $conteo['NUEVO'] ?? 0 }}</span>
                <span class="badge badge-warning">En proceso: {{ $conteo['EN_PROCESO'] ?? 0 }}</span>
                <span class="badge badge-success">Atendidos: {{ $conteo['ATENDIDO'] ?? 0 }}</span>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label class="small mb-1">Estado</label>
                        <select name="estado" class="form-control js-select2">
                            <option value="pendientes" {{ $estado === 'pendientes' ? 'selected' : '' }}>Pendientes (nuevos y en proceso)</option>
                            @foreach(ContactoMensaje::ESTADOS as $k => $t)
                                <option value="{{ $k }}" {{ $estado === $k ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                            <option value="todos" {{ $estado === 'todos' ? 'selected' : '' }}>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small mb-1">Motivo</label>
                        <select name="motivo" class="form-control js-select2">
                            <option value="">Todos</option>
                            @foreach(ContactoMensaje::MOTIVOS as $k => $t)
                                <option value="{{ $k }}" {{ request('motivo') === $k ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small mb-1">Buscar</label>
                        <input type="text" name="buscar" class="form-control" value="{{ request('buscar') }}" placeholder="Nombre, teléfono, correo o texto">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                        <a href="{{ route('contacto-mensajes.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Motivo</th>
                            <th>Mensaje</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mensajes as $m)
                            <tr class="{{ $m->cm_estado === 'NUEVO' ? 'font-weight-bold' : '' }}">
                                <td class="text-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $m->cm_nombre }}</td>
                                <td class="text-nowrap">
                                    @if($m->cm_telefono)<div><i class="fas fa-phone fa-fw text-muted"></i> {{ $m->cm_telefono }}</div>@endif
                                    @if($m->cm_correo)<div><i class="fas fa-envelope fa-fw text-muted"></i> {{ $m->cm_correo }}</div>@endif
                                </td>
                                <td>
                                    {{ ContactoMensaje::MOTIVOS[$m->cm_motivo] ?? $m->cm_motivo }}
                                    @if($m->cm_curso_interes)<div class="small text-muted">Curso: {{ $m->cm_curso_interes }}</div>@endif
                                </td>
                                <td style="max-width:320px;">{{ \Illuminate\Support\Str::limit($m->cm_mensaje, 110) }}</td>
                                <td><span class="badge badge-{{ $colorEstado[$m->cm_estado] ?? 'light' }}">{{ ContactoMensaje::ESTADOS[$m->cm_estado] ?? $m->cm_estado }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('contacto-mensajes.show', $m->cm_id) }}" class="btn btn-sm btn-info" title="Ver"><i class="fas fa-eye"></i></a>
                                    @if($m->whatsapp)
                                        <a href="https://wa.me/{{ $m->whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-success" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay mensajes para este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $mensajes->links() }}
        </div>
    </div>
</div>
@endsection
