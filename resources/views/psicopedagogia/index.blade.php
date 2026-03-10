@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-brain mr-2"></i>Psicopedagogía</h4>
                    <div>
                        <a href="{{ route('psicopedagogia.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Caso
                        </a>
                        <a href="{{ route('psicopedagogia.reporte-pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Curso</label>
                                <select name="cur_codigo" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Estudiante</label>
                                <select name="est_codigo" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($estudiantes as $est)
                                        <option value="{{ $est->est_codigo }}" {{ request('est_codigo') == $est->est_codigo ? 'selected' : '' }}>
                                            {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Caso</th>
                                <th>Solución</th>
                                <th>Acuerdo</th>
                                <th>Observaciones</th>
                                <th>Tipo Acuerdo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($casos as $caso)
                                <tr>
                                    <td>{{ $caso->psico_fecha->format('d/m/Y') }}</td>
                                    <td>{{ $caso->estudiante->est_nombres ?? '' }} {{ $caso->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $caso->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>{{ \Str::limit($caso->psico_caso, 40) }}</td>
                                    <td>{{ \Str::limit($caso->psico_solucion, 30) }}</td>
                                    <td>{{ \Str::limit($caso->psico_acuerdo, 30) }}</td>
                                    <td>{{ \Str::limit($caso->psico_observaciones, 30) }}</td>
                                    <td><span class="badge badge-info">{{ $caso->psico_tipo_acuerdo }}</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalDetalle{{ $caso->psico_id }}" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('psicopedagogia.edit', $caso->psico_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($caso->psico_tipo_acuerdo == 'ESCRITO')
                                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalCompromiso{{ $caso->psico_id }}" title="Compromiso">
                                                <i class="fas fa-file-contract"></i>
                                            </button>
                                        @endif
                                        <form action="{{ route('psicopedagogia.destroy', $caso->psico_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Detalle -->
                                <div class="modal fade" id="modalDetalle{{ $caso->psico_id }}">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title"><i class="fas fa-eye"></i> Detalles del Caso</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Código:</strong> {{ $caso->psico_codigo }}</p>
                                                        <p><strong>Fecha:</strong> {{ $caso->psico_fecha->format('d/m/Y') }}</p>
                                                        <p><strong>Estudiante:</strong> {{ $caso->estudiante->est_nombres }} {{ $caso->estudiante->est_apellidos }}</p>
                                                        <p><strong>Curso:</strong> {{ $caso->estudiante->curso->cur_nombre ?? 'N/A' }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Tipo Acuerdo:</strong> <span class="badge badge-info">{{ $caso->psico_tipo_acuerdo }}</span></p>
                                                        <p><strong>Registrado por:</strong> {{ $caso->psico_registrado_por ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p><strong>Caso:</strong></p>
                                                        <p class="text-justify">{{ $caso->psico_caso }}</p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p><strong>Solución:</strong></p>
                                                        <p class="text-justify">{{ $caso->psico_solucion ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p><strong>Acuerdo:</strong></p>
                                                        <p class="text-justify">{{ $caso->psico_acuerdo ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p><strong>Observaciones:</strong></p>
                                                        <p class="text-justify">{{ $caso->psico_observaciones ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Tipo Compromiso -->
                                <div class="modal fade" id="modalCompromiso{{ $caso->psico_id }}">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title"><i class="fas fa-file-contract"></i> Seleccionar Tipo de Compromiso</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Estudiante:</strong> {{ $caso->estudiante->est_nombres }} {{ $caso->estudiante->est_apellidos }}</p>
                                                <p><strong>Curso:</strong> {{ $caso->estudiante->curso->cur_nombre ?? 'N/A' }}</p>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="list-group">
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'ppff']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-file-alt text-primary"></i> Compromiso PPFF
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'informacion']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-info-circle text-info"></i> Acta de Información
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'conformidad']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-check-circle text-success"></i> Acta de Conformidad
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'disciplinario1']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exclamation-triangle text-warning"></i> Disciplinario 1
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'disciplinario1_alt']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exclamation-triangle text-warning"></i> Disciplinario 1 (Alt)
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'disciplinario1_alt2']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exclamation-triangle text-warning"></i> Disciplinario 1 (Alt2)
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'disciplinario1_alt3']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exclamation-triangle text-warning"></i> Disciplinario 1 (Alt3)
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'disciplinario2']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exclamation-triangle text-warning"></i> Disciplinario 2
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'transferencia']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-exchange-alt text-danger"></i> Transferencia
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'control']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-clipboard-check text-secondary"></i> Control y Disciplina
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'compromiso_diciplina']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-user-shield text-dark"></i> Compromiso Disciplinario
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="list-group">
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'puntualidad']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-clock text-primary"></i> Puntualidad
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'cumplimiento']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-tasks text-info"></i> Cumplimiento
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'cumplimiento_alt']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-tasks text-info"></i> Cumplimiento (Alt)
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'refaccion']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-tools text-warning"></i> Refacción
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'celular']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-mobile-alt text-danger"></i> Devolución Celular
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'neurologico']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-brain text-secondary"></i> Examen Neurológico
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'rendimiento']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-chart-line text-success"></i> Rendimiento Académico
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'uniforme']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-tshirt text-primary"></i> Uniforme
                                                            </a>
                                                            <a href="{{ route('psicopedagogia.compromiso-pdf', ['id' => $caso->psico_id, 'tipo' => 'padres_estudiante']) }}" class="list-group-item list-group-item-action" target="_blank">
                                                                <i class="fas fa-users text-info"></i> Padres y Estudiante
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay casos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $casos->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});
</script>
@endsection
@endsection
