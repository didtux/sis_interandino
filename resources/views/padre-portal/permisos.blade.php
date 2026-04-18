@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header"><h4><i class="fas fa-file-alt mr-2"></i>Permisos Solicitados</h4></div>
                <div class="card-body">
                    @include('padre-portal._selector-estudiante')

                    @if($estSeleccionado && $permisos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Tipo</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Motivo</th>
                                    <th>Solicitante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permisos as $p)
                                <tr>
                                    <td>{{ $p->permiso_numero }}</td>
                                    <td>
                                        <span class="badge badge-{{ $p->permiso_tipo === 'LICENCIA' ? 'info' : 'warning' }}">
                                            {{ $p->permiso_tipo }}
                                        </span>
                                    </td>
                                    <td>{{ $p->permiso_fecha_inicio->format('d/m/Y') }}</td>
                                    <td>{{ $p->permiso_fecha_fin->format('d/m/Y') }}</td>
                                    <td>{{ $p->permiso_motivo }}</td>
                                    <td>{{ $p->solicitante_nombre_completo }}</td>
                                    <td>
                                        @if($p->permiso_estado == 1)
                                            <span class="badge badge-success">Aprobado</span>
                                        @elseif($p->permiso_estado == 2)
                                            <span class="badge badge-warning">Pendiente</span>
                                        @else
                                            <span class="badge badge-danger">Rechazado</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($p->permiso_observacion)
                                <tr>
                                    <td colspan="7" class="py-1 pl-4" style="background:#f8f9fa; font-size:0.8rem;">
                                        <i class="fas fa-comment-alt text-muted mr-1"></i>{{ $p->permiso_observacion }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($estSeleccionado)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-file-alt fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <p>No hay permisos registrados.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
