@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-tags mr-2"></i>Categorías</h4>
                    @puede('categorias', 'crear')
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalCategoria">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </button>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorias as $c)
                                <tr>
                                    <td>{{ $c->categ_codigo }}</td>
                                    <td><strong>{{ $c->categ_nombre }}</strong></td>
                                    <td><span class="badge badge-primary">{{ $c->productos_count }}</span></td>
                                    <td>
                                        @puede('categorias', 'editar')
                                        <button class="btn btn-sm btn-info" onclick="editarCategoria({{ $c->categ_id }}, '{{ $c->categ_codigo }}', '{{ addslashes($c->categ_nombre) }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @endpuede
                                        @puede('categorias', 'eliminar')
                                        <form action="{{ route('categorias.destroy', $c->categ_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endpuede
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">No hay categorías</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $categorias->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoria">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCategoria" action="{{ route('categorias.store') }}" method="POST">
                @csrf
                <input type="hidden" id="method" name="_method" value="POST">
                <div class="modal-header">
                    <h5 id="modalTitle">Nueva Categoría</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" id="categ_codigo" name="categ_codigo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" id="categ_nombre" name="categ_nombre" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editarCategoria(id, codigo, nombre) {
    $('#modalTitle').text('Editar Categoría');
    $('#formCategoria').attr('action', '{{ url("categorias") }}/' + id);
    $('#method').val('PUT');
    $('#categ_codigo').val(codigo);
    $('#categ_nombre').val(nombre);
    $('#modalCategoria').modal('show');
}

$('#modalCategoria').on('hidden.bs.modal', function () {
    $('#modalTitle').text('Nueva Categoría');
    $('#formCategoria').attr('action', '{{ route('categorias.store') }}');
    $('#method').val('POST');
    $('#categ_codigo').val('');
    $('#categ_nombre').val('');
});
</script>
@endsection
