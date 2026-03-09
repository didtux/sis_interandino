@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background-color: #6777ef;">
                    <h4 style="color: white; margin: 0;"><i class="fas fa-chart-bar mr-2"></i>Reportes Especiales de Ventas</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Reporte por Producto -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5><i class="fas fa-box"></i> Venta por Producto</h5>
                                </div>
                                <div class="card-body">
                                    <form id="formProducto" method="GET" target="_blank">
                                        <div class="form-group">
                                            <label>Producto *</label>
                                            <select name="prod_codigo" class="form-control select2" required>
                                                <option value="">Seleccione un producto...</option>
                                                @foreach($productos as $p)
                                                    <option value="{{ $p->prod_codigo }}">{{ $p->prod_nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha Inicio</label>
                                            <input type="date" name="fecha_inicio" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha Fin</label>
                                            <input type="date" name="fecha_fin" class="form-control">
                                        </div>
                                        <button type="button" class="btn btn-danger btn-block" onclick="generarReporteProducto('pdf')">
                                            <i class="fas fa-file-pdf"></i> Generar PDF
                                        </button>
                                        <button type="button" class="btn btn-info btn-block mt-2" onclick="generarReporteProducto('termica')">
                                            <i class="fas fa-receipt"></i> Formato Térmico
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reporte Arqueo Semanal -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5><i class="fas fa-warehouse"></i> Arqueo Semanal de Almacén</h5>
                                </div>
                                <div class="card-body">
                                    <form id="formArqueo" method="GET" target="_blank">
                                        <div class="form-group">
                                            <label>Fecha Inicio *</label>
                                            <input type="date" name="fecha_inicio" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha Fin *</label>
                                            <input type="date" name="fecha_fin" class="form-control" required>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-block" onclick="generarReporteArqueo('pdf')">
                                            <i class="fas fa-file-pdf"></i> Generar PDF
                                        </button>
                                        <button type="button" class="btn btn-info btn-block mt-2" onclick="generarReporteArqueo('termica')">
                                            <i class="fas fa-receipt"></i> Formato Térmico
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: 'Seleccione...',
    allowClear: true
});

function generarReporteProducto(formato = 'pdf') {
    const form = document.getElementById('formProducto');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    swal({
        title: 'Generando reporte...',
        text: 'Por favor espere',
        icon: 'info',
        buttons: false,
        closeOnClickOutside: false
    });
    
    const params = new URLSearchParams(new FormData(form));
    params.append('formato', formato);
    window.open('{{ route("ventas.reporte-producto-pdf") }}?' + params.toString(), '_blank');
    
    setTimeout(() => swal.close(), 2000);
}

function generarReporteArqueo(formato = 'pdf') {
    const form = document.getElementById('formArqueo');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    swal({
        title: 'Generando reporte...',
        text: 'Por favor espere',
        icon: 'info',
        buttons: false,
        closeOnClickOutside: false
    });
    
    const params = new URLSearchParams(new FormData(form));
    params.append('formato', formato);
    window.open('{{ route("ventas.reporte-arqueo-pdf") }}?' + params.toString(), '_blank');
    
    setTimeout(() => swal.close(), 2000);
}
</script>
@endsection
@endsection
