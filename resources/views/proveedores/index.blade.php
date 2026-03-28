@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-truck mr-2"></i>Proveedores</h4>
                    @puede('proveedores', 'crear')
                    <a href="{{ route('proveedores.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Proveedor
                    </a>
                    @endpuede
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>NIT</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Contacto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proveedores as $p)
                                <tr>
                                    <td>{{ $p->prov_codigo }}</td>
                                    <td><strong>{{ $p->prov_nombre }}</strong></td>
                                    <td>{{ $p->prov_nit ?? 'N/A' }}</td>
                                    <td>{{ $p->prov_telefono ?? 'N/A' }}</td>
                                    <td>{{ $p->prov_email ?? 'N/A' }}</td>
                                    <td>{{ $p->prov_contacto ?? 'N/A' }}</td>
                                    <td>
                                        @puede('proveedores', 'editar')
                                        <a href="{{ route('proveedores.edit', $p->prov_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endpuede
                                        @puede('proveedores', 'eliminar')
                                        <form action="{{ route('proveedores.destroy', $p->prov_id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endpuede
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No hay proveedores</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $proveedores->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
