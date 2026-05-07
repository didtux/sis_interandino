@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-calendar-alt mr-2"></i>Gestiones</h4>
            <a href="{{ route('gestiones.create') }}" class="btn btn-primary-modern">
                <i class="fas fa-plus mr-1"></i>Nueva Gestión
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif
            <div class="table-responsive-modern">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th>Nombre</th>
                            <th>Abreviado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gestiones as $g)
                            <tr>
                                <td>{{ $g->ges_anio }}</td>
                                <td>{{ $g->ges_nombre }}</td>
                                <td>{{ $g->ges_abreviado ?: '-' }}</td>
                                <td>
                                    @if($g->ges_estado == 1)
                                        <span class="modern-badge badge-success-modern">ACTIVO</span>
                                    @else
                                        <span class="modern-badge badge-danger-modern">INACTIVO</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        @if($g->ges_estado != 1)
                                        <form action="{{ route('gestiones.activar', $g->ges_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-action btn-action-view" title="Activar"><i class="fas fa-power-off"></i></button>
                                        </form>
                                        @endif
                                        <a href="{{ route('gestiones.edit', $g->ges_id) }}" class="btn btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h5>Sin gestiones</h5></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
