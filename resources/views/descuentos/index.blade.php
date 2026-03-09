@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-percent mr-2"></i>Descuentos</h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalDescuento">
                        <i class="fas fa-plus"></i> Nuevo Descuento
                    </button>
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
                                <th>Porcentaje</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($descuentos as $d)
                                <tr>
                                    <td>{{ $d->desc_codigo }}</td>
                                    <td>{{ $d->desc_nombre }}</td>
                                    <td><span class="badge badge-success">{{ $d->desc_porcentaje }}%</span></td>
                                    <td>
                                        @if($d->desc_estado)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-danger">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $d->desc_fecha_registro ? $d->desc_fecha_registro->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        @if($d->desc_estado)
                                            <button class="btn btn-sm btn-warning" onclick="editarDescuento({{ $d->desc_id }}, '{{ $d->desc_nombre }}', {{ $d->desc_porcentaje }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('descuentos.destroy', $d->desc_id) }}" method="POST" style="display:inline">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay descuentos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalDescuento">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('descuentos.store') }}" method="POST" id="formDescuento">
                @csrf
                <input type="hidden" name="_method" value="POST" id="methodDescuento">
                <div class="modal-header">
                    <h5 id="tituloModal">Nuevo Descuento</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="desc_nombre" id="desc_nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Porcentaje (%) *</label>
                        <input type="number" step="0.01" name="desc_porcentaje" id="desc_porcentaje" class="form-control" min="0" max="100" required>
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

@section('scripts')
<script>
$('#modalDescuento').on('hidden.bs.modal', function() {
    $('#tituloModal').text('Nuevo Descuento');
    $('#formDescuento').attr('action', '{{ route('descuentos.store') }}');
    $('#methodDescuento').val('POST');
    $('#formDescuento')[0].reset();
});

function editarDescuento(id, nombre, porcentaje) {
    $('#tituloModal').text('Editar Descuento');
    $('#formDescuento').attr('action', '/descuentos/' + id);
    $('#methodDescuento').val('PUT');
    $('#desc_nombre').val(nombre);
    $('#desc_porcentaje').val(porcentaje);
    $('#modalDescuento').modal('show');
}
</script>
@endsection
@endsection
