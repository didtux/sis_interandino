@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-school mr-2"></i>Cursos</h4>
                    @puede('cursos', 'crear')
                    <a href="{{ route('cursos.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Curso
                    </a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Estudiantes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cursos as $curso)
                                    <tr>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $curso->cur_codigo }}</span>
                                        </td>
                                        <td data-label="Nombre">{{ $curso->cur_nombre }}</td>
                                        <td data-label="Estudiantes">
                                            <span class="modern-badge badge-success-modern">
                                                <i class="fas fa-users mr-1"></i>{{ $curso->estudiantes_count }}
                                            </span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('cursos.show', $curso->cur_id) }}" class="btn btn-action btn-action-view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @puede('cursos', 'editar')
                                                <a href="{{ route('cursos.edit', $curso->cur_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('cursos', 'eliminar')
                                                <form action="{{ route('cursos.destroy', $curso->cur_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este curso?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty-state">
                                                <i class="fas fa-school"></i>
                                                <h5>No hay cursos registrados</h5>
                                                <p>Comienza agregando tu primer curso</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $cursos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
