@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                    <h4><i class="fas fa-user-graduate mr-2"></i>Estudiantes</h4>
                    <div class="d-flex flex-wrap" style="gap:6px;">
                        <button class="btn btn-danger btn-sm" onclick="reporteGeneral()" title="Listado PDF">
                            <i class="fas fa-file-pdf"></i> Listado PDF
                        </button>
                        <button class="btn btn-success btn-sm" onclick="listadoExcel()" title="Listado Excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-info btn-sm" onclick="listadoContactos()" title="Lista Contactos">
                            <i class="fas fa-address-book"></i> Contactos
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="codigosQR()" title="Códigos QR">
                            <i class="fas fa-qrcode"></i> QR
                        </button>
                        <a href="{{ route('estudiantes.reprobados') }}" class="btn btn-dark btn-sm">
                            <i class="fas fa-exclamation-triangle"></i> Reprobados
                        </a>
                        @puede('estudiantes', 'crear')
                        <a href="{{ route('estudiantes.create') }}" class="btn btn-primary-modern btn-sm">
                            <i class="fas fa-plus mr-1"></i>Nuevo
                        </a>
                        @endpuede
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
                    @if(session('error'))<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>@endif

                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Buscar</label>
                                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido, CI o código..." value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Curso</label>
                                    <select name="curso" class="form-control select2-curso">
                                        <option value="">Todos</option>
                                        @foreach($cursos as $c)
                                            <option value="{{ $c->cur_codigo }}" {{ request('curso') == $c->cur_codigo ? 'selected' : '' }}>{{ $c->cur_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="registrados" {{ ($estado ?? 'registrados') == 'registrados' ? 'selected' : '' }}>REGISTRADOS</option>
                                        <option value="retirados"   {{ ($estado ?? '') == 'retirados'   ? 'selected' : '' }}>RETIRADOS</option>
                                        <option value="todos"       {{ ($estado ?? '') == 'todos'       ? 'selected' : '' }}>TODOS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                                        <a href="{{ route('estudiantes.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Foto</th>
                                    <th>Código</th>
                                    <th>Estudiante</th>
                                    <th>CI</th>
                                    <th>Curso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estudiantes as $estudiante)
                                    @php $retirado = $estudiante->est_visible == 0; @endphp
                                    <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                                        <td>{{ $estudiante->lista_numero ?? '-' }}</td>
                                        <td>
                                            @if($estudiante->est_foto)
                                                <img src="{{ asset('storage/' . $estudiante->est_foto) }}" style="width:40px;height:40px;object-fit:cover;border-radius:50%;cursor:pointer;" data-foto="{{ asset('storage/' . $estudiante->est_foto) }}" class="foto-thumbnail">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td><span class="modern-badge badge-primary-modern">{{ $estudiante->est_codigo }}</span></td>
                                        <td style="{{ $retirado ? 'color:#c0392b;font-weight:600;' : '' }}">
                                            {{ $estudiante->est_apellidos }} {{ $estudiante->est_nombres }}
                                            @if($retirado)<span class="modern-badge badge-danger-modern ml-1">RETIRADO</span>@endif
                                        </td>
                                        <td>{{ $estudiante->est_ci }}</td>
                                        <td><span class="modern-badge badge-success-modern">{{ $estudiante->curso->cur_nombre ?? 'N/A' }}</span></td>
                                        <td>
                                            @if($retirado)
                                                <span class="modern-badge badge-danger-modern">RETIRADO</span>
                                            @else
                                                <span class="modern-badge badge-success-modern">ACTIVO</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons" style="flex-wrap:wrap;">
                                                @if(request('curso'))
                                                <form action="{{ route('estudiantes.subir', $estudiante->est_id) }}" method="POST" style="display:inline;">@csrf
                                                    <button class="btn btn-action" title="Subir"><i class="fas fa-arrow-up"></i></button>
                                                </form>
                                                <form action="{{ route('estudiantes.bajar', $estudiante->est_id) }}" method="POST" style="display:inline;">@csrf
                                                    <button class="btn btn-action" title="Bajar"><i class="fas fa-arrow-down"></i></button>
                                                </form>
                                                @endif
                                                <a href="{{ route('estudiantes.kardex', $estudiante->est_id) }}" class="btn btn-sm btn-primary" title="Kárdex" target="_blank"><i class="fas fa-id-card"></i></a>
                                                <a href="{{ route('estudiantes.show', $estudiante->est_id) }}" class="btn btn-action btn-action-view" title="Ver"><i class="fas fa-eye"></i></a>
                                                @puede('estudiantes', 'editar')
                                                <a href="{{ route('estudiantes.edit', $estudiante->est_id) }}" class="btn btn-action btn-action-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                                <form action="{{ route('estudiantes.toggle-estado', $estudiante->est_id) }}" method="POST" style="display:inline;">@csrf
                                                    <button class="btn btn-sm {{ $retirado ? 'btn-success' : 'btn-warning' }}" title="{{ $retirado ? 'Reactivar estudiante' : 'Dar de baja' }}" onclick="return confirm('{{ $retirado ? '¿Reactivar este estudiante?' : '¿Dar de baja este estudiante?' }}')">
                                                        <i class="fas fa-{{ $retirado ? 'user-check' : 'user-slash' }}"></i>
                                                    </button>
                                                </form>
                                                @endpuede
                                                @puede('estudiantes', 'eliminar')
                                                <form action="{{ route('estudiantes.destroy', $estudiante->est_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-action btn-action-delete" onclick="return confirm('¿Eliminar?')" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                </form>
                                                @endpuede
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8"><div class="empty-state"><i class="fas fa-user-graduate"></i><h5>No hay estudiantes</h5></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">{{ $estudiantes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2-curso').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Seleccione un curso' });
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

function _curso() { return $('select[name="curso"]').val() || ''; }
function reporteGeneral()  { window.open('{{ route("estudiantes.reporte-general") }}'  + (_curso() ? '?curso=' + _curso() : ''), '_blank'); }
function listadoExcel()    { window.open('{{ route("estudiantes.listado-excel") }}'    + (_curso() ? '?curso=' + _curso() : ''), '_blank'); }
function listadoContactos(){
    if (!_curso()) { alert('Seleccione un curso'); return; }
    window.open('{{ route("estudiantes.listado-contactos") }}?curso=' + _curso(), '_blank');
}
function codigosQR(){
    if (!_curso()) { alert('Seleccione un curso'); return; }
    window.open('{{ route("estudiantes.codigos-qr") }}?curso=' + _curso(), '_blank');
}
</script>
@endsection
