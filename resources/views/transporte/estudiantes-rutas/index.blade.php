@extends('layouts.app')

@section('content')
@php $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre']; @endphp
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-bus mr-2"></i>Estudiantes en Rutas de Transporte</h4>
                    <a href="{{ route('estudiantes-rutas.create') }}" class="btn btn-primary-modern btn-sm"><i class="fas fa-plus mr-1"></i>Asignar Estudiante</a>
                </div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar estudiante..." value="{{ request('buscar') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="cur_codigo" class="form-control form-control-sm">
                                    <option value="">Todos los cursos</option>
                                    @foreach($cursos as $c)
                                        <option value="{{ $c->cur_codigo }}" {{ request('cur_codigo') == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="ruta_codigo" class="form-control form-control-sm">
                                    <option value="">Todas las rutas</option>
                                    @foreach($rutas as $r)
                                        @php
                                            $asig = $r->asignaciones ? $r->asignaciones->where('asig_estado', 1)->first() : null;
                                            $busInfo = $asig && $asig->vehiculo ? ' - Bus ' . ($asig->vehiculo->veh_numero_bus ?? $asig->vehiculo->veh_placa) : '';
                                        @endphp
                                        <option value="{{ $r->ruta_codigo }}" {{ request('ruta_codigo') == $r->ruta_codigo ? 'selected' : '' }}>{{ $r->ruta_nombre }}{{ $busInfo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                                    <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex" style="gap:4px;">
                                <button class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i></button>
                                <a href="{{ route('estudiantes-rutas.index') }}" class="btn btn-secondary btn-sm btn-block mt-0"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table" style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Ruta</th>
                                    <th>Bus</th>
                                    <th>Servicio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $a)
                                    @php
                                        $asigRuta = $a->ruta && $a->ruta->asignaciones ? $a->ruta->asignaciones->where('asig_estado', 1)->first() : null;
                                        $numBus = $asigRuta && $asigRuta->vehiculo ? ($asigRuta->vehiculo->veh_numero_bus ?? $asigRuta->vehiculo->veh_placa) : '-';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $a->estudiante->est_apellidos ?? '' }} {{ $a->estudiante->est_nombres ?? '' }}</strong></td>
                                        <td><span class="modern-badge badge-primary-modern" style="font-size:10px;">{{ $a->estudiante->curso->cur_nombre ?? 'N/A' }}</span></td>
                                        <td>{{ $a->ruta->ruta_nombre ?? 'N/A' }}</td>
                                        <td><strong>{{ $numBus }}</strong></td>
                                        <td>
                                            @if(!$a->ter_estado)
                                                <span class="badge badge-dark">Inactivo</span>
                                            @elseif($a->ter_suspendido)
                                                <span class="badge badge-danger">Suspendido</span>
                                                <br><small class="text-danger">desde {{ $mesesNombres[$a->ter_suspendido_desde] ?? '?' }}</small>
                                            @else
                                                <span class="badge badge-success">Activo</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($a->ter_estado && !$a->ter_suspendido)
                                                {{-- Suspender --}}
                                                <button class="btn btn-sm btn-outline-danger" onclick="suspender({{ $a->ter_id }})" title="Suspender servicio"><i class="fas fa-pause"></i></button>
                                            @elseif($a->ter_estado && $a->ter_suspendido)
                                                {{-- Reactivar --}}
                                                <form action="{{ route('estudiantes-rutas.reactivar', $a->ter_id) }}" method="POST" style="display:inline;">@csrf @method('PUT')
                                                    <button class="btn btn-sm btn-success" title="Reactivar servicio" onclick="return confirm('¿Reactivar servicio de transporte?')"><i class="fas fa-play"></i></button>
                                                </form>
                                            @endif
                                            <a href="{{ route('estudiantes-rutas.edit', $a->ter_id) }}" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('estudiantes-rutas.destroy', $a->ter_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-dark" onclick="return confirm('¿Dar de baja?')" title="Dar de baja"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No hay estudiantes asignados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal suspender --}}
<div class="modal fade" id="modalSuspender" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header bg-danger text-white py-2">
            <h5 class="modal-title"><i class="fas fa-pause-circle mr-2"></i>Suspender Servicio</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <form id="formSuspender" method="POST">@csrf @method('PUT')
            <div class="modal-body">
                <p class="small text-muted">El estudiante no generará mora desde el mes seleccionado hasta que se reactive el servicio.</p>
                <div class="form-group">
                    <label class="font-weight-bold">Suspender desde</label>
                    <select name="mes" class="form-control">
                        @for($m = 2; $m <= 11; $m++)
                            <option value="{{ $m }}" {{ $m == (int)date('n') ? 'selected' : '' }}>{{ $mesesNombres[$m] }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-pause mr-1"></i>Suspender</button>
            </div>
        </form>
    </div></div>
</div>
@endsection

@section('scripts')
<script>
function suspender(id) {
    $('#formSuspender').attr('action', '{{ url("estudiantes-rutas") }}/' + id + '/suspender');
    $('#modalSuspender').modal('show');
}
</script>
@endsection
