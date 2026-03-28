@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-book mr-2"></i>Materias</h4>
                    @puede('materias', 'crear')
                    <a href="{{ route('materias.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nueva Materia
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
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materias as $materia)
                                    <tr>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $materia->mat_codigo }}</span>
                                        </td>
                                        <td data-label="Nombre">{{ $materia->mat_nombre }}</td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                @puede('materias', 'editar')
                                                <a href="{{ route('materias.edit', $materia->mat_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endpuede
                                                @puede('materias', 'eliminar')
                                                <form action="{{ route('materias.destroy', $materia->mat_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar esta materia?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">
                                            <div class="empty-state">
                                                <i class="fas fa-book"></i>
                                                <h5>No hay materias registradas</h5>
                                                <p>Comienza agregando tu primera materia</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $materias->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
