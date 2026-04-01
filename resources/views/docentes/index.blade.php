@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-chalkboard-teacher mr-2"></i>Docentes</h4>
                    @puede('docentes', 'crear')
                    <a href="{{ route('docentes.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Docente
                    </a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, CI o código" value="{{ request('buscar') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="cur_codigo" class="form-control select2" style="width:100%">
                                    <option value="">Filtrar por curso</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>
                                            {{ $curso->cur_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="mat_codigo" class="form-control select2" style="width:100%">
                                    <option value="">Filtrar por materia</option>
                                    @foreach($materias as $materia)
                                        <option value="{{ $materia->mat_codigo }}" {{ request('mat_codigo') == $materia->mat_codigo ? 'selected' : '' }}>
                                            {{ $materia->mat_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex" style="gap:6px;">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                                <a href="{{ route('docentes.index') }}" class="btn btn-secondary btn-block mt-0"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Código</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>CI</th>
                                    <th>Materias / Cursos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($docentes as $docente)
                                    <tr>
                                        <td data-label="Foto">
                                            @if($docente->doc_foto)
                                                <img src="{{ asset('storage/' . $docente->doc_foto) }}" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $docente->doc_codigo }}</span>
                                        </td>
                                        <td data-label="Nombres">{{ $docente->doc_nombres }}</td>
                                        <td data-label="Apellidos">{{ $docente->doc_apellidos }}</td>
                                        <td data-label="CI">{{ $docente->doc_ci }}</td>
                                        <td data-label="Materias / Cursos">
                                            @forelse($docente->cursoMateriaDocentes as $cmd)
                                                <span class="modern-badge badge-warning-modern" style="margin:2px; display:inline-block;">
                                                    {{ $cmd->materia->mat_nombre ?? $cmd->mat_codigo }}
                                                    <small style="opacity:0.8;">→ {{ $cmd->curso->cur_nombre ?? $cmd->cur_codigo }}</small>
                                                </span>
                                            @empty
                                                <span class="text-muted">Sin asignar</span>
                                            @endforelse
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                @puede('docentes', 'crear')
                                                @if(in_array($docente->doc_codigo, $usuariosDocentes))
                                                    <span class="btn btn-action btn-sm" style="background-color: #28a745; color: white;" title="Ya tiene usuario">
                                                        <i class="fas fa-user-check"></i>
                                                    </span>
                                                @else
                                                    <button class="btn btn-action btn-sm" style="background-color: #6f42c1; color: white;" onclick="crearUsuario('{{ $docente->doc_id }}', '{{ $docente->doc_nombres }} {{ $docente->doc_apellidos }}', '{{ $docente->doc_ci }}')" title="Crear Usuario">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                @endif
                                                @endpuede
                                                <a href="{{ route('docentes.show', $docente->doc_id) }}" class="btn btn-action btn-action-view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @puede('docentes', 'editar')
                                                <a href="{{ route('docentes.edit', $docente->doc_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('docentes', 'eliminar')
                                                <form action="{{ route('docentes.destroy', $docente->doc_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este docente?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                                <h5>No hay docentes registrados</h5>
                                                <p>Comienza agregando tu primer docente</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $docentes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@puede('docentes', 'crear')
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
                        <label class="font-weight-bold">Docente:</label>
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
@endpuede
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: 'Seleccione...' });
});
function crearUsuario(id, nombre, ci) {
    document.getElementById('modal_nombre').textContent = nombre;
    document.getElementById('modal_ci').textContent = ci;
    document.getElementById('formCrearUsuario').action = '{{ url('/docentes') }}/' + id + '/crear-usuario';
    document.getElementById('modal_password').value = '';
    $('#modalCrearUsuario').modal('show');
}
</script>
@endsection
