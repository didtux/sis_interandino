@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h4><i class="fas fa-shield-alt mr-2"></i>{{ isset($rol) ? 'Editar Rol: ' . $rol->rol_nombre : 'Nuevo Rol' }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ isset($rol) ? route('roles.update', $rol->rol_id) : route('roles.store') }}" method="POST">
                        @csrf
                        @if(isset($rol)) @method('PUT') @endif

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Nombre del Rol <span class="text-danger">*</span></label>
                                    <input type="text" name="rol_nombre" class="form-control @error('rol_nombre') is-invalid @enderror"
                                        value="{{ old('rol_nombre', $rol->rol_nombre ?? '') }}" required {{ isset($rol) && $rol->rol_id == 1 ? 'readonly' : '' }}>
                                    @error('rol_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="font-weight-bold">Descripción</label>
                                    <input type="text" name="rol_descripcion" class="form-control"
                                        value="{{ old('rol_descripcion', $rol->rol_descripcion ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3"><i class="fas fa-key mr-2"></i>Permisos por Módulo</h5>

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="marcarTodos(true)"><i class="fas fa-check-double mr-1"></i>Marcar Todos</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="marcarTodos(false)"><i class="fas fa-times mr-1"></i>Desmarcar Todos</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" style="font-size: 0.9rem;">
                                <thead style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                    <tr>
                                        <th style="width: 35%;" class="text-white" >Módulo</th>
                                        <th class="text-center text-white" style="width: 13%; ">
                                            <i class="fas fa-eye mr-1 text-white"></i>Ver
                                        </th>
                                        <th class="text-center text-white" style="width: 13%;">
                                            <i class="fas fa-plus mr-1 text-white"></i>Crear
                                        </th>
                                        <th class="text-center text-white" style="width: 13%;">
                                            <i class="fas fa-edit mr-1 text-white"></i>Editar
                                        </th>
                                        <th class="text-center text-white" style="width: 13%;">
                                            <i class="fas fa-trash mr-1 text-white"></i>Eliminar
                                        </th>
                                        <th class="text-center text-white" style="width: 13%;">
                                            <i class="fas fa-check-double mr-1 text-white"></i>Todos
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modulos as $modulo)
                                        @php
                                            $p = $permisosMap[$modulo->mod_id] ?? ['ver'=>0,'crear'=>0,'editar'=>0,'eliminar'=>0];
                                        @endphp
                                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                                            <td>
                                                <i class="{{ $modulo->mod_icono }} mr-2" style="color: #667eea;"></i>
                                                {{ $modulo->mod_nombre }}
                                            </td>
                                            @foreach(['ver','crear','editar','eliminar'] as $accion)
                                                <td class="text-center">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input perm-check perm-{{ $modulo->mod_id }}"
                                                            id="perm_{{ $modulo->mod_id }}_{{ $accion }}"
                                                            name="permisos[{{ $modulo->mod_id }}][{{ $accion }}]" value="1"
                                                            {{ !empty($p[$accion]) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="perm_{{ $modulo->mod_id }}_{{ $accion }}"></label>
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input toggle-row"
                                                        id="toggle_{{ $modulo->mod_id }}" data-mod="{{ $modulo->mod_id }}"
                                                        {{ ($p['ver'] && $p['crear'] && $p['editar'] && $p['eliminar']) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="toggle_{{ $modulo->mod_id }}"></label>
                                                </div>
                                            </td>
                                        </tr>

                                        @foreach($modulo->hijos as $hijo)
                                            @php
                                                $ph = $permisosMap[$hijo->mod_id] ?? ['ver'=>0,'crear'=>0,'editar'=>0,'eliminar'=>0];
                                            @endphp
                                            <tr>
                                                <td style="padding-left: 2.5rem;">
                                                    <i class="{{ $hijo->mod_icono }} mr-2 text-muted"></i>
                                                    {{ $hijo->mod_nombre }}
                                                </td>
                                                @foreach(['ver','crear','editar','eliminar'] as $accion)
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input perm-check perm-{{ $hijo->mod_id }}"
                                                                id="perm_{{ $hijo->mod_id }}_{{ $accion }}"
                                                                name="permisos[{{ $hijo->mod_id }}][{{ $accion }}]" value="1"
                                                                {{ !empty($ph[$accion]) ? 'checked' : '' }}>
                                                            <label class="custom-control-label" for="perm_{{ $hijo->mod_id }}_{{ $accion }}"></label>
                                                        </div>
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input toggle-row"
                                                            id="toggle_{{ $hijo->mod_id }}" data-mod="{{ $hijo->mod_id }}"
                                                            {{ ($ph['ver'] && $ph['crear'] && $ph['editar'] && $ph['eliminar']) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="toggle_{{ $hijo->mod_id }}"></label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary-modern">
                                <i class="fas fa-save mr-1"></i>{{ isset($rol) ? 'Actualizar' : 'Guardar' }}
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left mr-1"></i>Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle all permissions for a row
    $('.toggle-row').on('change', function() {
        const modId = $(this).data('mod');
        const checked = $(this).is(':checked');
        $('.perm-' + modId).prop('checked', checked);
    });

    // Update toggle-row when individual checkboxes change
    $('.perm-check').on('change', function() {
        const classes = this.className.split(' ');
        const permClass = classes.find(c => c.startsWith('perm-') && c !== 'perm-check');
        if (permClass) {
            const modId = permClass.replace('perm-', '');
            const total = $('.perm-' + modId).length;
            const checked = $('.perm-' + modId + ':checked').length;
            $('#toggle_' + modId).prop('checked', total === checked);
        }
    });
});

function marcarTodos(estado) {
    $('.perm-check, .toggle-row').prop('checked', estado);
}
</script>
@endsection
