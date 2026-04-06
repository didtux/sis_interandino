@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-body">

        @php $user = auth()->user(); @endphp

        {{-- ============ ADMIN DASHBOARD ============ --}}
        @if($user->rol_id == 1)
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Estudiantes</h4></div><div class="card-body">{{ $totalEstudiantes }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success"><i class="fas fa-chalkboard"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Cursos</h4></div><div class="card-body">{{ $totalCursos }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Docentes</h4></div><div class="card-body">{{ $totalDocentes }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info"><i class="fas fa-user-plus"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Inscripciones {{ date('Y') }}</h4></div><div class="card-body">{{ $totalInscripciones }}</div></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Inscripciones del Mes</h4></div><div class="card-body">Bs. {{ number_format($inscripcionesMes, 2) }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Mensualidades del Mes</h4></div><div class="card-body">Bs. {{ number_format($mensualidadesMes, 2) }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info"><i class="fas fa-shopping-cart"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Ventas del Mes</h4></div><div class="card-body">Bs. {{ number_format($ventasMes, 2) }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon" style="background:#e74c3c;"><i class="fas fa-clipboard-list"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Notas por Aprobar</h4></div><div class="card-body">{{ $notasPendientes }}</div></div>
                    </div>
                </div>
            </div>

            {{-- Acciones rápidas --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h4></div>
                        <div class="card-body">
                            <a href="{{ route('inscripciones.create') }}" class="btn btn-primary m-1"><i class="fas fa-user-plus mr-1"></i>Nueva Inscripción</a>
                            <a href="{{ route('pagos.create') }}" class="btn btn-success m-1"><i class="fas fa-money-bill mr-1"></i>Registrar Mensualidad</a>
                            <a href="{{ route('ventas.create') }}" class="btn btn-info m-1"><i class="fas fa-shopping-cart mr-1"></i>Nueva Venta</a>
                            <a href="{{ route('estudiantes.create') }}" class="btn btn-warning m-1"><i class="fas fa-user-graduate mr-1"></i>Nuevo Estudiante</a>
                            <a href="{{ route('notas.index') }}" class="btn btn-danger m-1"><i class="fas fa-star mr-1"></i>Notas ({{ $notasPendientes }} pendientes)</a>
                            <a href="{{ route('asistencias.index') }}" class="btn btn-secondary m-1"><i class="fas fa-clipboard-check mr-1"></i>Asistencias</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asistencias + Estado inscripciones --}}
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-clipboard-check mr-2"></i>Hoy</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 text-center"><h2 class="text-success">{{ $asistenciasHoy }}</h2><p>Asistencias</p></div>
                                <div class="col-6 text-center"><h2 class="text-warning">{{ $atrasosHoy }}</h2><p>Atrasos</p></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-chart-pie mr-2"></i>Inscripciones {{ date('Y') }}</h4></div>
                        <div class="card-body"><canvas id="chartEstado" height="150"></canvas></div>
                    </div>
                </div>
            </div>

            {{-- Gráficos --}}
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-chart-line mr-2"></i>Inscripciones por Mes</h4></div>
                        <div class="card-body"><canvas id="chartInscripciones" height="200"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-chart-bar mr-2"></i>Estudiantes por Curso</h4></div>
                        <div class="card-body"><canvas id="chartEstudiantes" height="200"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-chart-bar mr-2"></i>Ventas por Categoría (30 días)</h4></div>
                        <div class="card-body"><canvas id="chartVentas" height="100"></canvas></div>
                    </div>
                </div>
            </div>

        {{-- ============ DOCENTE DASHBOARD ============ --}}
        @elseif($user->us_entidad_tipo === 'docente')
            <div class="row">
                <div class="col-12 mb-3">
                    <h4 class="text-white"><i class="fas fa-chalkboard-teacher mr-2"></i>Bienvenido, {{ $user->us_nombres }} {{ $user->us_apellidos }}</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary"><i class="fas fa-book"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Materias Asignadas</h4></div><div class="card-body">{{ $asignaciones->count() }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Total Estudiantes</h4></div><div class="card-body">{{ $totalEstudiantes }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Notas Borrador</h4></div><div class="card-body">{{ $notasBorrador }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info"><i class="fas fa-check-circle"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Notas Aprobadas</h4></div><div class="card-body">{{ $notasAprobadas }}</div></div>
                    </div>
                </div>
            </div>

            @if($notasRechazadas > 0)
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Tiene <strong>{{ $notasRechazadas }}</strong> calificación(es) rechazada(s). Revise y corrija.</div>
            @endif

            {{-- Materias y acceso rápido a notas --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-clipboard-list mr-2"></i>Mis Materias y Calificaciones</h4></div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Materia</th>
                                        @foreach($periodos as $p)
                                            <th class="text-center">{{ $p->periodo_nombre }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($asignaciones as $a)
                                        <tr>
                                            <td><span class="badge badge-primary">{{ $a->curso->cur_nombre ?? $a->cur_codigo }}</span></td>
                                            <td><span class="badge badge-warning">{{ $a->materia->mat_nombre ?? $a->mat_codigo }}</span></td>
                                            @foreach($periodos as $p)
                                                @php
                                                    $nota = \App\Models\Nota::where('curmatdoc_id', $a->curmatdoc_id)->where('periodo_id', $p->periodo_id)->first();
                                                    $estado = $nota->nota_estado ?? -1;
                                                @endphp
                                                <td class="text-center">
                                                    <a href="{{ route('notas.calificar', [$a->curmatdoc_id, $p->periodo_id]) }}"
                                                       class="btn btn-sm {{ $estado == 2 ? 'btn-success' : ($estado == 1 ? 'btn-warning' : ($estado == 3 ? 'btn-danger' : 'btn-outline-primary')) }}">
                                                        <i class="fas {{ $estado == 2 ? 'fa-check' : ($estado == 1 ? 'fa-clock' : ($estado == 3 ? 'fa-times' : 'fa-edit')) }} mr-1"></i>
                                                        {{ $estado == 2 ? 'Aprobado' : ($estado == 1 ? 'Enviado' : ($estado == 3 ? 'Rechazado' : ($estado == 0 ? 'Borrador' : 'Calificar'))) }}
                                                    </a>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ 2 + $periodos->count() }}" class="text-center text-muted py-4">No tiene materias asignadas</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ============ OTROS ROLES (por permisos) ============ --}}
        @else
            <div class="row">
                <div class="col-12 mb-3">
                    <h4 class="text-white"><i class="fas fa-home mr-2"></i>Bienvenido, {{ $user->us_nombres }} {{ $user->us_apellidos }}</h4>
                </div>
            </div>

            @if(isset($modulos) && $modulos->count())
                <div class="row">
                    @foreach($modulos as $mod)
                        @php
                            $slug = $mod['slug'];
                            $ruta = '#';
                            try { $ruta = route(str_replace('.', '-', $slug) . '.index'); } catch(\Exception $e) {
                                try { $ruta = route($slug); } catch(\Exception $e2) { $ruta = url('/' . explode('.', $slug)[0]); }
                            }
                            $colores = ['bg-primary','bg-success','bg-warning','bg-info','bg-danger','bg-secondary'];
                            $color = $colores[array_rand($colores)];
                        @endphp
                        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                            <a href="{{ $ruta }}" style="text-decoration:none;">
                                <div class="card card-statistic-1" style="cursor:pointer;">
                                    <div class="card-icon {{ $color }}"><i class="{{ $mod['icono'] }}"></i></div>
                                    <div class="card-wrap">
                                        <div class="card-header"><h4>{{ $mod['nombre'] }}</h4></div>
                                        <div class="card-body" style="font-size:12px;">
                                            @if($mod['puede_crear']) <span class="badge badge-success">Crear</span> @endif
                                            @if($mod['puede_editar']) <span class="badge badge-warning">Editar</span> @endif
                                            <span class="badge badge-info">Ver</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                                <h5>No tiene módulos asignados</h5>
                                <p class="text-muted">Contacte al administrador para que le asigne permisos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

    </div>
</section>
@endsection

@section('scripts')
@if($user->rol_id == 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
new Chart(document.getElementById('chartEstado').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Activas', 'Pagadas', 'Anuladas'],
        datasets: [{ data: [{{ $estadoInscripciones['pendientes'] }}, {{ $estadoInscripciones['pagadas'] }}, {{ $estadoInscripciones['anuladas'] }}], backgroundColor: ['#ffc107','#28a745','#dc3545'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

var meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
var dataMeses = new Array(12).fill(0);
@foreach($inscripcionesPorMes as $item) dataMeses[{{ $item->mes - 1 }}] = {{ $item->total }}; @endforeach
new Chart(document.getElementById('chartInscripciones').getContext('2d'), {
    type: 'line',
    data: { labels: meses, datasets: [{ label: 'Inscripciones', data: dataMeses, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.1)', tension: 0.4, fill: true }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('chartEstudiantes').getContext('2d'), {
    type: 'bar',
    data: { labels: [@foreach($estudiantesPorCurso as $c)'{{ $c->cur_nombre }}',@endforeach], datasets: [{ label: 'Estudiantes', data: [@foreach($estudiantesPorCurso as $c){{ $c->estudiantes_count }},@endforeach], backgroundColor: '#28a745' }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('chartVentas').getContext('2d'), {
    type: 'bar',
    data: { labels: [@foreach($ventasPorCategoria as $v)'{{ $v->categ_nombre }}',@endforeach], datasets: [{ label: 'Bs.', data: [@foreach($ventasPorCategoria as $v){{ $v->total }},@endforeach], backgroundColor: '#17a2b8' }] },
    options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
});
</script>
@endif
@endsection
