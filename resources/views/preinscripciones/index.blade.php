@extends('layouts.app')

@section('content')
@php use App\Models\Preinscripcion; @endphp
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-user-clock mr-2"></i>Pre-inscripciones {{ $gestion }}</h4>
            <div>
                @if($abierta)
                    @puede('preinscripciones', 'crear')
                        <a href="{{ route('preinscripciones.create', ['gestion' => $gestion]) }}" class="btn btn-primary-modern"><i class="fas fa-plus mr-1"></i>Nueva pre-inscripción</a>
                    @endpuede
                @else
                    <span class="badge badge-secondary p-2">Inscripción {{ $gestion }} cerrada</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if(!$abierta)
                <div class="alert alert-info">Para habilitar la pre-inscripción de {{ $gestion }}, cree la gestión y marque "Inscripción abierta" en Parametrización → Gestiones.</div>
            @endif

            <div class="mb-3">
                @foreach(Preinscripcion::ESTADOS as $k => [$t, $color])
                    <span class="badge badge-{{ $color }} mr-1">{{ $t }}: {{ $conteo[$k] ?? 0 }}</span>
                @endforeach
            </div>

            <form method="GET" class="mb-3">
                <input type="hidden" name="gestion" value="{{ $gestion }}">
                <div class="row">
                    <div class="col-md-3">
                        <select name="estado" class="form-control js-select2">
                            <option value="">Todos los estados</option>
                            @foreach(Preinscripcion::ESTADOS as $k => [$t])
                                <option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="cur_destino" class="form-control js-select2">
                            <option value="">Todos los cursos</option>
                            @foreach($cursos as $c)
                                <option value="{{ $c->cur_codigo }}" {{ request('cur_destino') === $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="buscar" class="form-control" value="{{ request('buscar') }}" placeholder="Nombre, CI o código"></div>
                    <div class="col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-search"></i></button></div>
                </div>
            </form>

            @if(($conteo['CONFIRMADA'] ?? 0) > 0)
                @puede('preinscripciones', 'crear')
                <form method="POST" action="{{ route('preinscripciones.convertir-confirmadas') }}" class="mb-2"
                      onsubmit="return confirm('Se crearán las inscripciones {{ $gestion }} de todas las pre-inscripciones CONFIRMADAS de alumnos antiguos. ¿Continuar?')">
                    @csrf <input type="hidden" name="gestion" value="{{ $gestion }}">
                    <button class="btn btn-sm btn-success"><i class="fas fa-check-double mr-1"></i>Inscribir todas las confirmadas</button>
                </form>
                @endpuede
            @endif

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr><th>Código</th><th>Estudiante</th><th>Tipo</th><th>Curso actual</th><th>Curso {{ $gestion }}</th><th class="text-right">Reserva</th><th>Estado</th><th>Fecha</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($pres as $p)
                            @php [$et, $ec] = Preinscripcion::ESTADOS[$p->preinsc_estado] ?? [$p->preinsc_estado, 'light']; @endphp
                            <tr>
                                <td>{{ $p->preinsc_codigo }}</td>
                                <td>{{ $p->nombre_completo }}</td>
                                <td><span class="badge badge-{{ $p->preinsc_tipo === 'NUEVO' ? 'info' : 'light' }}">{{ $p->preinsc_tipo }}</span></td>
                                <td>{{ optional($p->cursoOrigen)->cur_nombre ?? '—' }}</td>
                                <td>{{ optional($p->cursoDestino)->cur_nombre }}</td>
                                <td class="text-right">{{ number_format($p->monto_reserva, 2) }}</td>
                                <td><span class="badge badge-{{ $ec }}">{{ $et }}</span></td>
                                <td>{{ $p->preinsc_fecha->format('d/m/Y') }}</td>
                                <td><a href="{{ route('preinscripciones.show', $p->preinsc_id) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Sin pre-inscripciones para este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
