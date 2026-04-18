@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-calendar-alt mr-2"></i>Detalle — {{ $agenda->age_codigo }}</h4>
                    <div>
                        <a href="{{ route('agenda.edit', $agenda->age_id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
                        <a href="{{ route('agenda.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless" style="font-size:0.9rem;">
                                <tr>
                                    <td style="width:150px;"><strong>Código:</strong></td>
                                    <td><span class="badge badge-secondary">{{ $agenda->age_codigo }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td>
                                        @if($agenda->age_tipo == 1)
                                            <span class="badge badge-primary"><i class="fas fa-calendar-check mr-1"></i>Agenda</span>
                                        @else
                                            <span class="badge badge-warning"><i class="fas fa-bell mr-1"></i>Notificación</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha y Hora:</strong></td>
                                    <td>
                                        @if($agenda->age_fechahora)
                                            {{ $agenda->age_fechahora->format('d/m/Y — H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Título:</strong></td>
                                    <td>{{ $agenda->age_titulo }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Detalles:</strong></td>
                                    <td>{!! nl2br(e($agenda->age_detalles)) !!}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estudiante:</strong></td>
                                    <td>
                                        @if($agenda->estudiante)
                                            {{ $agenda->estudiante->est_apellidos }} {{ $agenda->estudiante->est_nombres }}
                                            <span class="badge badge-primary ml-1">{{ $agenda->estudiante->curso->cur_nombre ?? '' }}</span>
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-4">
                            @if($agenda->estudiante && $agenda->estudiante->padres->count() > 0)
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-1"></i>Padres de Familia</h6>
                                @foreach($agenda->estudiante->padres as $padre)
                                <div class="card mb-2" style="border-left:3px solid #667eea;">
                                    <div class="card-body py-2 px-3" style="font-size:0.85rem;">
                                        <strong>{{ $padre->pfam_nombres }}</strong>
                                        @if($padre->pfam_parentesco)
                                            <span class="badge badge-light">{{ $padre->pfam_parentesco }}</span>
                                        @endif
                                        @if($padre->pfam_numeroscelular)
                                            <br><i class="fas fa-phone text-muted mr-1"></i>{{ $padre->pfam_numeroscelular }}
                                        @endif
                                        @if($padre->pfam_correo)
                                            <br><i class="fas fa-envelope text-muted mr-1"></i>{{ $padre->pfam_correo }}
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            @elseif($agenda->estudiante)
                                <div class="text-muted" style="font-size:0.85rem;">
                                    <i class="fas fa-info-circle mr-1"></i>Sin padres registrados
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>
                    <form action="{{ route('agenda.destroy', $agenda->age_id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este registro?')">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
