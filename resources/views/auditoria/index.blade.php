@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-history mr-2"></i>Auditoría del Sistema</h4>
                    <span class="badge badge-secondary p-2">{{ $registros->total() }} registros</span>
                </div>
                <div class="card-body">
                    {{-- Filtros --}}
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Usuario</label>
                                <input type="text" name="usuario" class="form-control form-control-sm" placeholder="Nombre..." value="{{ request('usuario') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Módulo</label>
                                <select name="modulo" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    @foreach($modulos as $m)
                                        <option value="{{ $m }}" {{ request('modulo') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small text-muted mb-1">Acción</label>
                                <select name="accion" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <option value="crear" {{ request('accion') == 'crear' ? 'selected' : '' }}>Crear</option>
                                    <option value="editar" {{ request('accion') == 'editar' ? 'selected' : '' }}>Editar</option>
                                    <option value="eliminar" {{ request('accion') == 'eliminar' ? 'selected' : '' }}>Eliminar</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end" style="gap:4px;">
                                <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i> Filtrar</button>
                                <a href="{{ route('auditoria.index') }}" class="btn btn-secondary btn-sm btn-block mt-0"><i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table" style="font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th style="width:140px;">Fecha</th>
                                    <th>Usuario</th>
                                    <th style="width:80px;">Acción</th>
                                    <th>Módulo</th>
                                    <th>Descripción</th>
                                    <th style="width:100px;">IP</th>
                                    <th style="width:50px;">Det.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registros as $r)
                                    <tr>
                                        <td><small>{{ $r->audit_fecha->format('d/m/Y H:i:s') }}</small></td>
                                        <td><strong>{{ $r->audit_usuario_nombre }}</strong></td>
                                        <td>
                                            @php
                                                $colorAccion = match($r->audit_accion) {
                                                    'crear' => 'badge-success',
                                                    'editar' => 'badge-warning',
                                                    'eliminar' => 'badge-danger',
                                                    default => 'badge-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $colorAccion }}">{{ ucfirst($r->audit_accion) }}</span>
                                        </td>
                                        <td><span class="modern-badge badge-primary-modern" style="font-size:11px;">{{ $r->audit_modulo }}</span></td>
                                        <td>{{ $r->audit_descripcion }}</td>
                                        <td><small class="text-muted">{{ $r->audit_ip }}</small></td>
                                        <td>
                                            @if($r->audit_datos_anteriores || $r->audit_datos_nuevos)
                                                <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#modal{{ $r->audit_id }}"><i class="fas fa-eye"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No hay registros de auditoría</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $registros->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modales de detalle --}}
@foreach($registros as $r)
    @if($r->audit_datos_anteriores || $r->audit_datos_nuevos)
        <div class="modal fade" id="modal{{ $r->audit_id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#2c3e50,#34495e);color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-search-plus mr-2"></i>Detalle - {{ ucfirst($r->audit_accion) }} en {{ $r->audit_modulo }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Fecha:</strong> {{ $r->audit_fecha->format('d/m/Y H:i:s') }} | <strong>Usuario:</strong> {{ $r->audit_usuario_nombre }} | <strong>ID Registro:</strong> {{ $r->audit_registro_id ?? '-' }}</p>
                        <div class="row">
                            @if($r->audit_datos_anteriores)
                                <div class="col-md-6">
                                    <h6 class="text-danger"><i class="fas fa-arrow-left mr-1"></i>Datos Anteriores</h6>
                                    <pre style="background:#fff5f5;padding:10px;border-radius:5px;font-size:12px;max-height:300px;overflow:auto;">{{ json_encode($r->audit_datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                            @if($r->audit_datos_nuevos)
                                <div class="col-md-{{ $r->audit_datos_anteriores ? '6' : '12' }}">
                                    <h6 class="text-success"><i class="fas fa-arrow-right mr-1"></i>Datos Nuevos</h6>
                                    <pre style="background:#f0fff0;padding:10px;border-radius:5px;font-size:12px;max-height:300px;overflow:auto;">{{ json_encode($r->audit_datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
