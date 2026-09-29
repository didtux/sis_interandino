@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tasks mr-2"></i>Nueva Asignación</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('asignaciones-transporte.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Chofer *</label>
                                    <select name="chof_codigo" id="selChofer" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($choferes as $c)
                                            <option value="{{ $c->chof_codigo }}">{{ $c->chof_nombres }} {{ $c->chof_apellidos }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" class="form-check-input" id="chkNuevoChofer" name="nuevo_chofer" value="1">
                                        <label class="form-check-label small" for="chkNuevoChofer">Registrar un chofer nuevo acá mismo</label>
                                    </div>
                                </div>
                                {{-- Alta inline: antes había que ir al módulo Choferes y volver --}}
                                <div id="boxNuevoChofer" style="display:none;">
                                    <input type="text" name="chof_nombres"   class="form-control form-control-sm mb-1" placeholder="Nombres *">
                                    <input type="text" name="chof_apellidos" class="form-control form-control-sm mb-1" placeholder="Apellidos *">
                                    <input type="text" name="chof_ci"        class="form-control form-control-sm mb-1" placeholder="CI *">
                                    <input type="text" name="chof_licencia"  class="form-control form-control-sm mb-1" placeholder="Licencia *">
                                    <input type="text" name="chof_telefono"  class="form-control form-control-sm mb-1" placeholder="Teléfono">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vehículo *</label>
                                    <select name="veh_codigo" id="selVehiculo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($vehiculos as $v)
                                            <option value="{{ $v->veh_codigo }}">
                                                @if($v->veh_numero_bus) Bus {{ $v->veh_numero_bus }} - @endif{{ $v->veh_placa }} - {{ $v->veh_marca }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" class="form-check-input" id="chkNuevoVehiculo" name="nuevo_vehiculo" value="1">
                                        <label class="form-check-label small" for="chkNuevoVehiculo">Registrar un vehículo nuevo acá mismo</label>
                                    </div>
                                </div>
                                <div id="boxNuevoVehiculo" style="display:none;">
                                    <input type="text"   name="veh_numero_bus" class="form-control form-control-sm mb-1" placeholder="N° de bus">
                                    <input type="text"   name="veh_placa"      class="form-control form-control-sm mb-1" placeholder="Placa *">
                                    <input type="text"   name="veh_marca"      class="form-control form-control-sm mb-1" placeholder="Marca *">
                                    <input type="text"   name="veh_modelo"     class="form-control form-control-sm mb-1" placeholder="Modelo">
                                    <input type="number" name="veh_capacidad"  class="form-control form-control-sm mb-1" placeholder="Capacidad *" min="1">
                                    <input type="text"   name="veh_color"      class="form-control form-control-sm mb-1" placeholder="Color">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ruta *</label>
                                    <select name="ruta_codigo" id="ruta-select" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($rutas as $r)
                                            @php
                                                $asignacion = $r->asignaciones->where('asig_estado', 1)->first();
                                                $vehiculoInfo = '';
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    $vehiculoInfo = ' - ';
                                                    if ($asignacion->vehiculo->veh_numero_bus) {
                                                        $vehiculoInfo .= 'Bus ' . $asignacion->vehiculo->veh_numero_bus . ' - ';
                                                    }
                                                    $vehiculoInfo .= $asignacion->vehiculo->veh_marca . ' ' . $asignacion->vehiculo->veh_placa;
                                                }
                                            @endphp
                                            <option value="{{ $r->ruta_codigo }}" data-vehiculo="{{ $asignacion && $asignacion->vehiculo ? $asignacion->vehiculo->veh_codigo : '' }}">
                                                {{ $r->ruta_nombre }}{{ $vehiculoInfo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha Inicio *</label>
                                    <input type="date" name="asig_fecha_inicio" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha Fin</label>
                                    <input type="date" name="asig_fecha_fin" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="{{ route('asignaciones-transporte.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
/**
 * Alta inline de chofer y vehículo. Al marcar la casilla el <select> deja de
 * ser obligatorio y se piden los datos del nuevo registro; el backend lo crea
 * y lo asigna en la misma operación.
 */
function toggleAlta(chk, box, sel) {
    var on = $(chk).is(':checked');
    $(box).toggle(on);
    $(sel).prop('required', !on).prop('disabled', on);
    if (on) $(sel).val('').trigger('change');
    $(box).find('input').prop('required', false);
    if (on) {
        $(box).find('input[placeholder$="*"]').prop('required', true);
    }
}
$('#chkNuevoChofer').on('change',   function() { toggleAlta(this, '#boxNuevoChofer',   '#selChofer'); });
$('#chkNuevoVehiculo').on('change', function() { toggleAlta(this, '#boxNuevoVehiculo', '#selVehiculo'); });
</script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    // Auto-seleccionar vehículo cuando se selecciona una ruta
    $('#ruta-select').on('change', function() {
        var vehiculoCodigo = $(this).find(':selected').data('vehiculo');
        if (vehiculoCodigo) {
            $('select[name="veh_codigo"]').val(vehiculoCodigo).trigger('change');
        }
    });
});
</script>
@endsection
