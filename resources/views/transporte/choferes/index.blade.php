@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-id-card mr-2"></i>Choferes</h4>
                    <a href="{{ route('choferes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Chofer
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Código</th>
                                <th>Nombres</th>
                                <th>CI</th>
                                <th>Licencia</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($choferes as $c)
                                <tr>
                                    <td>
                                        @if($c->chof_foto)
                                            <img src="{{ asset('storage/' . $c->chof_foto) }}" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:50%;cursor:pointer;" data-foto="{{ asset('storage/' . $c->chof_foto) }}" class="foto-thumbnail">
                                        @else
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        @endif
                                    </td>
                                    <td>{{ $c->chof_codigo }}</td>
                                    <td><strong>{{ $c->chof_nombres }} {{ $c->chof_apellidos }}</strong></td>
                                    <td>{{ $c->chof_ci }}</td>
                                    <td>{{ $c->chof_licencia }}</td>
                                    <td>{{ $c->chof_telefono }}</td>
                                    <td>
                                        <span class="badge badge-{{ $c->chof_estado ? 'success' : 'danger' }}">
                                            {{ $c->chof_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('choferes.edit', $c->chof_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('choferes.destroy', $c->chof_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar chofer?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No hay choferes registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.foto-thumbnail').forEach(img => {
        img.addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:pointer;';
            modal.innerHTML = `<img src="${this.dataset.foto}" style="max-width:90%;max-height:90%;border-radius:8px;">`;
            modal.onclick = () => modal.remove();
            document.body.appendChild(modal);
        });
    });
});
</script>
