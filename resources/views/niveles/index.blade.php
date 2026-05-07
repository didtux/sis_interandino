@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-layer-group mr-2"></i>Niveles</h4>
            <a href="{{ route('niveles.create') }}" class="btn btn-primary-modern">
                <i class="fas fa-plus mr-1"></i>Nuevo Nivel
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
                            <th>Orden</th>
                            <th>Nombre</th>
                            <th>Abreviado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($niveles as $n)
                            <tr>
                                <td>{{ $n->niv_orden }}</td>
                                <td>{{ $n->niv_nombre }}</td>
                                <td>{{ $n->niv_abreviado ?: '-' }}</td>
                                <td>
                                    @if($n->niv_estado == 1)
                                        <span class="modern-badge badge-success-modern">ACTIVO</span>
                                    @else
                                        <span class="modern-badge badge-danger-modern">INACTIVO</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('niveles.edit', $n->niv_id) }}" class="btn btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('niveles.destroy', $n->niv_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete" onclick="return confirm('¿Desactivar nivel?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-layer-group"></i><h5>Sin niveles</h5></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
