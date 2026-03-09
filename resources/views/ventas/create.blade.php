@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-shopping-cart mr-2"></i>Nueva Venta</h4>
                </div>
                <div class="card-body">
                    <form id="form-venta">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cliente <span class="text-danger">*</span></label>
                                    <input type="text" id="ven_cliente" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Celular</label>
                                    <input type="text" id="ven_celular" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" id="ven_direccion" class="form-control">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Agregar Productos</h5>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><i class="fas fa-barcode"></i> Escanear Código de Barras</label>
                                    <div class="input-group">
                                        <input type="text" id="barcode-input" class="form-control form-control-lg" placeholder="Escanee el código de barras aquí..." autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" onclick="focusBarcode()">
                                                <i class="fas fa-barcode"></i> Listo para escanear
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">El producto se agregará automáticamente al escanear</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select id="producto-select" class="form-control select2" multiple>
                                        @foreach($productos as $p)
                                            <option value="{{ $p->prod_codigo }}" 
                                                data-precio="{{ $p->prod_preciounitario }}" 
                                                data-stock="{{ $p->prod_cantidad }}"
                                                data-nombre="{{ $p->prod_nombre }}"
                                                data-item="{{ $p->prod_item }}">
                                                {{ $p->prod_nombre }} (Stock: {{ $p->prod_cantidad }}) - Bs. {{ number_format($p->prod_preciounitario, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Seleccione múltiples productos y presione "Agregar Seleccionados"</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" id="cantidad" class="form-control" min="1" value="1">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <select id="tipo" class="form-control">
                                        <option value="venta">Venta</option>
                                        <option value="prestamo">Préstamo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-success btn-block" onclick="agregarSeleccionados()">
                                        <i class="fas fa-plus"></i> Agregar Seleccionados
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="text-white">Producto</th>
                                        <th class="text-white">Cantidad</th>
                                        <th class="text-white">Precio Unit.</th>
                                        <th class="text-white">Tipo</th>
                                        <th class="text-white">Subtotal</th>
                                        <th class="text-white">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="lista-productos">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay productos agregados</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                                        <td colspan="2"><strong>Bs. <span id="total-venta">0.00</span></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="form-group mt-3">
                            <button type="button" class="btn btn-primary" onclick="confirmarVenta()">
                                <i class="fas fa-save"></i> Registrar Venta
                            </button>
                            <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let productosVenta = [];

function agregarSeleccionados() {
    const select = document.getElementById('producto-select');
    const selectedOptions = Array.from(select.selectedOptions);
    
    if (selectedOptions.length === 0) {
        alert('Seleccione al menos un producto');
        return;
    }
    
    const cantidad = parseInt(document.getElementById('cantidad').value);
    const tipo = document.getElementById('tipo').value;
    
    selectedOptions.forEach(option => {
        const stock = parseInt(option.dataset.stock);
        
        if (cantidad > stock) {
            alert(`Stock insuficiente para ${option.dataset.nombre}. Disponible: ${stock}`);
            return;
        }
        
        const producto = {
            prod_codigo: option.value,
            prod_nombre: option.dataset.nombre,
            cantidad: cantidad,
            precio: parseFloat(option.dataset.precio),
            tipo: tipo,
            subtotal: cantidad * parseFloat(option.dataset.precio)
        };
        
        productosVenta.push(producto);
    });
    
    actualizarTabla();
    
    // Limpiar selección pero mantener el select abierto
    $('#producto-select').val(null).trigger('change');
    document.getElementById('cantidad').value = 1;
}

function agregarProducto() {
    const select = document.getElementById('producto-select');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        alert('Seleccione un producto');
        return;
    }
    
    const cantidad = parseInt(document.getElementById('cantidad').value);
    const stock = parseInt(option.dataset.stock);
    
    if (cantidad > stock) {
        alert('Stock insuficiente. Disponible: ' + stock);
        return;
    }
    
    const producto = {
        prod_codigo: option.value,
        prod_nombre: option.dataset.nombre || option.text.split(' (Stock:')[0],
        cantidad: cantidad,
        precio: parseFloat(option.dataset.precio),
        tipo: document.getElementById('tipo').value,
        subtotal: cantidad * parseFloat(option.dataset.precio)
    };
    
    productosVenta.push(producto);
    actualizarTabla();
    
    $('#producto-select').val('').trigger('change');
    document.getElementById('cantidad').value = 1;
}

function eliminarProducto(index) {
    productosVenta.splice(index, 1);
    actualizarTabla();
}

function actualizarTabla() {
    const tbody = document.getElementById('lista-productos');
    tbody.innerHTML = '';
    
    if (productosVenta.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay productos agregados</td></tr>';
        document.getElementById('total-venta').textContent = '0.00';
        return;
    }
    
    let total = 0;
    productosVenta.forEach((p, index) => {
        total += p.subtotal;
        tbody.innerHTML += `
            <tr>
                <td>${p.prod_nombre}</td>
                <td>${p.cantidad}</td>
                <td>Bs. ${p.precio.toFixed(2)}</td>
                <td><span class="badge badge-${p.tipo === 'venta' ? 'success' : 'warning'}">${p.tipo}</span></td>
                <td>Bs. ${p.subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    document.getElementById('total-venta').textContent = total.toFixed(2);
}

function confirmarVenta() {
    const cliente = document.getElementById('ven_cliente').value;
    
    if (!cliente) {
        alert('Ingrese el nombre del cliente');
        return;
    }
    
    if (productosVenta.length === 0) {
        alert('Agregue al menos un producto');
        return;
    }
    
    const total = productosVenta.reduce((sum, p) => sum + p.subtotal, 0);
    
    if (confirm(`¿Confirmar venta por Bs. ${total.toFixed(2)}?`)) {
        guardarVenta();
    }
}

function guardarVenta() {
    const data = {
        ven_cliente: document.getElementById('ven_cliente').value,
        ven_celular: document.getElementById('ven_celular').value,
        ven_direccion: document.getElementById('ven_direccion').value,
        productos: productosVenta,
        _token: '{{ csrf_token() }}'
    };
    
    fetch('{{ route("ventas.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Venta registrada exitosamente');
            window.location.href = '{{ route("ventas.index") }}';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error al registrar la venta');
        console.error(error);
    });
}
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#producto-select').select2({
        placeholder: 'Buscar productos...',
        width: '100%',
        closeOnSelect: false,
        allowClear: true
    });
    
    // Auto-focus en el campo de código de barras
    $('#barcode-input').focus();
    
    // Detectar escaneo de código de barras
    let barcodeBuffer = '';
    let barcodeTimeout;
    
    $('#barcode-input').on('keypress', function(e) {
        clearTimeout(barcodeTimeout);
        
        if (e.which === 13) { // Enter
            e.preventDefault();
            const barcode = $(this).val().trim();
            
            if (barcode.length > 0) {
                buscarYAgregarProducto(barcode);
                $(this).val('');
            }
            barcodeBuffer = '';
        } else {
            barcodeBuffer += String.fromCharCode(e.which);
            
            barcodeTimeout = setTimeout(function() {
                barcodeBuffer = '';
            }, 100);
        }
    });
});

function focusBarcode() {
    $('#barcode-input').focus();
}

function buscarYAgregarProducto(barcode) {
    // Buscar producto por código de barras (prod_item)
    let productoEncontrado = false;
    
    $('#producto-select option').each(function() {
        const option = $(this);
        const prodCodigo = option.val();
        
        if (prodCodigo) {
            // Hacer petición AJAX para verificar el prod_item
            $.ajax({
                url: '/api/producto-por-barcode/' + barcode,
                method: 'GET',
                async: false,
                success: function(data) {
                    if (data.found && data.prod_codigo === prodCodigo) {
                        productoEncontrado = true;
                        
                        // Seleccionar el producto
                        $('#producto-select').val(prodCodigo).trigger('change');
                        
                        // Agregar automáticamente
                        setTimeout(function() {
                            agregarProducto();
                            $('#barcode-input').addClass('is-valid').removeClass('is-invalid');
                            
                            // Mostrar notificación
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Producto agregado'
                            });
                            
                            setTimeout(function() {
                                $('#barcode-input').removeClass('is-valid');
                                $('#barcode-input').focus();
                            }, 500);
                        }, 100);
                        
                        return false;
                    }
                }
            });
        }
    });
    
    if (!productoEncontrado) {
        $('#barcode-input').addClass('is-invalid').removeClass('is-valid');
        
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        Toast.fire({
            icon: 'error',
            title: 'Producto no encontrado'
        });
        
        setTimeout(function() {
            $('#barcode-input').removeClass('is-invalid');
            $('#barcode-input').focus();
        }, 1000);
    }
}
</script>
@endpush
