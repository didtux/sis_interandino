@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-box mr-2"></i>Productos</h4>
                    <div>
                        @puede('productos', 'crear')
                        <a href="{{ route('productos.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                        @endpuede
                        <button class="btn btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" id="searchProducto" class="form-control" placeholder="Buscar producto...">
                        </div>
                        <div class="col-md-3">
                            <select id="filterCategoria" class="form-control select2-categoria">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $c)
                                    <option value="{{ $c->categ_nombre }}">{{ $c->categ_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table class="table table-striped" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Descuento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $p)
                                <tr>
                                    <td>{{ $p->prod_codigo }}</td>
                                    <td><strong>{{ $p->prod_nombre }}</strong></td>
                                    <td><span class="badge badge-info">{{ $p->categoria->categ_nombre ?? 'N/A' }}</span></td>
                                    <td>
                                        <span class="badge badge-{{ $p->prod_cantidad > 10 ? 'success' : 'warning' }}">
                                            {{ $p->prod_cantidad }}
                                        </span>
                                    </td>
                                    <td>Bs. {{ number_format($p->prod_preciounitario, 2) }}</td>
                                    <td>Bs. {{ number_format($p->prod_preciodescuento, 2) }}</td>
                                    <td>
                                        <a href="{{ route('productos.etiqueta', $p->prod_id) }}" class="btn btn-sm btn-info" target="_blank" title="Imprimir Etiqueta">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                        @puede('productos', 'editar')
                                        <a href="{{ route('productos.edit', $p->prod_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endpuede
                                        @puede('productos', 'eliminar')
                                        <form action="{{ route('productos.destroy', $p->prod_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endpuede
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No hay productos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $productos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-categoria').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una categoría'
    });
});

$('#searchProducto, #filterCategoria').on('keyup change', function() {
    var searchText = $('#searchProducto').val().toLowerCase();
    var categoria = $('#filterCategoria').val().toLowerCase();
    
    $('#tablaProductos tbody tr').filter(function() {
        var text = $(this).text().toLowerCase();
        var catText = $(this).find('td:eq(2)').text().toLowerCase();
        var matchSearch = text.indexOf(searchText) > -1;
        var matchCategoria = categoria === '' || catText.indexOf(categoria) > -1;
        $(this).toggle(matchSearch && matchCategoria);
    });
});

function exportarExcel() {
    var wb = XLSX.utils.table_to_book(document.getElementById('tablaProductos'));
    XLSX.writeFile(wb, 'productos_' + new Date().getTime() + '.xlsx');
}

function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Logo (si existe)
    const logoPath = '{{ asset("img/logo.png") }}';
    const img = new Image();
    img.src = logoPath;
    
    img.onload = function() {
        // Agregar logo
        doc.addImage(img, 'PNG', 14, 10, 20, 20);
        
        // Información de la institución
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('UNIDAD EDUCATIVA PRIVADA', 40, 15);
        doc.text('INTERANDINO BOLIVIANO', 40, 20);
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text('Dir. Calle Victor Gutierrez Nro 3339', 40, 25);
        doc.text('Teléfonos: 2840320', 40, 29);
        
        // Fecha en la esquina superior derecha
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('Fecha', 185, 12);
        doc.setFontSize(10);
        doc.text(new Date().toLocaleDateString('es-BO'), 175, 17);
        doc.setFontSize(7);
        doc.setFont(undefined, 'italic');
        doc.text('Saib', 185, 21);
        
        // Título del reporte
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text('REPORTE DE PRODUCTOS', 105, 40, { align: 'center' });
        
        // Información del usuario
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 14, 48);
        doc.text('Fecha y hora de impresión: ' + new Date().toLocaleDateString('es-BO') + ' ' + new Date().toLocaleTimeString('es-BO'), 14, 53);
        
        // Tabla
        doc.autoTable({ 
            html: '#tablaProductos',
            startY: 58,
            columns: [
                { header: 'Código', dataKey: 0 },
                { header: 'Nombre', dataKey: 1 },
                { header: 'Categoría', dataKey: 2 },
                { header: 'Stock', dataKey: 3 },
                { header: 'Precio', dataKey: 4 },
                { header: 'Descuento', dataKey: 5 }
            ],
            headStyles: { 
                fillColor: [240, 240, 240],
                textColor: [0, 0, 0],
                fontStyle: 'bold',
                lineWidth: 0.5,
                lineColor: [0, 0, 0]
            },
            styles: {
                fontSize: 8,
                cellPadding: 2,
                lineWidth: 0.5,
                lineColor: [0, 0, 0]
            },
            didParseCell: function(data) {
                // Excluir la columna de acciones (índice 6)
                if (data.column.index === 6) {
                    data.cell.text = [];
                }
            }
        });
        
        // Footer
        const pageCount = doc.internal.getNumberOfPages();
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        for(let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.text('Página ' + i + ' de ' + pageCount, 105, 285, { align: 'center' });
        }
        
        doc.save('productos_' + new Date().getTime() + '.pdf');
    };
    
    img.onerror = function() {
        // Si no se puede cargar el logo, generar PDF sin él
        generarPDFSinLogo(doc);
    };
}

function generarPDFSinLogo(doc) {
    // Información de la institución sin logo
    doc.setFontSize(10);
    doc.setFont(undefined, 'bold');
    doc.text('UNIDAD EDUCATIVA PRIVADA', 14, 15);
    doc.text('INTERANDINO BOLIVIANO', 14, 20);
    doc.setFontSize(8);
    doc.setFont(undefined, 'normal');
    doc.text('Dir. Calle Victor Gutierrez Nro 3339', 14, 25);
    doc.text('Teléfonos: 2840320', 14, 29);
    
    // Fecha
    doc.setFontSize(8);
    doc.setFont(undefined, 'bold');
    doc.text('Fecha', 185, 12);
    doc.setFontSize(10);
    doc.text(new Date().toLocaleDateString('es-BO'), 175, 17);
    
    // Título
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('REPORTE DE PRODUCTOS', 105, 40, { align: 'center' });
    
    // Usuario
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 14, 48);
    doc.text('Fecha y hora: ' + new Date().toLocaleDateString('es-BO') + ' ' + new Date().toLocaleTimeString('es-BO'), 14, 53);
    
    // Tabla
    doc.autoTable({ 
        html: '#tablaProductos',
        startY: 58,
        columns: [
            { header: 'Código', dataKey: 0 },
            { header: 'Nombre', dataKey: 1 },
            { header: 'Categoría', dataKey: 2 },
            { header: 'Stock', dataKey: 3 },
            { header: 'Precio', dataKey: 4 },
            { header: 'Descuento', dataKey: 5 }
        ],
        headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
        styles: { fontSize: 8 },
        didParseCell: function(data) {
            if (data.column.index === 6) {
                data.cell.text = [];
            }
        }
    });
    
    doc.save('productos_' + new Date().getTime() + '.pdf');
}
</script>
@endsection
@endsection
