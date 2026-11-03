@extends('layouts.app')

@section('content')
@php
    $acciones = [
        'ver'      => ['fas fa-eye', 'Ver'],
        'crear'    => ['fas fa-plus', 'Crear'],
        'editar'   => ['fas fa-edit', 'Editar'],
        'eliminar' => ['fas fa-trash', 'Eliminar'],
        'anular'   => ['fas fa-ban', 'Anular'],
        'exportar' => ['fas fa-file-export', 'Exportar'],
    ];
    $vacio = array_fill_keys(array_keys($acciones), 0);
    $capacidades = collect(config('permisos.capacidades', []))->groupBy('grupo', true);
    $capsRol = $capacidadesRol ?? [];
@endphp
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

                        @if(isset($rol) && $rol->rol_id == 1)
                            <div class="alert alert-info">El Administrador tiene todos los permisos y capacidades siempre; lo que se marque acá no lo limita.</div>
                        @endif

                        <h5 class="mb-2"><i class="fas fa-key mr-2"></i>Permisos por Módulo</h5>
                        <p class="text-muted small mb-3">
                            Marcar un módulo general marca también sus submódulos. <strong>Anular</strong> es anular recibos o registros;
                            <strong>Exportar</strong> es descargar PDF o Excel.
                        </p>

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="marcarTodos(true)"><i class="fas fa-check-double mr-1"></i>Marcar Todos</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="marcarTodos(false)"><i class="fas fa-times mr-1"></i>Desmarcar Todos</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" style="font-size: 0.9rem;">
                                <thead style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                    <tr>
                                        <th style="width: 31%;" class="text-white">Módulo</th>
                                        @foreach($acciones as $accion => [$icono, $titulo])
                                            <th class="text-center text-white" style="width: 9%;"><i class="{{ $icono }} mr-1 text-white"></i>{{ $titulo }}</th>
                                        @endforeach
                                        <th class="text-center text-white" style="width: 9%;"><i class="fas fa-check-double mr-1 text-white"></i>Todos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modulos as $modulo)
                                        @php $p = ($permisosMap[$modulo->mod_id] ?? []) + $vacio; @endphp
                                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                                            <td>
                                                <i class="{{ $modulo->mod_icono }} mr-2" style="color: #667eea;"></i>
                                                {{ $modulo->mod_nombre }}
                                                @if($ayuda = (config('permisos.ayuda_modulos')[$modulo->mod_slug] ?? null))<small class="d-block text-muted font-weight-normal">{{ $ayuda }}</small>@endif
                                            </td>
                                            @foreach($acciones as $accion => $x)
                                                <td class="text-center">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input perm-check perm-{{ $modulo->mod_id }} padre-accion"
                                                            id="perm_{{ $modulo->mod_id }}_{{ $accion }}" data-mod="{{ $modulo->mod_id }}" data-accion="{{ $accion }}"
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
                                                        {{ collect($acciones)->keys()->every(fn($a) => !empty($p[$a])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="toggle_{{ $modulo->mod_id }}"></label>
                                                </div>
                                            </td>
                                        </tr>

                                        @foreach($modulo->hijos as $hijo)
                                            @php $ph = ($permisosMap[$hijo->mod_id] ?? []) + $vacio; @endphp
                                            <tr>
                                                <td style="padding-left: 2.5rem;">
                                                    <i class="{{ $hijo->mod_icono }} mr-2 text-muted"></i>
                                                    {{ $hijo->mod_nombre }}
                                                    @if($ayuda = (config('permisos.ayuda_modulos')[$hijo->mod_slug] ?? null))<small class="d-block text-muted">{{ $ayuda }}</small>@endif
                                                </td>
                                                @foreach($acciones as $accion => $x)
                                                    <td class="text-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input perm-check perm-{{ $hijo->mod_id }} hijo-de-{{ $modulo->mod_id }}-{{ $accion }}"
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
                                                            {{ collect($acciones)->keys()->every(fn($a) => !empty($ph[$a])) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="toggle_{{ $hijo->mod_id }}"></label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Capacidades especiales: antes eran listas de roles escritas en el
                             código, que ningún tilde podía cambiar (config/permisos.php). --}}
                        <h5 class="mt-4 mb-2"><i class="fas fa-user-shield mr-2"></i>Capacidades especiales</h5>
                        <p class="text-muted small mb-3">Decisiones que no dependen de un módulo: aprobar notas, autorizar observados, ver auditoría, etc.</p>
                        <div class="row">
                            @foreach($capacidades as $grupo => $caps)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="border rounded p-2 h-100">
                                        <div class="font-weight-bold mb-2 text-primary">{{ $grupo }}</div>
                                        @foreach($caps as $slug => $cap)
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input cap-check" id="cap_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                       name="capacidades[]" value="{{ $slug }}" {{ in_array($slug, $capsRol) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="cap_{{ $loop->parent->index }}_{{ $loop->index }}">
                                                    {{ $cap['nombre'] }}
                                                    @if(!empty($cap['descripcion']))<br><small class="text-muted">{{ $cap['descripcion'] }}</small>@endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
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
    // "Todos" de una fila
    $('.toggle-row').on('change', function() {
        const modId = $(this).data('mod');
        $('.perm-' + modId).prop('checked', $(this).is(':checked')).trigger('change');
    });

    // Una acción del módulo general se aplica a sus submódulos (antes había que
    // marcar uno por uno y era fácil olvidarse: de ahí muchos "Acceso Denegado").
    $('.padre-accion').on('change', function() {
        $('.hijo-de-' + $(this).data('mod') + '-' + $(this).data('accion')).prop('checked', $(this).is(':checked'));
        actualizarFilas();
    });

    $('.perm-check').on('change', actualizarFilas);
});

function actualizarFilas() {
    $('.toggle-row').each(function() {
        const modId = $(this).data('mod');
        const total = $('.perm-' + modId).length;
        $(this).prop('checked', total > 0 && total === $('.perm-' + modId + ':checked').length);
    });
}

function marcarTodos(estado) {
    $('.perm-check, .toggle-row, .cap-check').prop('checked', estado);
}
</script>
@endsection
