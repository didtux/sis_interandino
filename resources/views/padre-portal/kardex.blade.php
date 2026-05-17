@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-folder-open mr-2 text-info"></i>Kardex / Anotaciones</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            <form method="GET" class="mb-3">
                <label class="small font-weight-bold">Selecciona uno de tus hijos:</label>
                <select name="est_codigo" class="form-control" onchange="this.form.submit()" style="max-width:400px;">
                    @foreach($estudiantes as $e)
                        <option value="{{ $e->est_codigo }}" {{ $estSeleccionado && $estSeleccionado->est_codigo == $e->est_codigo ? 'selected' : '' }}>
                            {{ $e->est_apellido_paterno }} {{ $e->est_nombres }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if($estSeleccionado)
                <h6 class="text-muted mb-3">
                    <i class="fas fa-user-graduate mr-1"></i>
                    {{ $estSeleccionado->est_apellido_paterno }} {{ $estSeleccionado->est_nombres }}
                </h6>

                @forelse($registros as $r)
                    @php
                        $color = ['POSITIVO'=>'#27ae60','NEGATIVO'=>'#e74c3c','NEUTRO'=>'#7f8c8d'][$r->ek_categoria ?? ''] ?? '#34495e';
                        $tipoBadge = ['FELICITACION'=>'badge-success','ACADEMICO'=>'badge-primary','CONDUCTUAL'=>'badge-warning','OBSERVACION'=>'badge-secondary','COMPROMISO'=>'badge-info'][$r->ek_tipo] ?? 'badge-light';
                    @endphp
                    <div class="card mb-2" style="border-left:4px solid {{ $color }};">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge {{ $tipoBadge }} mr-1">{{ $r->ek_tipo }}</span>
                                    @if($r->ek_categoria)<span class="badge badge-light border">{{ $r->ek_categoria }}</span>@endif
                                    <strong class="ml-2">{{ $r->ek_titulo }}</strong>
                                </div>
                                <small class="text-muted">{{ $r->ek_fecha->format('d/m/Y') }}</small>
                            </div>
                            @if($r->ek_descripcion)
                                <p class="mb-2" style="font-size:13px;">{{ $r->ek_descripcion }}</p>
                            @endif
                            @if($r->ek_acuerdo)
                                <div class="alert alert-warning py-2 mb-2" style="font-size:12.5px;">
                                    <i class="fas fa-handshake mr-1"></i><strong>Compromiso:</strong> {{ $r->ek_acuerdo }}
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center" style="font-size:12px;">
                                <span class="text-muted">
                                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                                    {{ optional($r->docente)->doc_apellidos ?? 'Dirección' }} {{ optional($r->docente)->doc_nombres }}
                                </span>
                                @if($r->ek_visto_padre)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Visto {{ $r->ek_visto_padre_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <form action="{{ route('padre-portal.kardex.visto', $r->ek_id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-check mr-1"></i>Marcar como visto</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-folder-open" style="font-size:2rem;opacity:0.3;"></i>
                        <p class="mt-2">Aún no hay anotaciones para este estudiante.</p>
                    </div>
                @endforelse

                <div class="d-flex justify-content-center">{{ $registros->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
