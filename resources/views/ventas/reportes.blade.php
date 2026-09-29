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
                                            <label>Categoría</label>
                                            <select name="categ_codigo" class="form-control select2">
                                                <option value="">Todas / no aplica</option>
                                                @foreach($categorias ?? [] as $cat)
                                                    <option value="{{ $cat->categ_codigo }}">{{ $cat->categ_nombre }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Si elegís sólo la categoría, el reporte sale con el total y el desglose por producto.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Producto</label>
                                            <select name="prod_codigo" class="form-control select2">
                                                <option value="">Todos los de la categoría</option>
                                                @foreach($productos as $p)
                                                    <option value="{{ $p->prod_codigo }}" data-categ="{{ $p->categ_codigo }}">{{ $p->prod_nombre }}</option>
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
                                        <div class="form-group">
                                            <div class="btn-group btn-group-sm btn-block" role="group">
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formProducto','hoy')">Hoy</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formProducto','mes')">Este mes</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formProducto','gestion')">Gestión</button>
                                            </div>
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
                                        <div class="form-group">
                                            <div class="btn-group btn-group-sm btn-block" role="group">
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formArqueo','hoy')">Hoy</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formArqueo','mes')">Este mes</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="presetVentas('formArqueo','gestion')">Gestión</button>
                                            </div>
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

/** Presets de rango Hoy / Este mes / Gestión sobre cualquiera de los dos formularios. */
function presetVentas(formId, tipo) {
    const form = document.getElementById(formId);
    const hoy = new Date();
    const p = n => ('0' + n).slice(-2);
    const fmt = d => d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    let desde, hasta = fmt(hoy);

    if (tipo === 'hoy')      desde = fmt(hoy);
    else if (tipo === 'mes') desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
    else { desde = hoy.getFullYear() + '-01-01'; hasta = hoy.getFullYear() + '-12-31'; }

    form.querySelector('[name="fecha_inicio"]').value = desde;
    form.querySelector('[name="fecha_fin"]').value = hasta;
}

/** Al elegir categoría, la lista de productos se limita a esa categoría. */
$('select[name="categ_codigo"]').on('change', function() {
    const cat = $(this).val();
    const $prod = $('select[name="prod_codigo"]');
    $prod.val('').trigger('change');
    $prod.find('option').each(function() {
        if (!$(this).val()) return;
        $(this).prop('disabled', cat && $(this).data('categ') != cat);
    });
});

function generarReporteProducto(formato = 'pdf') {
    const form = document.getElementById('formProducto');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    if (!form.prod_codigo.value && !form.categ_codigo.value) {
        swal('Falta un filtro', 'Elegí un producto o una categoría.', 'warning');
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
