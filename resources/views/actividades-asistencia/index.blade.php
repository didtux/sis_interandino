@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-calendar-check mr-2"></i>Asistencia Actividades</h4>
                    @puede('actividades-asistencia', 'crear')
                    <a href="{{ route('actividades-asistencia.create') }}" class="btn btn-primary-modern btn-sm"><i class="fas fa-plus mr-1"></i>Nueva Actividad</a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-8"><input type="text" name="buscar" class="form-control" placeholder="Buscar actividad..." value="{{ request('buscar') }}"></div>
                            <div class="col-md-4 d-flex" style="gap:6px;"><button class="btn btn-primary btn-block"><i class="fas fa-search"></i></button><a href="{{ route('actividades-asistencia.index') }}" class="btn btn-secondary btn-block mt-0"><i class="fas fa-times"></i></a></div>
                        </div>
                    </form>
                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead><tr><th>Nombre</th><th>Fecha</th><th>Categorías</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody>
                                @forelse($actividades as $a)
                                <tr>
                                    <td><strong>{{ $a->act_nombre }}</strong><br><small class="text-muted">{{ $a->act_descripcion }}</small></td>
                                    <td>{{ $a->act_fecha->format('d/m/Y') }}</td>
                                    <td><span class="badge badge-info">{{ $a->categorias_count }}</span></td>
                                    <td><span class="badge badge-{{ $a->act_estado ? 'success' : 'secondary' }}">{{ $a->act_estado ? 'Activo' : 'Inactivo' }}</span></td>
                                    <td>
                                        <a href="{{ route('actividades-asistencia.show', $a->act_id) }}" class="btn btn-sm btn-info" title="Ver"><i class="fas fa-eye"></i></a>
                                        <a href="{{ route('actividades-asistencia.edit', $a->act_id) }}" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('actividades-asistencia.destroy', $a->act_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar actividad y todos sus registros?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5"><div class="empty-state"><i class="fas fa-calendar-check"></i><h5>No hay actividades</h5></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $actividades->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
