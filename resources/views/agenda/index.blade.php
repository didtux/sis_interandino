@extends('layouts.app')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
@endsection

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
                        <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabLista"><i class="fas fa-list mr-1"></i>Lista</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabCalendario"><i class="fas fa-calendar mr-1"></i>Calendario</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Tab Lista --}}
                        <div class="tab-pane fade show active" id="tabLista">
                            <form method="GET" class="form-inline mb-3" style="gap:8px;">
                                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="Buscar título o estudiante...">
                                <select name="tipo" class="form-control form-control-sm">
                                    <option value="">Todos los tipos</option>
                                    <option value="1" {{ request('tipo') == '1' ? 'selected' : '' }}>Agenda</option>
                                    <option value="2" {{ request('tipo') == '2' ? 'selected' : '' }}>Notificación</option>
                                </select>
                                <button class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i>Filtrar</button>
                                @if(request()->hasAny(['buscar','tipo']))
                                    <a href="{{ route('agenda.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times mr-1"></i>Limpiar</a>
                                @endif
                            </form>

                            <div class="table-responsive-modern">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
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
                                            <td data-label="Código"><span class="modern-badge badge-secondary-modern">{{ $agenda->age_codigo }}</span></td>
                                            <td data-label="Fecha">
                                                @if($agenda->age_fechahora)
                                                    <i class="fas fa-calendar mr-1"></i>{{ $agenda->age_fechahora->format('d/m/Y') }}
                                                    <br><i class="fas fa-clock mr-1"></i>{{ $agenda->age_fechahora->format('H:i') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td data-label="Tipo">
                                                @if($agenda->age_tipo == 1)
                                                    <span class="modern-badge badge-primary-modern"><i class="fas fa-calendar-check mr-1"></i>Agenda</span>
                                                @else
                                                    <span class="modern-badge badge-warning-modern"><i class="fas fa-bell mr-1"></i>Notificación</span>
                                                @endif
                                            </td>
                                            <td data-label="Título">{{ $agenda->age_titulo }}</td>
                                            <td data-label="Estudiante">
                                                @if($agenda->estudiante)
                                                    {{ $agenda->estudiante->est_apellidos }} {{ $agenda->estudiante->est_nombres }}
                                                    <br><span class="badge badge-primary" style="font-size:0.7rem;">{{ $agenda->estudiante->curso->cur_nombre ?? '' }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td data-label="Acciones">
                                                <div class="action-buttons">
                                                    <a href="{{ route('agenda.show', $agenda->age_id) }}" class="btn btn-action btn-action-view" title="Ver"><i class="fas fa-eye"></i></a>
                                                    <a href="{{ route('agenda.edit', $agenda->age_id) }}" class="btn btn-action btn-action-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                                    <form action="{{ route('agenda.destroy', $agenda->age_id) }}" method="POST" style="display:inline;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Eliminar este registro?')" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6">
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
                            <div class="d-flex justify-content-center">{{ $agendas->links() }}</div>
                        </div>

                        {{-- Tab Calendario --}}
                        <div class="tab-pane fade" id="tabCalendario">
                            <div id="calendario"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/es.js"></script>
<script>
var calendarInit = false;
$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    if ($(e.target).attr('href') === '#tabCalendario' && !calendarInit) {
        calendarInit = true;
        var cal = new FullCalendar.Calendar(document.getElementById('calendario'), {
            locale: 'es',
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            events: @json($eventos),
            eventClick: function(info) {
                if (info.event.url) { info.jsEvent.preventDefault(); window.location.href = info.event.url; }
            },
            height: 'auto'
        });
        cal.render();
    }
});
</script>
@endsection
