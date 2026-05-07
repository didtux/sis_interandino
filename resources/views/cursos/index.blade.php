@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
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

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('cursos.index') }}" class="row g-2 mb-3">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                                   placeholder="Buscar por código, nombre o abreviado...">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="nivel" class="form-control">
                                <option value="">-- Todos los niveles --</option>
                                <option value="INICIAL"     {{ ($nivel ?? '') == 'INICIAL'     ? 'selected' : '' }}>INICIAL</option>
                                <option value="PRIMARIA"    {{ ($nivel ?? '') == 'PRIMARIA'    ? 'selected' : '' }}>PRIMARIA</option>
                                <option value="SECUNDARIA"  {{ ($nivel ?? '') == 'SECUNDARIA'  ? 'selected' : '' }}>SECUNDARIA</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="estado" class="form-control">
                                <option value="activos"   {{ ($estado ?? 'activos') == 'activos'   ? 'selected' : '' }}>Activos</option>
                                <option value="inactivos" {{ ($estado ?? '')        == 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                                <option value="todos"     {{ ($estado ?? '')        == 'todos'     ? 'selected' : '' }}>Todos</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2 d-flex" style="gap:6px;">
                            <button type="submit" class="btn btn-primary-modern flex-grow-1">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('cursos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-eraser"></i>
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Abreviado</th>
                                    <th>Nivel</th>
                                    <th>Cupo</th>
                                    <th>Estudiantes</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cursos as $curso)
                                    <tr>
                                        <td data-label="#">{{ $curso->cur_orden }}</td>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $curso->cur_codigo }}</span>
                                        </td>
                                        <td data-label="Nombre">{{ $curso->cur_nombre }}</td>
                                        <td data-label="Abreviado">{{ $curso->cur_abreviado ?: '-' }}</td>
                                        <td data-label="Nivel">
                                            @if($curso->cur_nivel)
                                                <span class="modern-badge badge-info-modern">{{ $curso->cur_nivel }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td data-label="Cupo">{{ $curso->cur_cupo ?: '-' }}</td>
                                        <td data-label="Estudiantes">
                                            <span class="modern-badge badge-success-modern">
                                                <i class="fas fa-users mr-1"></i>{{ $curso->estudiantes_count }}
                                            </span>
                                        </td>
                                        <td data-label="Estado">
                                            @if($curso->cur_visible == 1)
                                                <span class="modern-badge badge-success-modern">ACTIVO</span>
                                            @else
                                                <span class="modern-badge badge-danger-modern">INACTIVO</span>
                                            @endif
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
                                                @if($curso->cur_visible == 1)
                                                <form action="{{ route('cursos.destroy', $curso->cur_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de desactivar este curso?')" title="Desactivar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
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
