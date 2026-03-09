@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-exclamation-triangle mr-2 text-warning"></i>Estudiantes en Mora</h4>
                    <a href="{{ route('pagos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Mes</label>
                                <select name="mes" class="form-control">
                                    @for($m = 2; $m <= 11; $m++)
                                        <option value="{{ $m }}" {{ $mesActual == $m ? 'selected' : '' }}>
                                            {{ $mesesNombres[$m] }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Curso</label>
                                <select name="cur_codigo" class="form-control select2">
                                    <option value="">Todos los cursos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="{{ route('pagos.mora') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                                <a href="{{ route('pagos.mora-pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Mostrando estudiantes que NO han pagado {{ $mesesNombres[$mesActual] }}</strong>
                        <br>Total: {{ $estudiantesEnMora->count() }} estudiante(s)
                    </div>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Monto Mensualidad</th>
                                    <th>Meses Pagados</th>
                                    <th>Meses Pendientes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estudiantesEnMora as $index => $estudiante)
                                    @php
                                        $mesesPagados = [];
                                        foreach($estudiante->pagos as $pago) {
                                            $mesesPagados = array_merge($mesesPagados, $pago->meses_cubiertos);
                                        }
                                        $mesesPagados = array_unique($mesesPagados);
                                        sort($mesesPagados);
                                        
                                        $mesesPendientes = [];
                                        for($m = 2; $m <= $mesActual; $m++) {
                                            if(!in_array($m, $mesesPagados)) {
                                                $mesesPendientes[] = $m;
                                            }
                                        }
                                        
                                        // Calcular monto mensualidad (con o sin inscripción)
                                        if ($estudiante->inscripcion) {
                                            $montoMensualidad = $estudiante->inscripcion->insc_monto_final / 10;
                                        } else {
                                            // Estimar monto basado en pagos anteriores (promedio)
                                            $montoMensualidad = $estudiante->pagos->count() > 0 
                                                ? $estudiante->pagos->sum('pagos_precio') / $estudiante->pagos->count() 
                                                : 475; // Monto por defecto
                                        }
                                    @endphp
                                    <tr>
                                        <td data-label="N°">{{ $index + 1 }}</td>
                                        <td data-label="Estudiante">
                                            <strong>{{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}</strong>
                                        </td>
                                        <td data-label="Curso">
                                            <span class="badge badge-info">{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</span>
                                        </td>
                                        <td data-label="Monto Mensualidad">
                                            <strong class="text-success">Bs. {{ number_format($montoMensualidad, 2) }}</strong>
                                        </td>
                                        <td data-label="Meses Pagados">
                                            @if(count($mesesPagados) > 0)
                                                @foreach($mesesPagados as $mes)
                                                    <span class="badge badge-success">{{ $mesesNombres[$mes] }}</span>
                                                @endforeach
                                            @else
                                                <span class="badge badge-secondary">Ninguno</span>
                                            @endif
                                        </td>
                                        <td data-label="Meses Pendientes">
                                            @foreach($mesesPendientes as $mes)
                                                <span class="badge badge-danger">{{ $mesesNombres[$mes] }}</span>
                                            @endforeach
                                        </td>
                                        <td data-label="Acciones">
                                            <a href="{{ route('pagos.create') }}?est_codigo={{ $estudiante->est_codigo }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-money-bill"></i> Registrar Pago
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <h5>No hay estudiantes en mora</h5>
                                                <p>Todos los estudiantes están al día con sus pagos de {{ $mesesNombres[$mesActual] }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endsection
