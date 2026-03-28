@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Detalle del Estudiante</h4>
                    <div>
                        <a href="{{ route('estudiantes.edit', $estudiante->est_id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('estudiantes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            @if($estudiante->est_foto)
                                <img src="{{ asset('storage/' . $estudiante->est_foto) }}" alt="Foto" class="img-fluid rounded" style="max-width: 250px;">
                            @else
                                <div class="bg-light rounded p-5">
                                    <i class="fas fa-user fa-5x text-muted"></i>
                                    <p class="text-muted mt-2">Sin foto</p>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Código</th>
                                    <td>{{ $estudiante->est_codigo }}</td>
                                </tr>
                                <tr>
                                    <th>Nombres</th>
                                    <td>{{ $estudiante->est_nombres }}</td>
                                </tr>
                                <tr>
                                    <th>Apellidos</th>
                                    <td>{{ $estudiante->est_apellidos }}</td>
                                </tr>
                                <tr>
                                    <th>CI</th>
                                    <td>{{ $estudiante->est_ci ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Curso</th>
                                    <td>{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Lugar de Nacimiento</th>
                                    <td>{{ $estudiante->est_lugarnac ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de Nacimiento</th>
                                    <td>{{ $estudiante->est_fechanac ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>U.E. de Procedencia</th>
                                    <td>{{ $estudiante->est_ueprocedencia ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Código RUDE</th>
                                    <td>{{ $estudiante->est_rude ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Nro. Celular</th>
                                    <td>{{ $estudiante->est_celular ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Preinscripción</th>
                                    <td>{{ $estudiante->preinscripcion }}</td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        @if($estudiante->est_visible)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-danger">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
