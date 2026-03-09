@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-exchange-alt mr-2"></i>Movimientos de Almacén</h4>
                    <a href="{{ route('movimientos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Movimiento
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="tipo" class="form-control">
                                    <option value="">Todos los tipos</option>
                                    <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entrada</option>
                                    <option value="salida" {{ request('tipo') == 'salida' ? 'selected' : '' }}>Salida</option>
                                    <option value="ajuste" {{ request('tipo') == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                                    <option value="devolucion" {{ request('tipo') == 'devolucion' ? 'selected' : '' }}>Devolución</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movimientos as $m)
                                <tr>
                                    <td>{{ $m->mov_codigo }}</td>
                                    <td>{{ $m->producto->prod_nombre ?? 'N/A' }}</td>
                                    <td>{{ $m->proveedor->prov_nombre ?? 'N/A' }}</td>
                                    <td>
                                        @if($m->mov_tipo == 'entrada')
                                            <span class="badge badge-success">Entrada</span>
                                        @elseif($m->mov_tipo == 'salida')
                                            <span class="badge badge-danger">Salida</span>
                                        @elseif($m->mov_tipo == 'ajuste')
                                            <span class="badge badge-warning">Ajuste</span>
                                        @else
                                            <span class="badge badge-info">Devolución</span>
                                        @endif
                                    </td>
                                    <td>{{ $m->mov_cantidad }}</td>
                                    <td>Bs. {{ number_format($m->mov_precio_unitario, 2) }}</td>
                                    <td>Bs. {{ number_format($m->mov_precio_total, 2) }}</td>
                                    <td>{{ $m->mov_fecha->format('d/m/Y H:i') }}</td>
                                    <td>{{ $m->mov_usuario }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay movimientos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $movimientos->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
