@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Nuevo Producto</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('productos.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="prod_nombre" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Categoría *</label>
                                    <select name="categ_codigo" class="form-control select2" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($categorias as $c)
                                            <option value="{{ $c->categ_codigo }}">{{ $c->categ_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item/SKU</label>
                                    <div class="input-group">
                                        <input type="text" name="prod_item" id="prod_item" class="form-control" placeholder="Escanee el código de barras aquí">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" onclick="focusBarcode()">
                                                <i class="fas fa-barcode"></i> Escanear
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Haga clic en el campo y escanee el código de barras</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cantidad *</label>
                                    <input type="number" name="prod_cantidad" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio Unitario *</label>
                                    <input type="number" step="0.01" name="prod_preciounitario" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio con Descuento</label>
                                    <input type="number" step="0.01" name="prod_preciodescuento" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Detalles</label>
                                    <textarea name="prod_detalles" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
});

function focusBarcode() {
    $('#prod_item').focus();
}

// Auto-focus en el campo de código de barras al cargar la página
$(document).ready(function() {
    $('#prod_item').focus();
    
    // Detectar escaneo de código de barras (entrada rápida seguida de Enter)
    let barcodeBuffer = '';
    let barcodeTimeout;
    
    $('#prod_item').on('keypress', function(e) {
        clearTimeout(barcodeTimeout);
        
        if (e.which === 13) { // Enter
            e.preventDefault();
            if (barcodeBuffer.length > 0) {
                $(this).val(barcodeBuffer);
                $(this).addClass('is-valid');
                // Mover al siguiente campo
                $('input[name="prod_cantidad"]').focus();
                barcodeBuffer = '';
            }
        } else {
            barcodeBuffer += String.fromCharCode(e.which);
            
            // Limpiar buffer después de 100ms (detecta entrada manual vs escáner)
            barcodeTimeout = setTimeout(function() {
                barcodeBuffer = '';
            }, 100);
        }
    });
    
    // Permitir entrada manual normal
    $('#prod_item').on('input', function() {
        if ($(this).val().length > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
});
</script>
@endsection
@endsection
