@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-chalkboard-teacher mr-2"></i>Docentes</h4>
                    <a href="{{ route('docentes.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Docente
                    </a>
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
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>CI</th>
                                    <th>Materia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($docentes as $docente)
                                    <tr>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $docente->doc_codigo }}</span>
                                        </td>
                                        <td data-label="Nombres">{{ $docente->doc_nombres }}</td>
                                        <td data-label="Apellidos">{{ $docente->doc_apellidos }}</td>
                                        <td data-label="CI">{{ $docente->doc_ci }}</td>
                                        <td data-label="Materia">
                                            <span class="modern-badge badge-warning-modern">{{ $docente->doc_materia }}</span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <a href="{{ route('docentes.show', $docente->doc_id) }}" class="btn btn-action btn-action-view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('docentes.edit', $docente->doc_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('docentes.destroy', $docente->doc_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este docente?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
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
@endsection
