@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-shopping-cart mr-2"></i>Ventas</h4>
                    <a href="{{ route('ventas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Venta
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Producto</label>
                                <select name="prod_codigo" id="producto-filter" class="form-control select2">
                                    <option value="">Todos los productos</option>
                                    @foreach($productos as $p)
                                        <option value="{{ $p->prod_codigo }}" {{ request('prod_codigo') == $p->prod_codigo ? 'selected' : '' }}>
                                            {{ $p->prod_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Cliente</label>
                                <input type="text" name="cliente" class="form-control" value="{{ request('cliente') }}" placeholder="Buscar cliente...">
                            </div>
                            <div class="col-md-2">
                                <label>Tipo</label>
                                <select name="tipo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="venta" {{ request('tipo') == 'venta' ? 'selected' : '' }}>Venta</option>
                                    <option value="prestamo" {{ request('tipo') == 'prestamo' ? 'selected' : '' }}>Préstamo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="completado" {{ request('estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                                    <option value="anulado" {{ request('estado') == 'anulado' ? 'selected' : '' }}>Anulado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                                <a href="{{ route('ventas.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                                <a href="{{ route('ventas.reportes') }}" class="btn btn-info"><i class="fas fa-chart-bar"></i> Reportes Especiales</a>
                                <a href="{{ route('ventas.reporte-pdf', request()->all()) }}" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                                <a href="{{ route('ventas.reporte-excel', request()->all()) }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</a>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped" id="tablaVentas">
                        <thead>
                            <tr>
                                <th width="50"></th>
                                <th>Código Venta</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalGeneral = 0; @endphp
                            @forelse($ventasAgrupadas as $venta)
                                @php $totalGeneral += $venta['total']; @endphp
                                <tr data-toggle="collapse" data-target="#detalle-{{ $venta['ven_codigo'] }}" style="cursor: pointer;">
                                    <td><i class="fas fa-chevron-down"></i></td>
                                    <td><strong>{{ $venta['ven_codigo'] }}</strong></td>
                                    <td>{{ $venta['ven_cliente'] }}</td>
                                    <td><span class="badge badge-info">{{ $venta['cantidad_productos'] }} producto(s)</span></td>
                                    <td><strong>Bs. {{ number_format($venta['total'], 2) }}</strong></td>
                                    <td><span class="badge badge-{{ $venta['venta_tipo'] == 'venta' ? 'success' : 'warning' }}">{{ ucfirst($venta['venta_tipo']) }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($venta['venta_fecha'])->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $venta['venta_estado'] == 'completado' ? 'success' : 'danger' }}">
                                            {{ ucfirst($venta['venta_estado']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($venta['venta_estado'] == 'completado')
                                            <a href="{{ route('ventas.recibo', $venta['productos']->first()->ven_id) }}" class="btn btn-sm btn-info" target="_blank" title="Recibo">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="anularVenta('{{ $venta['ven_codigo'] }}')" title="Anular">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">Anulada</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="9" class="p-0">
                                        <div id="detalle-{{ $venta['ven_codigo'] }}" class="collapse">
                                            <table class="table table-sm table-bordered m-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Cantidad</th>
                                                        <th>Precio Unit.</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($venta['productos'] as $item)
                                                        <tr>
                                                            <td>{{ $item->producto->prod_nombre ?? 'N/A' }}</td>
                                                            <td>{{ $item->venta_cantidad }}</td>
                                                            <td>Bs. {{ number_format($item->venta_precio, 2) }}</td>
                                                            <td>Bs. {{ number_format($item->venta_preciototal, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay ventas</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="4" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                                <td colspan="5"><strong>Bs. {{ number_format($totalGeneral, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2 solo si el elemento existe
    if ($('#producto-filter').length) {
        $('#producto-filter').select2({
            placeholder: 'Seleccione producto...',
            allowClear: true,
            width: '100%'
        });
    }
});

function anularVenta(venCodigo) {
    if (confirm('¿Está seguro de anular esta venta? Se restablecerá el stock de todos los productos.')) {
        // Buscar el primer ID de la venta con ese código
        fetch('{{ url("/api/venta-por-codigo") }}/' + venCodigo)
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ url("/ventas") }}/' + data.id + '/anular';
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    
                    form.appendChild(csrfInput);
                    form.appendChild(methodInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al anular la venta');
            });
    }
}
</script>
@endsection
@endsection
