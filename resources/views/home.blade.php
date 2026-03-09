@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Dashboard - U.E. Privada Interandino Boliviano</h3>
    </div>
    <div class="section-body">
        <!-- Tarjetas de Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Total Estudiantes</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalEstudiantes }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Total Cursos</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalCursos }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Total Docentes</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalDocentes }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-info">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Inscripciones</h4>
                        </div>
                        <div class="card-body">
                            {{ $totalInscripciones }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Montos -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Inscripciones del Mes</h4>
                        </div>
                        <div class="card-body">
                            Bs. {{ number_format($inscripcionesMes, 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Saldo Pendiente</h4>
                        </div>
                        <div class="card-body">
                            Bs. {{ number_format($inscripcionesPendientes, 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Ventas del Mes</h4>
                        </div>
                        <div class="card-body">
                            Bs. {{ number_format($ventasMes, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asistencias de Hoy -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-clipboard-check mr-2"></i>Asistencias de Hoy</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center">
                                <h2 class="text-success">{{ $asistenciasHoy }}</h2>
                                <p>Asistencias Registradas</p>
                            </div>
                            <div class="col-6 text-center">
                                <h2 class="text-warning">{{ $atrasosHoy }}</h2>
                                <p>Atrasos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-pie mr-2"></i>Estado de Inscripciones</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="chartEstadoInscripciones" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-line mr-2"></i>Inscripciones por Mes</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="chartInscripciones" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-bar mr-2"></i>Estudiantes por Curso</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="chartEstudiantes" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-bar mr-2"></i>Ventas por Categoría (Últimos 30 días)</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="chartVentas" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Gráfico: Estado de Inscripciones
var ctxEstado = document.getElementById('chartEstadoInscripciones').getContext('2d');
new Chart(ctxEstado, {
    type: 'doughnut',
    data: {
        labels: ['Pendientes', 'Pagadas', 'Canceladas'],
        datasets: [{
            data: [{{ $estadoInscripciones['pendientes'] }}, {{ $estadoInscripciones['pagadas'] }}, {{ $estadoInscripciones['canceladas'] }}],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico: Inscripciones por Mes
var ctxInscripciones = document.getElementById('chartInscripciones').getContext('2d');
var meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
var dataMeses = new Array(12).fill(0);
@foreach($inscripcionesPorMes as $item)
    dataMeses[{{ $item->mes - 1 }}] = {{ $item->total }};
@endforeach

new Chart(ctxInscripciones, {
    type: 'line',
    data: {
        labels: meses,
        datasets: [{
            label: 'Inscripciones',
            data: dataMeses,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico: Estudiantes por Curso
var ctxEstudiantes = document.getElementById('chartEstudiantes').getContext('2d');
new Chart(ctxEstudiantes, {
    type: 'bar',
    data: {
        labels: [@foreach($estudiantesPorCurso as $curso)'{{ $curso->cur_nombre }}',@endforeach],
        datasets: [{
            label: 'Estudiantes',
            data: [@foreach($estudiantesPorCurso as $curso){{ $curso->estudiantes_count }},@endforeach],
            backgroundColor: '#28a745'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico: Ventas por Categoría
var ctxVentas = document.getElementById('chartVentas').getContext('2d');
new Chart(ctxVentas, {
    type: 'bar',
    data: {
        labels: [@foreach($ventasPorCategoria as $venta)'{{ $venta->categ_nombre }}',@endforeach],
        datasets: [{
            label: 'Ventas (Bs.)',
            data: [@foreach($ventasPorCategoria as $venta){{ $venta->total }},@endforeach],
            backgroundColor: '#17a2b8'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endsection
@endsection
