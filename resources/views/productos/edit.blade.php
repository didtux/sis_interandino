@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Editar Producto</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('productos.update', $producto->prod_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="prod_nombre" class="form-control" value="{{ $producto->prod_nombre }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Categoría *</label>
                                    <select name="categ_codigo" class="form-control select2" required>
                                        @foreach($categorias as $c)
                                            <option value="{{ $c->categ_codigo }}" {{ $producto->categ_codigo == $c->categ_codigo ? 'selected' : '' }}>
                                                {{ $c->categ_nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item/SKU</label>
                                    <input type="text" name="prod_item" class="form-control" value="{{ $producto->prod_item }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cantidad *</label>
                                    <input type="number" name="prod_cantidad" class="form-control" value="{{ $producto->prod_cantidad }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio Unitario *</label>
                                    <input type="number" step="0.01" name="prod_preciounitario" class="form-control" value="{{ $producto->prod_preciounitario }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio con Descuento</label>
                                    <input type="number" step="0.01" name="prod_preciodescuento" class="form-control" value="{{ $producto->prod_preciodescuento }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Detalles</label>
                                    <textarea name="prod_detalles" class="form-control" rows="3">{{ $producto->prod_detalles }}</textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
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
</script>
@endsection
@endsection
