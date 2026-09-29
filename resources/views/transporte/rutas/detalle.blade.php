@php
    $asignacion = $ruta->asignaciones->where('asig_estado', 1)->first();
    $chofer = $asignacion ? $asignacion->chofer : null;
    $vehiculo = $asignacion ? $asignacion->vehiculo : null;
@endphp

<div class="row">
    <div class="col-md-6">
        <h5>Información de la Ruta</h5>
        <table class="table table-bordered">
            <tr>
                <th width="40%">Código:</th>
                <td>{{ $ruta->ruta_codigo }}</td>
            </tr>
            <tr>
                <th>Nombre:</th>
                <td>{{ $ruta->ruta_nombre }}</td>
            </tr>
            <tr>
                <th>Descripción:</th>
                <td>{{ $ruta->ruta_descripcion ?? '-' }}</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Conductor y Vehículo</h5>
        <table class="table table-bordered">
            <tr>
                <th width="40%">Conductor:</th>
                <td>{{ $chofer ? $chofer->chof_nombres . ' ' . $chofer->chof_apellidos : '-' }}</td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td>{{ $chofer->chof_telefono ?? '-' }}</td>
            </tr>
            <tr>
                <th>Vehículo:</th>
                <td>{{ $vehiculo ? $vehiculo->veh_marca . ' ' . $vehiculo->veh_modelo . ' - ' . $vehiculo->veh_placa : '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-end mt-3 mb-2">
    <h5 class="mb-0">Estudiantes Asignados</h5>
    {{-- Selector de mes: sin esto no se podía ver quién pagó un mes concreto --}}
    <div style="max-width:220px;">
        <label class="small text-muted mb-1">Mes del pago</label>
        <select class="form-control form-control-sm" onchange="cargarDetalleRuta({{ $ruta->ruta_id }}, this.value)">
            @foreach($meses as $m)
                <option value="{{ $m }}" {{ $mes === $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
            <option value="todos" {{ $mes === 'todos' ? 'selected' : '' }}>Todos (último pago)</option>
        </select>
    </div>
</div>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>
            <th>Curso</th>
            <th>Dirección</th>
            <th>Pago {{ $mes === 'todos' ? '(último)' : $mes }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ruta->estudiantes->where('ter_estado', 1) as $index => $er)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $er->estudiante->est_nombres }} {{ $er->estudiante->est_apellidos }}</td>
                <td>{{ $er->estudiante->curso->cur_nombre ?? '-' }}</td>
                <td>{{ $er->ter_direccion_recogida ?? '-' }}</td>
                @php
                    $montoMes = $mes === 'todos'
                        ? ($er->pago ? $er->pago->tpago_monto : 0)
                        : ($pagosMes[$er->est_codigo] ?? 0);
                @endphp
                <td>
                    @if($montoMes > 0)
                        Bs. {{ number_format($montoMes, 2) }}
                    @else
                        <span class="badge badge-danger">SIN PAGO</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No hay estudiantes asignados</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="table-info">
            <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
            @php
                $totalMes = $ruta->estudiantes->where('ter_estado', 1)->sum(function($er) use ($mes, $pagosMes) {
                    return $mes === 'todos'
                        ? ($er->pago ? $er->pago->tpago_monto : 0)
                        : ($pagosMes[$er->est_codigo] ?? 0);
                });
                $sinPago = $ruta->estudiantes->where('ter_estado', 1)->filter(function($er) use ($mes, $pagosMes) {
                    return $mes === 'todos'
                        ? !$er->pago
                        : !isset($pagosMes[$er->est_codigo]);
                })->count();
            @endphp
            <td>
                <strong>Bs. {{ number_format($totalMes, 2) }}</strong>
                @if($sinPago > 0)
                    <br><small class="text-danger">{{ $sinPago }} sin pago</small>
                @endif
            </td>
        </tr>
    </tfoot>
</table>
