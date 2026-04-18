@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-users mr-2"></i>Mis Estudiantes</h4></div>
                <div class="card-body">
                    @include('chofer-portal._selector-ruta')

                    @if($estudiantesRuta->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:0.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr>
                                    <th>N°</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Dirección Recogida</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estudiantesRuta as $i => $er)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <strong>{{ $er->estudiante->est_apellidos ?? '' }} {{ $er->estudiante->est_nombres ?? '' }}</strong>
                                        <br><small class="text-muted">{{ $er->est_codigo }}</small>
                                    </td>
                                    <td><span class="badge badge-primary">{{ $er->estudiante->curso->cur_nombre ?? '-' }}</span></td>
                                    <td>{{ $er->ter_direccion_recogida ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted mt-2" style="font-size:0.8rem;">
                        <i class="fas fa-info-circle mr-1"></i>Total: <strong>{{ $estudiantesRuta->count() }}</strong> estudiantes
                    </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay estudiantes asignados a esta ruta.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
