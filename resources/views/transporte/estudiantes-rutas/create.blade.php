@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-users mr-2"></i>Asignar Estudiante a Ruta</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('estudiantes-rutas.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estudiante *</label>
                                    <select name="est_codigo" id="estudiante-select" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($estudiantes as $e)
                                            @php
                                                $ultimoPago = $e->pagosTransporte()->orderBy('tpago_fecha_registro', 'desc')->first();
                                                $pagosData = $ultimoPago ? json_encode([
                                                    'tpago_codigo' => $ultimoPago->tpago_codigo,
                                                    'tpago_tipo' => $ultimoPago->tpago_tipo,
                                                    'tpago_monto' => $ultimoPago->tpago_monto,
                                                    'tpago_estado' => $ultimoPago->tpago_estado,
                                                    'tpago_fecha_inicio' => $ultimoPago->tpago_fecha_inicio,
                                                    'tpago_fecha_fin' => $ultimoPago->tpago_fecha_fin,
                                                    'vigente' => $ultimoPago->tpago_estado == 'vigente'
                                                ]) : '';
                                            @endphp
                                            <option value="{{ $e->est_codigo }}" data-pago='{{ $pagosData }}'>
                                                {{ $e->est_nombres }} {{ $e->est_apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ruta/Bus *</label>
                                    <select name="ruta_codigo" id="ruta-select" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($rutas as $r)
                                            @php
                                                $asignacion = $r->asignaciones->where('asig_estado', 1)->first();
                                                $vehiculoInfo = '';
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    if ($asignacion->vehiculo->veh_numero_bus) {
                                                        $vehiculoInfo = ' - Bus ' . $asignacion->vehiculo->veh_numero_bus;
                                                    }
                                                    $vehiculoInfo .= ' - ' . $asignacion->vehiculo->veh_marca . ' ' . $asignacion->vehiculo->veh_placa;
                                                }
                                            @endphp
                                            <option value="{{ $r->ruta_codigo }}">
                                                {{ $r->ruta_nombre }}{{ $vehiculoInfo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="info-pago" class="alert alert-info" style="display:none;">
                            <h6><i class="fas fa-info-circle"></i> Información del Último Pago</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tipo:</strong> <span id="pago-tipo"></span><br>
                                    <strong>Monto:</strong> Bs. <span id="pago-monto"></span><br>
                                    <strong>Estado:</strong> <span id="pago-estado-badge"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Vigencia:</strong> <span id="pago-vigencia"></span><br>
                                    <strong>Fecha Inicio:</strong> <span id="pago-inicio"></span><br>
                                    <strong>Fecha Fin:</strong> <span id="pago-fin"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Pago de Transporte *</label>
                                    <select name="tpago_codigo" id="pago-select" class="form-control select2" required>
                                        <option value="">Primero seleccione estudiante...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección de Recogida</label>
                            <input type="text" name="ter_direccion_recogida" class="form-control" placeholder="Ej: Av. 6 de Agosto #123">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="{{ route('estudiantes-rutas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var todosLosPagos = @json($pagos->groupBy('est_codigo'));

$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    $('#estudiante-select').on('change', function() {
        var estCodigo = $(this).val();
        var pagoData = $(this).find(':selected').data('pago');
        
        if (estCodigo && pagoData) {
            // Mostrar información del último pago
            $('#pago-tipo').text(pagoData.tpago_tipo.charAt(0).toUpperCase() + pagoData.tpago_tipo.slice(1));
            $('#pago-monto').text(parseFloat(pagoData.tpago_monto).toFixed(2));
            $('#pago-inicio').text(pagoData.tpago_fecha_inicio);
            $('#pago-fin').text(pagoData.tpago_fecha_fin);
            
            var estadoBadge = '';
            var vigenciaTexto = '';
            if (pagoData.tpago_estado === 'vigente') {
                estadoBadge = '<span class="badge badge-success">Vigente</span>';
                vigenciaTexto = '<span class="text-success"><i class="fas fa-check-circle"></i> Pago vigente</span>';
            } else if (pagoData.tpago_estado === 'vencido') {
                estadoBadge = '<span class="badge badge-danger">Vencido</span>';
                vigenciaTexto = '<span class="text-danger"><i class="fas fa-times-circle"></i> Pago vencido</span>';
            } else {
                estadoBadge = '<span class="badge badge-secondary">Cancelado</span>';
                vigenciaTexto = '<span class="text-muted"><i class="fas fa-ban"></i> Pago cancelado</span>';
            }
            
            $('#pago-estado-badge').html(estadoBadge);
            $('#pago-vigencia').html(vigenciaTexto);
            $('#info-pago').show();
            
            // Cargar pagos del estudiante en el select
            var pagosEstudiante = todosLosPagos[estCodigo] || [];
            $('#pago-select').empty().append('<option value="">Seleccione pago...</option>');
            
            if (pagosEstudiante.length > 0) {
                pagosEstudiante.forEach(function(pago) {
                    var selected = pago.tpago_codigo === pagoData.tpago_codigo ? 'selected' : '';
                    var estadoClass = pago.tpago_estado === 'vigente' ? 'text-success' : (pago.tpago_estado === 'vencido' ? 'text-danger' : 'text-muted');
                    $('#pago-select').append(
                        '<option value="' + pago.tpago_codigo + '" ' + selected + ' class="' + estadoClass + '">' +
                        pago.tpago_tipo.charAt(0).toUpperCase() + pago.tpago_tipo.slice(1) + ' - ' +
                        'Bs. ' + parseFloat(pago.tpago_monto).toFixed(2) + ' - ' +
                        pago.tpago_estado.charAt(0).toUpperCase() + pago.tpago_estado.slice(1) +
                        '</option>'
                    );
                });
            } else {
                $('#pago-select').append('<option value="">No hay pagos registrados</option>');
            }
            
            $('#pago-select').trigger('change');
        } else {
            $('#info-pago').hide();
            $('#pago-select').empty().append('<option value="">Primero seleccione estudiante...</option>');
        }
    });
});
</script>
@endsection
