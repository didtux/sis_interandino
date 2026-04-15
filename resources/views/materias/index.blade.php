@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">

        {{-- ── Tabla de Materias ── --}}
        <div class="col-lg-7">
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
                                    <th>Campo</th>
                                    <th>Orden</th>
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
                                        <td data-label="Campo"><span class="badge badge-info">{{ $materia->mat_campo ?: '-' }}</span></td>
                                        <td data-label="Orden">{{ $materia->mat_orden }}</td>
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
                                        <td colspan="5">
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

        {{-- ── Grupos de Materias ── --}}
        <div class="col-lg-5">
            <div class="card modern-card" style="border-top:3px solid #8e44ad;">
                <div class="card-header" style="background:#8e44ad;color:#fff;">
                    <h4 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Grupos de Materias (Áreas)</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Agrupa materias que comparten una misma nota promedio.
                        Ej: <strong>Comunicación y Lenguajes</strong> = Aymara + Inglés + Español.
                        El promedio del grupo = suma de promedios / cantidad de materias.
                    </p>

                    {{-- Formulario nuevo grupo --}}
                    <form action="{{ route('materias.guardar-grupo') }}" method="POST" class="mb-4" id="formGrupo">
                        @csrf
                        <input type="hidden" name="grupo_id" id="grupo_id" value="">
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Nombre del Grupo / Área</label>
                            <input type="text" name="grupo_nombre" id="grupo_nombre" class="form-control form-control-sm"
                                   placeholder="Ej: Comunicación y Lenguajes" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Materias del grupo</label>
                            <select name="materias[]" id="grupo_materias" class="form-control select2-multi" multiple required style="width:100%;">
                                @foreach($todasMaterias as $m)
                                    <option value="{{ $m->mat_codigo }}">{{ $m->mat_nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Seleccione al menos 2 materias</small>
                        </div>
                        <div class="d-flex" style="gap:6px;">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-save mr-1"></i><span id="btnGrupoText">Crear Grupo</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btnCancelarEdicion" style="display:none;" onclick="cancelarEdicion()">
                                <i class="fas fa-times mr-1"></i>Cancelar
                            </button>
                        </div>
                    </form>

                    {{-- Listado de grupos existentes --}}
                    @forelse($grupos as $grupo)
                        <div class="card mb-2" style="border-left:4px solid #8e44ad;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong style="color:#8e44ad;">{{ $grupo->grupo_nombre }}</strong>
                                        <div class="mt-1">
                                            @foreach($grupo->materias as $mat)
                                                <span class="badge badge-light border" style="font-size:11px;">{{ $mat->mat_nombre }}</span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">{{ $grupo->materias->count() }} materias — Promedio = Σ promedios / {{ $grupo->materias->count() }}</small>
                                    </div>
                                    <div class="d-flex" style="gap:4px;">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Editar"
                                                onclick="editarGrupo({{ $grupo->grupo_id }}, '{{ addslashes($grupo->grupo_nombre) }}', {{ json_encode($grupo->materias->pluck('mat_codigo')) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('materias.eliminar-grupo', $grupo->grupo_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"
                                                    onclick="return confirm('¿Eliminar grupo {{ addslashes($grupo->grupo_nombre) }}?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-layer-group" style="font-size:2rem;opacity:0.3;"></i>
                            <p class="mt-2 mb-0">No hay grupos creados aún</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#grupo_materias').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione materias...',
        closeOnSelect: false
    });
});

function editarGrupo(id, nombre, materias) {
    $('#grupo_id').val(id);
    $('#grupo_nombre').val(nombre);
    $('#grupo_materias').val(materias).trigger('change');
    $('#btnGrupoText').text('Actualizar Grupo');
    $('#btnCancelarEdicion').show();
    $('html, body').animate({ scrollTop: $('#formGrupo').offset().top - 100 }, 300);
}

function cancelarEdicion() {
    $('#grupo_id').val('');
    $('#grupo_nombre').val('');
    $('#grupo_materias').val([]).trigger('change');
    $('#btnGrupoText').text('Crear Grupo');
    $('#btnCancelarEdicion').hide();
}
</script>
@endsection
