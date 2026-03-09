@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-clock mr-2"></i>Gestión de Atrasos</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Turno</label>
                                <select name="turno" class="form-control select2-turno">
                                    <option value="">Todos los turnos</option>
                                    @foreach($turnos as $turno)
                                        <option value="{{ $turno->config_id }}" {{ request('turno') == $turno->config_id ? 'selected' : '' }}>
                                            {{ $turno->config_categoria }} - {{ $turno->config_turno }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Curso</label>
                                <select name="cur_codigo" class="form-control select2-curso">
                                    <option value="">Todos los cursos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estudiante</label>
                                <select name="estudiante" class="form-control select2-estudiante">
                                    <option value="">Todos los estudiantes</option>
                                    @foreach($estudiantes as $e)
                                        <option value="{{ $e->est_codigo }}" {{ request('estudiante') == $e->est_codigo ? 'selected' : '' }}>{{ $e->est_nombres }} {{ $e->est_apellidos }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="{{ route('asistencia-config.atrasos') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                                <a href="{{ route('asistencia-config.atrasos.reporte-pdf', request()->all()) }}" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf"></i> Reporte PDF</a>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Turno</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atrasos as $a)
                                <tr>
                                    <td>{{ $a->estud_codigo }}</td>
                                    <td>{{ $a->estudiante->est_nombres ?? 'N/A' }} {{ $a->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $a->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $config = \App\Models\ConfiguracionAsistencia::activo()
                                                ->whereRaw('TIME(?) BETWEEN TIME(hora_entrada) AND TIME(hora_salida)', [$a->asis_hora])
                                                ->first();
                                        @endphp
                                        {{ $config ? $config->config_categoria . ' - ' . $config->config_turno : 'N/A' }}
                                    </td>
                                    <td>{{ $a->asis_fecha->format('d/m/Y') }}</td>
                                    <td>{{ $a->asis_hora }}</td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Atraso
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No hay atrasos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2-curso, .select2-estudiante, .select2-turno').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true
    });
});
</script>
@endsection
@endsection
