@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-id-card mr-2"></i>Choferes</h4>
                    @puede('choferes', 'crear')
                    <a href="{{ route('choferes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Chofer
                    </a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
                    @endif

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
                                        @puede('choferes', 'crear')
                                        @if(in_array($c->chof_codigo, $usuariosChoferes))
                                            <span class="btn btn-sm" style="background-color: #28a745; color: white;" title="Ya tiene usuario">
                                                <i class="fas fa-user-check"></i>
                                            </span>
                                        @else
                                            <button class="btn btn-sm" style="background-color: #6f42c1; color: white;" onclick="crearUsuario('{{ $c->chof_id }}', '{{ $c->chof_nombres }} {{ $c->chof_apellidos }}', '{{ $c->chof_ci }}')" title="Crear Usuario">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        @endif
                                        @endpuede
                                        @puede('choferes', 'editar')
                                        <a href="{{ route('choferes.edit', $c->chof_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endpuede
                                        @puede('choferes', 'eliminar')
                                        <form action="{{ route('choferes.destroy', $c->chof_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar chofer?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endpuede
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

<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #6f42c1, #8b5cf6); color: white;">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Crear Usuario</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formCrearUsuario" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="font-weight-bold">Chofer:</label>
                        <span id="modal_nombre"></span>
                    </div>
                    <div class="mb-3">
                        <label class="font-weight-bold">CI:</label>
                        <span id="modal_ci"></span>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="modal_password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" style="background-color: #6f42c1; color: white;"><i class="fas fa-save mr-1"></i>Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function crearUsuario(id, nombre, ci) {
    document.getElementById('modal_nombre').textContent = nombre;
    document.getElementById('modal_ci').textContent = ci;
    document.getElementById('formCrearUsuario').action = '{{ url('/choferes') }}/' + id + '/crear-usuario';
    document.getElementById('modal_password').value = '';
    $('#modalCrearUsuario').modal('show');
}

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
@endsection
