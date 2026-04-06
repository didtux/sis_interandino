@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3 text-white">
                <h4><i class="fas fa-cog mr-2 text-white"></i>Configuración de Notas - Gestión {{ $gestion }}</h4>
                <a href="{{ route('notas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success-modern">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        {{-- Periodos --}}
        <div class="col-md-7">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Periodos (Trimestres/Bimestres)</h5>
                    <button class="btn btn-primary-modern btn-sm" onclick="modalPeriodo()">
                        <i class="fas fa-plus mr-1"></i>Nuevo
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Nombre</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periodos as $p)
                                <tr>
                                    <td>{{ $p->periodo_numero }}</td>
                                    <td>{{ $p->periodo_nombre }}</td>
                                    <td>{{ $p->periodo_fecha_inicio->format('d/m/Y') }}</td>
                                    <td>{{ $p->periodo_fecha_fin->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="modern-badge {{ $p->periodo_estado ? 'badge-success-modern' : 'badge-secondary' }}">
                                            {{ $p->periodo_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-action btn-action-edit btn-sm" onclick="modalPeriodo({{ json_encode($p) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('notas.eliminar-periodo', $p->periodo_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete btn-sm" onclick="return confirm('¿Eliminar este periodo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No hay periodos configurados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Dimensiones --}}
        <div class="col-md-5">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Dimensiones</h5>
                    <button class="btn btn-primary-modern btn-sm" onclick="modalDimension()">
                        <i class="fas fa-plus mr-1"></i>Nueva
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Dimensión</th>
                                <th>Valor Máx.</th>
                                <th>Columnas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalMax = 0; @endphp
                            @forelse($dimensiones as $d)
                                @php $totalMax += $d->dimension_valor_max; @endphp
                                <tr>
                                    <td>{{ $d->dimension_orden }}</td>
                                    <td><strong>{{ $d->dimension_nombre }}</strong></td>
                                    <td><span class="modern-badge badge-primary-modern">{{ $d->dimension_valor_max }}</span></td>
                                    <td><span class="modern-badge badge-warning-modern">{{ $d->dimension_columnas }}</span></td>
                                    <td>
                                        <button class="btn btn-action btn-action-edit btn-sm" onclick="modalDimension({{ json_encode($d) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('notas.eliminar-dimension', $d->dimension_id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-action btn-action-delete btn-sm" onclick="return confirm('¿Eliminar esta dimensión?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No hay dimensiones configuradas</td></tr>
                            @endforelse
                        </tbody>
                        @if($dimensiones->count())
                            <tfoot>
                                <tr style="background:#f8f9fa;font-weight:bold;">
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td><span class="modern-badge {{ $totalMax == 100 ? 'badge-success-modern' : 'badge-danger-modern' }}">{{ $totalMax }}/100</span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Periodo --}}
<div class="modal fade" id="modalPeriodo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;">
                <h5 class="modal-title" id="tituloPeriodo">Nuevo Periodo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.guardar-periodo') }}" method="POST">
                @csrf
                <input type="hidden" name="periodo_id" id="periodo_id">
                <input type="hidden" name="periodo_gestion" value="{{ $gestion }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="periodo_nombre" id="periodo_nombre" class="form-control" required placeholder="Ej: 1er Trimestre">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>N° Orden <span class="text-danger">*</span></label>
                                <input type="number" name="periodo_numero" id="periodo_numero" class="form-control" required min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="periodo_fecha_inicio" id="periodo_fecha_inicio" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha Fin <span class="text-danger">*</span></label>
                                <input type="date" name="periodo_fecha_fin" id="periodo_fecha_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="periodo_estado" id="periodo_estado" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-modern"><i class="fas fa-save mr-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Dimensión --}}
<div class="modal fade" id="modalDimension" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#9b59b6,#8e44ad);color:#fff;">
                <h5 class="modal-title" id="tituloDimension">Nueva Dimensión</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.guardar-dimension') }}" method="POST">
                @csrf
                <input type="hidden" name="dimension_id" id="dimension_id">
                <input type="hidden" name="dimension_gestion" value="{{ $gestion }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="dimension_nombre" id="dimension_nombre" class="form-control" required placeholder="Ej: SER, SABER, HACER">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valor Máximo <span class="text-danger">*</span></label>
                                <input type="number" name="dimension_valor_max" id="dimension_valor_max" class="form-control" required min="1" max="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Columnas <span class="text-danger">*</span></label>
                                <input type="number" name="dimension_columnas" id="dimension_columnas" class="form-control" required min="1" max="10" value="1">
                                <small class="text-muted">Cantidad de sub-notas</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" name="dimension_orden" id="dimension_orden" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="dimension_estado" id="dimension_estado" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" style="background:#9b59b6;color:#fff;"><i class="fas fa-save mr-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function modalPeriodo(data = null) {
    if (data) {
        $('#tituloPeriodo').text('Editar Periodo');
        $('#periodo_id').val(data.periodo_id);
        $('#periodo_nombre').val(data.periodo_nombre);
        $('#periodo_numero').val(data.periodo_numero);
        $('#periodo_fecha_inicio').val(data.periodo_fecha_inicio);
        $('#periodo_fecha_fin').val(data.periodo_fecha_fin);
        $('#periodo_estado').val(data.periodo_estado);
    } else {
        $('#tituloPeriodo').text('Nuevo Periodo');
        $('#modalPeriodo form')[0].reset();
        $('#periodo_id').val('');
    }
    $('#modalPeriodo').modal('show');
}

function modalDimension(data = null) {
    if (data) {
        $('#tituloDimension').text('Editar Dimensión');
        $('#dimension_id').val(data.dimension_id);
        $('#dimension_nombre').val(data.dimension_nombre);
        $('#dimension_valor_max').val(data.dimension_valor_max);
        $('#dimension_columnas').val(data.dimension_columnas);
        $('#dimension_orden').val(data.dimension_orden);
        $('#dimension_estado').val(data.dimension_estado);
    } else {
        $('#tituloDimension').text('Nueva Dimensión');
        $('#modalDimension form')[0].reset();
        $('#dimension_id').val('');
    }
    $('#modalDimension').modal('show');
}
</script>
@endsection
