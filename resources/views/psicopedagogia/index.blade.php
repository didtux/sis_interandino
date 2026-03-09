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
                                    <td><span class="badge badge-info">{{ $caso->psico_tipo_acuerdo }}</span></td>
                                    <td>
                                        <a href="{{ route('psicopedagogia.edit', $caso->psico_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($caso->psico_tipo_acuerdo == 'ESCRITO')
                                            <a href="{{ route('psicopedagogia.compromiso-pdf', $caso->psico_id) }}" class="btn btn-sm btn-info" target="_blank" title="Compromiso">
                                                <i class="fas fa-file-contract"></i>
                                            </a>
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
                            @empty
                                <tr><td colspan="8" class="text-center">No hay casos registrados</td></tr>
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
