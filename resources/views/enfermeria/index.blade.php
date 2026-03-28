@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-heartbeat mr-2"></i>Enfermería</h4>
                    <div>
                        <a href="{{ route('enfermeria.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Registro
                        </a>
                        <a href="{{ route('enfermeria.reporte-pdf', array_merge(request()->all(), ['tipo_persona' => 'ESTUDIANTE'])) }}" class="btn btn-danger" target="_blank" title="Reporte Estudiantes">
                            <i class="fas fa-file-pdf"></i> PDF Estudiantes
                        </a>
                        <a href="{{ route('enfermeria.reporte-docentes-pdf', request()->all()) }}" class="btn btn-warning" target="_blank" title="Reporte Docentes">
                            <i class="fas fa-file-pdf"></i> PDF Docentes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Tipo</label>
                                <select name="tipo_persona" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="ESTUDIANTE" {{ request('tipo_persona') == 'ESTUDIANTE' ? 'selected' : '' }}>Estudiante</option>
                                    <option value="DOCENTE" {{ request('tipo_persona') == 'DOCENTE' ? 'selected' : '' }}>Docente</option>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                            <div class="col-md-2">
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
                                <label>Docente</label>
                                <select name="doc_codigo" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($docentes as $doc)
                                        <option value="{{ $doc->doc_codigo }}" {{ request('doc_codigo') == $doc->doc_codigo ? 'selected' : '' }}>
                                            {{ $doc->doc_nombres }} {{ $doc->doc_apellidos }}
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
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Persona</th>
                                <th>Curso</th>
                                <th>DX Detalle</th>
                                <th>Tipo Atención</th>
                                <th>Medicamentos</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $registro)
                                <tr>
                                    <td>{{ $registro->enf_fecha->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($registro->enf_hora)->format('H:i') }}</td>
                                    <td><span class="badge badge-{{ $registro->enf_tipo_persona == 'ESTUDIANTE' ? 'primary' : 'info' }}">{{ $registro->enf_tipo_persona }}</span></td>
                                    <td>
                                        @if($registro->enf_tipo_persona == 'ESTUDIANTE')
                                            {{ $registro->estudiante->est_nombres ?? '' }} {{ $registro->estudiante->est_apellidos ?? '' }}
                                        @else
                                            {{ $registro->docente->doc_nombres ?? '' }} {{ $registro->docente->doc_apellidos ?? '' }}
                                        @endif
                                    </td>
                                    <td>{{ $registro->enf_tipo_persona == 'ESTUDIANTE' ? ($registro->estudiante->curso->cur_nombre ?? '-') : '-' }}</td>
                                    <td>{{ $registro->enf_dx_detalle }}</td>
                                    <td>{{ $registro->enf_tipo_atencion ?? '-' }}</td>
                                    <td>{{ \Str::limit($registro->enf_medicamentos, 30) }}</td>
                                    <td>{{ \Str::limit($registro->enf_observaciones, 30) }}</td>
                                    <td>
                                        <a href="{{ route('enfermeria.edit', $registro->enf_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('enfermeria.destroy', $registro->enf_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center">No hay registros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $registros->appends(request()->query())->links() }}
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
