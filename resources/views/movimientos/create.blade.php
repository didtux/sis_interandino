@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-exchange-alt mr-2"></i>Nuevo Movimiento de Almacén</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('movimientos.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Producto <span class="text-danger">*</span></label>
                                    <select name="prod_codigo" id="prod_codigo" class="form-control select2" required>
                                        <option value="">Seleccione un producto</option>
                                        @foreach($productos as $prod)
                                            <option value="{{ $prod->prod_codigo }}" data-stock="{{ $prod->prod_cantidad }}">
                                                {{ $prod->prod_nombre }} (Stock: {{ $prod->prod_cantidad }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <select name="prov_codigo" class="form-control select2">
                                        <option value="">Seleccione un proveedor</option>
                                        @foreach($proveedores as $prov)
                                            <option value="{{ $prov->prov_codigo }}">{{ $prov->prov_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo de Movimiento <span class="text-danger">*</span></label>
                                    <select name="mov_tipo" id="mov_tipo" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="entrada">Entrada (Compra)</option>
                                        <option value="salida">Salida (Consumo)</option>
                                        <option value="ajuste">Ajuste de Inventario</option>
                                        <option value="devolucion">Devolución</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cantidad <span class="text-danger">*</span></label>
                                    <input type="number" name="mov_cantidad" id="mov_cantidad" class="form-control" min="1" required>
                                    <small class="text-muted">Stock actual: <span id="stock_actual">0</span></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <input type="number" step="0.01" name="mov_precio_unitario" id="mov_precio_unitario" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Motivo <span class="text-danger">*</span></label>
                                    <input type="text" name="mov_motivo" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Observación</label>
                                    <textarea name="mov_observacion" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Registrar Movimiento
                        </button>
                        <a href="{{ route('movimientos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    $('#prod_codigo').on('change', function() {
        var stock = $(this).find(':selected').data('stock');
        $('#stock_actual').text(stock);
    });

    $('#mov_tipo').on('change', function() {
        if($(this).val() == 'salida') {
            $('#mov_cantidad').attr('max', $('#stock_actual').text());
        } else {
            $('#mov_cantidad').removeAttr('max');
        }
    });
});
</script>
@endsection
@endsection
