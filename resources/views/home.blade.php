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
                        <div class="card-icon" style="background:#6f42c1;"><i class="fas fa-users"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Padres / Tutores</h4></div><div class="card-body">{{ $totalPadres }}</div></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info"><i class="fas fa-user-plus"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Inscripciones {{ date('Y') }}</h4></div><div class="card-body">{{ $totalInscripciones }}</div></div>
                    </div>
                </div>
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

            {{-- Indicadores operativos --}}
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon" style="background:#e74c3c;"><i class="fas fa-clipboard-list"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Notas por Aprobar</h4></div><div class="card-body">{{ $notasPendientes }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon" style="background:#8e44ad;"><i class="fas fa-user-slash"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Observados Activos</h4></div><div class="card-body">{{ $observadosActivos }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info"><i class="fas fa-file-signature"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Permisos Hoy</h4></div><div class="card-body">{{ $permisosHoy }}</div></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="card-wrap"><div class="card-header"><h4>Atrasos Hoy</h4></div><div class="card-body">{{ $atrasosHoy }}</div></div>
                    </div>
                </div>
            </div>

            {{-- Desglose de recaudación --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-hand-holding-usd mr-2"></i>Recaudación Acumulada {{ date('Y') }}</h4></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3 col-6 mb-2"><div class="text-muted small">Mensualidades</div><h4 class="text-primary">Bs. {{ number_format($recaudacionAnual['mensualidades'], 2) }}</h4></div>
                                <div class="col-md-3 col-6 mb-2"><div class="text-muted small">Inscripciones</div><h4 class="text-success">Bs. {{ number_format($recaudacionAnual['inscripciones'], 2) }}</h4></div>
                                <div class="col-md-3 col-6 mb-2"><div class="text-muted small">Ventas</div><h4 class="text-info">Bs. {{ number_format($recaudacionAnual['ventas'], 2) }}</h4></div>
                                <div class="col-md-3 col-6 mb-2"><div class="text-muted small">TOTAL</div><h4 class="font-weight-bold">Bs. {{ number_format($recaudacionAnual['total'], 2) }}</h4></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ranking: mejores estudiantes + en riesgo --}}
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-trophy mr-2 text-warning"></i>Mejores Estudiantes {{ date('Y') }}</h4></div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead><tr><th>#</th><th>Estudiante</th><th>Curso</th><th class="text-center">Promedio</th></tr></thead>
                                <tbody>
                                    @forelse($mejoresEstudiantes as $idx => $est)
                                        <tr>
                                            <td>
                                                @if($idx === 0)<i class="fas fa-medal text-warning"></i>
                                                @elseif($idx === 1)<i class="fas fa-medal" style="color:#b0b0b0;"></i>
                                                @elseif($idx === 2)<i class="fas fa-medal" style="color:#cd7f32;"></i>
                                                @else{{ $idx + 1 }}@endif
                                            </td>
                                            <td>{{ $est->nombre }}</td>
                                            <td><span class="badge badge-primary">{{ $est->cur_nombre ?? '-' }}</span></td>
                                            <td class="text-center font-weight-bold text-success">{{ $est->promedio }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin notas aprobadas aún</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Estudiantes en Riesgo (&lt; 51)</h4></div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead><tr><th>Estudiante</th><th>Curso</th><th class="text-center">Promedio</th></tr></thead>
                                <tbody>
                                    @forelse($estudiantesEnRiesgo as $est)
                                        <tr>
                                            <td>{{ $est->nombre }}</td>
                                            <td><span class="badge badge-secondary">{{ $est->cur_nombre ?? '-' }}</span></td>
                                            <td class="text-center font-weight-bold text-danger">{{ $est->promedio }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">Ningún estudiante en riesgo 🎉</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Próximos eventos de agenda --}}
            @if($proximosEventos->count())
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-calendar-alt mr-2"></i>Próximos Eventos (30 días)</h4></div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                @foreach($proximosEventos as $ev)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>
                                            <span class="badge {{ $ev->age_tipo == 1 ? 'badge-primary' : 'badge-warning' }} mr-2">{{ $ev->age_tipo == 1 ? 'Agenda' : 'Notificación' }}</span>
                                            {{ $ev->age_titulo }}
                                        </span>
                                        <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($ev->age_fechahora)->format('d/m/Y H:i') }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4"><h3 class="text-warning mb-0">{{ $estadoInscripciones['pendientes'] }}</h3><small class="text-muted">Activas</small></div>
                                <div class="col-4"><h3 class="text-success mb-0">{{ $estadoInscripciones['pagadas'] }}</h3><small class="text-muted">Pagadas</small></div>
                                <div class="col-4"><h3 class="text-danger mb-0">{{ $estadoInscripciones['anuladas'] }}</h3><small class="text-muted">Anuladas</small></div>
                            </div>
                        </div>
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
@endsection
