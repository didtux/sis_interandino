@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background-color: #6777ef;">
                    <h4 style="color: white; margin: 0;"><i class="fas fa-warehouse mr-2"></i>Reporte de Stock</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Buscar Producto</label>
                                    <input type="text" name="buscar" class="form-control" placeholder="Nombre o código" value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select name="categ_codigo" class="form-control select2">
                                        <option value="">Todas</option>
                                        @foreach($categorias ?? [] as $cat)
                                            <option value="{{ $cat->categ_codigo }}" {{ request('categ_codigo') == $cat->categ_codigo ? 'selected' : '' }}>{{ $cat->categ_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <select name="prov_codigo" class="form-control select2">
                                        <option value="">Todos</option>
                                        @foreach($proveedores ?? [] as $prov)
                                            <option value="{{ $prov->prov_codigo }}" {{ request('prov_codigo') == $prov->prov_codigo ? 'selected' : '' }}>{{ $prov->prov_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select name="prod_codigo" class="form-control select2">
                                        <option value="">Todos</option>
                                        @foreach($catalogo ?? [] as $prod)
                                            <option value="{{ $prod->prod_codigo }}" {{ request('prod_codigo') == $prod->prod_codigo ? 'selected' : '' }}>{{ $prod->prod_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="sin_stock" {{ request('estado') == 'sin_stock' ? 'selected' : '' }}>Sin Stock</option>
                                        <option value="bajo" {{ request('estado') == 'bajo' ? 'selected' : '' }}>Stock Bajo (1-5)</option>
                                        <option value="medio" {{ request('estado') == 'medio' ? 'selected' : '' }}>Stock Medio (6-10)</option>
                                        <option value="normal" {{ request('estado') == 'normal' ? 'selected' : '' }}>Stock Normal (>10)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                                        <a href="{{ route('movimientos.reporte-stock') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
                                        <button type="button" class="btn btn-danger" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped" id="tablaStock">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Proveedor</th>
                                <th>Stock Actual</th>
                                <th>Precio Unitario</th>
                                <th>Valor Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $valorTotal = 0; @endphp
                            @forelse($productos as $p)
                                @php 
                                    $valor = $p->prod_cantidad * $p->prod_preciounitario;
                                    $valorTotal += $valor;
                                @endphp
                                <tr>
                                    <td>{{ $p->prod_codigo }}</td>
                                    <td><strong>{{ $p->prod_nombre }}</strong></td>
                                    <td>{{ $p->categoria->categ_nombre ?? 'N/A' }}</td>
                                    <td>{{ $p->proveedor->prov_nombre ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $p->prod_cantidad > 10 ? 'success' : ($p->prod_cantidad > 0 ? 'warning' : 'danger') }}">
                                            {{ $p->prod_cantidad }}
                                        </span>
                                    </td>
                                    <td>Bs. {{ number_format($p->prod_preciounitario, 2) }}</td>
                                    <td>Bs. {{ number_format($valor, 2) }}</td>
                                    <td>
                                        @if($p->prod_cantidad == 0)
                                            <span class="badge badge-danger">Sin Stock</span>
                                        @elseif($p->prod_cantidad <= 5)
                                            <span class="badge badge-warning">Stock Bajo</span>
                                        @elseif($p->prod_cantidad <= 10)
                                            <span class="badge badge-info">Stock Medio</span>
                                        @else
                                            <span class="badge badge-success">Stock Normal</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No hay productos</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="6" class="text-right"><strong>VALOR TOTAL INVENTARIO:</strong></td>
                                <td colspan="2"><strong>Bs. {{ number_format($valorTotal, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(function() { $('.select2').select2({ width: '100%' }); });

function exportarPDF() {
    // El PDF sale con los mismos filtros que la pantalla.
    const params = new URLSearchParams();
    ['buscar', 'estado', 'categ_codigo', 'prov_codigo', 'prod_codigo'].forEach(function(campo) {
        const el = document.querySelector('[name="' + campo + '"]');
        if (el && el.value) params.append(campo, el.value);
    });
    window.open('{{ route("movimientos.reporte-stock-pdf") }}?' + params.toString(), '_blank');
}
</script>
@endsection
