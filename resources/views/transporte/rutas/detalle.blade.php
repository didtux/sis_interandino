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

<h5 class="mt-3">Estudiantes Asignados</h5>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>
            <th>Curso</th>
            <th>Dirección</th>
            <th>Monto Pagado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ruta->estudiantes->where('ter_estado', 1) as $index => $er)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $er->estudiante->est_nombres }} {{ $er->estudiante->est_apellidos }}</td>
                <td>{{ $er->estudiante->curso->cur_nombre ?? '-' }}</td>
                <td>{{ $er->ter_direccion_recogida ?? '-' }}</td>
                <td>Bs. {{ $er->pago ? number_format($er->pago->tpago_monto, 2) : '0.00' }}</td>
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
            <td><strong>Bs. {{ number_format($ruta->estudiantes->where('ter_estado', 1)->sum(function($er) { return $er->pago ? $er->pago->tpago_monto : 0; }), 2) }}</strong></td>
        </tr>
    </tfoot>
</table>
