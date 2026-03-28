@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-user-graduate mr-2"></i>Estudiantes</h4>
                    <div>
                        <button class="btn btn-danger" onclick="reporteGeneral()">
                            <i class="fas fa-file-pdf"></i> Reporte General
                        </button>
                        @puede('estudiantes', 'crear')
                        <a href="{{ route('estudiantes.create') }}" class="btn btn-primary-modern">
                            <i class="fas fa-plus mr-1"></i>Nuevo Estudiante
                        </a>
                        @endpuede
                    </div>
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

                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Buscar Estudiante</label>
                                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido, CI o código..." value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Filtrar por Curso</label>
                                    <select name="curso" class="form-control select2-curso">
                                        <option value="">Todos los cursos</option>
                                        @foreach($cursos as $c)
                                            <option value="{{ $c->cur_codigo }}" {{ request('curso') == $c->cur_codigo ? 'selected' : '' }}>
                                                {{ $c->cur_nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ route('estudiantes.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
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
                                    <th>Curso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estudiantes as $estudiante)
                                    <tr>
                                        <td data-label="Foto">
                                            @if($estudiante->est_foto)
                                                <img src="{{ asset('storage/' . $estudiante->est_foto) }}" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:50%;cursor:pointer;" data-foto="{{ asset('storage/' . $estudiante->est_foto) }}" class="foto-thumbnail">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $estudiante->est_codigo }}</span>
                                        </td>
                                        <td data-label="Nombres">{{ $estudiante->est_nombres }}</td>
                                        <td data-label="Apellidos">{{ $estudiante->est_apellidos }}</td>
                                        <td data-label="CI">{{ $estudiante->est_ci }}</td>
                                        <td data-label="Curso">
                                            <span class="modern-badge badge-success-modern">{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('estudiantes.kardex', $estudiante->est_id) }}" class="btn btn-sm btn-primary" title="Kardex" target="_blank">
                                                    <i class="fas fa-id-card"></i> Kardex
                                                </a>
                                                <a href="{{ route('estudiantes.show', $estudiante->est_id) }}" class="btn btn-action btn-action-view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @puede('estudiantes', 'editar')
                                                <a href="{{ route('estudiantes.edit', $estudiante->est_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('estudiantes', 'eliminar')
                                                <form action="{{ route('estudiantes.destroy', $estudiante->est_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este estudiante?')" title="Eliminar">
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
                                                <i class="fas fa-user-graduate"></i>
                                                <h5>No hay estudiantes registrados</h5>
                                                <p>Comienza agregando tu primer estudiante</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $estudiantes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2-curso').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione un curso'
    });
    
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

function reporteGeneral() {
    const curso = $('select[name="curso"]').val();
    let url = '{{ route("estudiantes.reporte-general") }}';
    
    if (curso) {
        url += '?curso=' + curso;
    }
    
    window.open(url, '_blank');
}
</script>
@endsection
