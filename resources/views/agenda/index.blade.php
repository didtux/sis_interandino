@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-calendar-alt mr-2"></i>Agenda y Notificaciones</h4>
                    <a href="{{ route('agenda.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Registro
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Estudiante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agendas as $agenda)
                                    <tr>
                                        <td data-label="Fecha y Hora">
                                            <i class="fas fa-calendar mr-1"></i>{{ $agenda->age_fechahora->format('d/m/Y') }}
                                            <br><i class="fas fa-clock mr-1"></i>{{ $agenda->age_fechahora->format('H:i') }}
                                        </td>
                                        <td data-label="Tipo">
                                            @if($agenda->age_tipo == 1)
                                                <span class="modern-badge badge-primary-modern">
                                                    <i class="fas fa-calendar-check mr-1"></i>Agenda
                                                </span>
                                            @else
                                                <span class="modern-badge badge-warning-modern">
                                                    <i class="fas fa-bell mr-1"></i>Notificación
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="Título">{{ $agenda->age_titulo }}</td>
                                        <td data-label="Estudiante">
                                            <span class="modern-badge badge-success-modern">{{ $agenda->estudiante->est_nombres ?? 'N/A' }}</span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <form action="{{ route('agenda.destroy', $agenda->age_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este registro?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-calendar-alt"></i>
                                                <h5>No hay registros en la agenda</h5>
                                                <p>Comienza agregando tu primer evento</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $agendas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
