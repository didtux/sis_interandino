@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-users mr-2"></i>Padres de Familia</h4>
                    <a href="{{ route('padres.create') }}" class="btn btn-primary-modern">
                        <i class="fas fa-plus mr-1"></i>Nuevo Padre
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif



                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, CI o código" value="{{ request('buscar') }}">
                            </div>
                            <div class="col-md-4">
                                <select name="estudiante_id" class="form-control select2" style="width: 100%">
                                    <option value="">Filtrar por estudiante</option>
                                    @foreach($estudiantes as $est)
                                        <option value="{{ $est->est_codigo }}" {{ request('estudiante_id') == $est->est_codigo ? 'selected' : '' }}>
                                            {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('padres.index') }}" class="btn btn-secondary btn-block"><i class="fas fa-times"></i> Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Código</th>
                                    <th>CI</th>
                                    <th>Nombres</th>
                                    <th>Celular</th>
                                    <th>Correo</th>
                                    <th>Estudiantes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($padres as $padre)
                                    <tr>
                                        <td data-label="Foto">
                                            @if($padre->pfam_foto)
                                                <img src="{{ asset('storage/' . $padre->pfam_foto) }}" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:50%;cursor:pointer;" data-foto="{{ asset('storage/' . $padre->pfam_foto) }}" class="foto-thumbnail">
                                            @else
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            @endif
                                        </td>
                                        <td data-label="Código">
                                            <span class="modern-badge badge-primary-modern">{{ $padre->pfam_codigo }}</span>
                                        </td>
                                        <td data-label="CI">{{ $padre->pfam_ci }}</td>
                                        <td data-label="Nombres">{{ $padre->pfam_nombres }}</td>
                                        <td data-label="Celular">
                                            <i class="fas fa-phone mr-1"></i>{{ $padre->pfam_numeroscelular }}
                                        </td>
                                        <td data-label="Correo">
                                            <i class="fas fa-envelope mr-1"></i>{{ $padre->pfam_correo }}
                                        </td>
                                        <td data-label="Estudiantes">
                                            @if($padre->estudiantes->count() > 0)
                                                @foreach($padre->estudiantes as $est)
                                                    <span class="badge badge-info">{{ $est->est_nombres }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Sin estudiantes</span>
                                            @endif
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-buttons">
                                                <button class="btn btn-action btn-sm" style="background-color: #17a2b8; color: white;" onclick="gestionarEstudiantes('{{ $padre->pfam_id }}', '{{ $padre->pfam_nombres }}')" title="Gestionar Estudiantes">
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                                <a href="{{ route('padres.edit', $padre->pfam_id) }}" class="btn btn-action btn-action-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('padres.destroy', $padre->pfam_id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-delete" onclick="return confirm('¿Está seguro de eliminar este padre de familia?')" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <h5>No hay padres registrados</h5>
                                                <p>Comienza agregando el primer padre de familia</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $padres->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEstudiantes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-friends"></i> Gestionar Estudiantes - <span id="nombrePadre"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                @foreach($padres as $p)
                <div class="padre-estudiantes" data-padre-id="{{ $p->pfam_id }}" style="display:none;">
                    <div class="form-group">
                        <label>Estudiantes Vinculados</label>
                        <div class="estudiantes-list">
                            @if($p->estudiantes->count() > 0)
                                @foreach($p->estudiantes as $est)
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user"></i> {{ $est->est_nombres }} {{ $est->est_apellidos }} - {{ $est->curso->cur_nombre ?? 'Sin curso' }}</span>
                                    <form action="{{ route('padres.desvincular', [$p->pfam_id, $est->est_codigo]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Desvincular este estudiante?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">No hay estudiantes vinculados</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                <form id="formEstudiantes">
                    <input type="hidden" id="padre_id">
                    <div class="form-group">
                        <label>Agregar Estudiante</label>
                        <select id="estudiante_agregar" class="form-control select2-modal">
                            <option value="">Seleccione un estudiante</option>
                            @foreach($estudiantes as $est)
                                <option value="{{ $est->est_codigo }}">{{ $est->est_nombres }} {{ $est->est_apellidos }} - {{ $est->curso->cur_nombre ?? 'Sin curso' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="agregarEstudiante()">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione...',
        allowClear: true
    });
});

function gestionarEstudiantes(padreId, nombrePadre) {
    $('#padre_id').val(padreId);
    $('#nombrePadre').text(nombrePadre);
    $('.padre-estudiantes').hide();
    $('.padre-estudiantes[data-padre-id="' + padreId + '"]').show();
    $('#modalEstudiantes').modal('show');
    setTimeout(() => {
        $('.select2-modal').select2({ 
            dropdownParent: $('#modalEstudiantes'),
            theme: 'bootstrap4',
            width: '100%'
        });
    }, 300);
}

function agregarEstudiante() {
    const padreId = $('#padre_id').val();
    const estudianteId = $('#estudiante_agregar').val();
    
    if (!estudianteId) {
        alert('Seleccione un estudiante');
        return;
    }
    
    var form = $('<form>', {
        method: 'POST',
        action: '{{ route("padres.vincular", ":id") }}'.replace(':id', padreId)
    });
    form.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
    form.append($('<input>', {type: 'hidden', name: 'est_id', value: estudianteId}));
    $('body').append(form);
    form.submit();
}

</script>
@endsection
