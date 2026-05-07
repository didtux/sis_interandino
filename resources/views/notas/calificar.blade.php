@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <h4 class="mb-1"><i class="fas fa-clipboard-list mr-2"></i>{{ $periodo->periodo_nombre }}</h4>
                            <div class="mt-2">
                                <span class="modern-badge badge-primary-modern">{{ $asignacion->curso->cur_nombre }}</span>
                                <span class="modern-badge badge-warning-modern">{{ $asignacion->materia->mat_nombre }}</span>
                                <span class="modern-badge badge-success-modern">{{ $asignacion->docente->doc_nombres }} {{ $asignacion->docente->doc_apellidos }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Inicio:</small> <strong>{{ $periodo->periodo_fecha_inicio->format('d/m/Y') }}</strong><br>
                            <small class="text-muted">Fin:</small> <strong>{{ $periodo->periodo_fecha_fin->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-4 text-right">
                            @php
                                $badgeClass = match($estadoNotas) { 0 => 'badge-secondary', 1 => 'badge-warning', 2 => 'badge-success', 3 => 'badge-danger', default => 'badge-secondary' };
                                $estadoTexto = match($estadoNotas) { 0 => 'Borrador', 1 => 'Enviado', 2 => 'Aprobado', 3 => 'Rechazado', default => 'Sin notas' };
                            @endphp
                            <span class="badge {{ $badgeClass }} p-2" style="font-size:0.9rem;">{{ $estadoTexto }}</span>
                            @if(!$enRango)
                                <br><span class="badge badge-danger p-1 mt-1" style="font-size:0.75rem;"><i class="fas fa-lock mr-1"></i>Fuera de periodo</span>
                            @endif
                            <br><a href="{{ route('notas.index') }}" class="btn btn-secondary btn-sm mt-2"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
            @endif

            {{-- Notificación de aprobación/rechazo --}}
            @if(in_array($estadoNotas, [2, 3]))
                <div class="alert {{ $estadoNotas == 2 ? 'alert-success' : 'alert-danger' }} py-3">
                    <div class="d-flex align-items-start">
                        <i class="fas {{ $estadoNotas == 2 ? 'fa-check-circle' : 'fa-times-circle' }} fa-2x mr-3 mt-1"></i>
                        <div>
                            <h5 class="mb-1">{{ $estadoNotas == 2 ? 'Notas Aprobadas' : 'Notas Rechazadas' }}</h5>
                            @if($observacionAdmin)
                                <p class="mb-1"><strong>Observación:</strong> {{ $observacionAdmin }}</p>
                            @endif
                            <small class="text-white">
                                {{ $estadoNotas == 2 ? 'Aprobado' : 'Rechazado' }} por
                                <strong>{{ $aprobadoPor ? $aprobadoPor->us_nombres . ' ' . $aprobadoPor->us_apellidos : 'Administrador' }}</strong>
                                @if($fechaAprobacion)
                                    el {{ \Carbon\Carbon::parse($fechaAprobacion)->format('d/m/Y H:i') }}
                                @endif
                            </small>
                            @if($estadoNotas == 3)
                                <br><small class="text-danger font-weight-bold"><i class="fas fa-info-circle mr-1"></i>Puede corregir y volver a enviar las notas.</small>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @php $totalCols = 2; foreach($dimensiones as $d) { $totalCols += $d->dimension_columnas + 1; } $totalCols += 1; @endphp

            <form action="{{ route('notas.guardar') }}" method="POST" id="formNotas">
                @csrf
                <input type="hidden" name="curmatdoc_id" value="{{ $asignacion->curmatdoc_id }}">
                <input type="hidden" name="periodo_id" value="{{ $periodo->periodo_id }}">

                <div class="card modern-card">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">EVALUACIÓN DEL MAESTRO</h5>
                        <div>
                            <a href="{{ route('notas.reporte-valoracion', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" class="btn btn-danger btn-sm" target="_blank">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </a>
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalImportarExcel">
                                <i class="fas fa-file-excel mr-1"></i>Importar Excel
                            </button>
                            @if($esEditable)
                                <button type="submit" name="accion" value="guardar" class="btn btn-secondary btn-sm"><i class="fas fa-save mr-1"></i>Guardar Borrador</button>
                                <button type="submit" name="accion" value="enviar" class="btn btn-primary-modern btn-sm" onclick="return confirm('¿Enviar notas para aprobación?')"><i class="fas fa-paper-plane mr-1"></i>Enviar</button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div style="overflow-x:auto;">
                            <table class="modern-table" style="font-size:0.85rem;" id="tablaNotas">
                                <thead>
                                    <tr style="background:#2c3e50;color:#fff;">
                                        <th rowspan="2" style="width:40px;text-align:center;">N°</th>
                                        <th rowspan="2" style="min-width:200px;">NÓMINA DE ESTUDIANTES</th>
                                        @php
                                            $colores = ['#e74c3c','#3498db','#2ecc71','#9b59b6','#f39c12','#1abc9c','#e67e22'];
                                        @endphp
                                        @foreach($dimensiones as $idx => $dim)
                                            <th colspan="{{ $dim->dimension_columnas + 1 }}" style="text-align:center;background:{{ $colores[$idx % count($colores)] }};color:#fff;">
                                                {{ $dim->dimension_nombre }}/{{ $dim->dimension_valor_max }}
                                            </th>
                                        @endforeach
                                        <th rowspan="2" style="text-align:center;background:#f39c12;color:#fff;width:70px;">PROM.<br>TRIM.</th>
                                    </tr>
                                    <tr style="background:#34495e;color:#fff;font-size:0.75rem;">
                                        @foreach($dimensiones as $dim)
                                            @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                                                <th style="text-align:center;width:60px;">Nota {{ $c }}</th>
                                            @endfor
                                            <th style="text-align:center;width:60px;background:rgba(0,0,0,0.2);color:#fff;">PROM.</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($estudiantes as $i => $est)
                                        @php
                                            $nota = $notasExistentes[$est->est_codigo] ?? null;
                                            $detallesMap = [];
                                            if ($nota) {
                                                foreach ($nota->detalles as $det) {
                                                    $detallesMap[$det->dimension_id][$det->columna_num] = $det->detalle_valor;
                                                }
                                            }
                                        @endphp
                                        @php $retirado = ($est->est_visible ?? 1) == 0; @endphp
                                        <tr style="{{ $retirado ? 'background:#ffe6e6;' : '' }}">
                                            <td style="text-align:center;font-weight:bold;color:{{ $retirado ? '#c0392b' : 'inherit' }};">{{ $est->lista_numero ?? ($i + 1) }}</td>
                                            <td style="white-space:nowrap;{{ $retirado ? 'color:#c0392b;font-weight:600;' : '' }}">
                                                {{ $est->est_apellidos }} {{ $est->est_nombres }}
                                                @if($retirado)<span class="badge badge-danger ml-1" style="font-size:9px;">RETIRADO</span>@endif
                                            </td>
                                            @foreach($dimensiones as $dim)
                                                @for($c = 1; $c <= $dim->dimension_columnas; $c++)
                                                    @php $val = $detallesMap[$dim->dimension_id][$c] ?? ''; @endphp
                                                    <td>
                                                        <input type="number"
                                                            name="notas[{{ $est->est_codigo }}][{{ $dim->dimension_id }}][{{ $c }}]"
                                                            class="form-control form-control-sm input-nota input-dim-{{ $dim->dimension_id }}"
                                                            data-dim="{{ $dim->dimension_id }}"
                                                            data-max="{{ $dim->dimension_valor_max }}"
                                                            data-cols="{{ $dim->dimension_columnas }}"
                                                            value="{{ $val > 0 ? $val : '' }}"
                                                            min="0" max="{{ $dim->dimension_valor_max }}" step="0.1"
                                                            {{ !$esEditable ? 'readonly' : '' }}>
                                                    </td>
                                                @endfor
                                                <td style="text-align:center;font-weight:bold;background:rgba(0,0,0,0.03);" class="prom-dim-{{ $dim->dimension_id }}">0</td>
                                            @endforeach
                                            <td style="text-align:center;font-weight:bold;font-size:1rem;background:#fef3cd;" class="prom-trim">{{ $nota->nota_promedio_trimestral ?? 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ $totalCols }}"><div class="empty-state"><i class="fas fa-users"></i><h5>No hay estudiantes</h5></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($esEditable && $estudiantes->count())
                        <div class="card-footer text-right">
                            <button type="submit" name="accion" value="guardar" class="btn btn-secondary"><i class="fas fa-save mr-1"></i>Guardar Borrador</button>
                            <button type="submit" name="accion" value="enviar" class="btn btn-primary-modern" onclick="return confirm('¿Enviar notas para aprobación?')"><i class="fas fa-paper-plane mr-1"></i>Enviar</button>
                        </div>
                    @endif
                </div>
            </form>

            @if(auth()->user()->rol_id == 1 && $estadoNotas == 1)
                <div class="card modern-card mt-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c3e50,#34495e);color:#fff;">
                        <h5 class="mb-0"><i class="fas fa-gavel mr-2"></i>Aprobación de Notas</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('notas.aprobar', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}" method="POST" id="formAprobar">
                            @csrf
                            <input type="hidden" name="accion" id="accionAprobar" value="">
                            <div class="form-group">
                                <label>Observación (opcional)</label>
                                <textarea name="observacion" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="button" class="btn btn-success" onclick="if(confirm('¿Aprobar estas notas?')){document.getElementById('accionAprobar').value='aprobar';document.getElementById('formAprobar').submit();}"><i class="fas fa-check mr-1"></i>Aprobar</button>
                            <button type="button" class="btn btn-danger" onclick="var obs=document.querySelector('#formAprobar textarea[name=observacion]').value;if(!obs.trim()){alert('Debe ingresar una observación para rechazar');document.querySelector('#formAprobar textarea[name=observacion]').focus();return;}if(confirm('¿Rechazar estas notas?')){document.getElementById('accionAprobar').value='rechazar';document.getElementById('formAprobar').submit();}"><i class="fas fa-times mr-1"></i>Rechazar</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal Importar Excel --}}
<div class="modal fade" id="modalImportarExcel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-file-excel mr-2"></i>Importar desde Excel</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('notas.importar-excel') }}" method="POST" enctype="multipart/form-data" id="formImportarExcel">
                @csrf
                <input type="hidden" name="curmatdoc_id" value="{{ $asignacion->curmatdoc_id }}">
                <input type="hidden" name="periodo_id" value="{{ $periodo->periodo_id }}">
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size:0.85rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Seleccione el archivo Excel con formato de <strong>Registro Pedagógico</strong>.
                        Los datos se cargarán como <strong>borrador</strong> para que pueda revisarlos antes de confirmar.
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Archivo Excel <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" name="archivo" class="custom-file-input" id="archivoExcel" accept=".xlsx,.xls" required>
                            <label class="custom-file-label" for="archivoExcel" data-browse="Buscar">Seleccionar archivo...</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">¿Qué desea importar? <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-4">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tipo_notas" name="tipo_importacion" value="notas" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="tipo_notas">
                                        <i class="fas fa-pen text-primary mr-1"></i>Notas
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tipo_asistencia" name="tipo_importacion" value="asistencia" class="custom-control-input">
                                    <label class="custom-control-label" for="tipo_asistencia">
                                        <i class="fas fa-user-check text-info mr-1"></i>Asistencia
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tipo_ambos" name="tipo_importacion" value="ambos" class="custom-control-input">
                                    <label class="custom-control-label" for="tipo_ambos">
                                        <i class="fas fa-layer-group text-success mr-1"></i>Ambos
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Trimestre destino</label>
                        <input type="text" class="form-control" value="{{ $periodo->periodo_nombre }} (Trimestre {{ $periodo->periodo_numero }})" readonly>
                        <small class="text-muted">Se importará la hoja correspondiente al trimestre {{ $periodo->periodo_numero }} del Excel.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnImportar">
                        <i class="fas fa-upload mr-1"></i>Cargar y Previsualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.input-nota { width:65px!important;text-align:center;padding:2px 4px;font-size:0.85rem; }
.input-nota:focus { border-color:#3498db;box-shadow:0 0 0 .15rem rgba(52,152,219,.25); }
#tablaNotas td,#tablaNotas th { padding:4px 6px;vertical-align:middle; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Custom file input label
    $('#archivoExcel').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Seleccionar archivo...');
    });

    // Loading al enviar importación
    $('#formImportarExcel').on('submit', function() {
        var btn = $('#btnImportar');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...');
    });

    // Dimensiones config desde PHP
    var dimensiones = @json($dimensiones->map(fn($d) => ['id' => $d->dimension_id, 'max' => $d->dimension_valor_max, 'cols' => $d->dimension_columnas]));

    function calcularFila(row) {
        var $row = $(row);
        var promTrim = 0;

        dimensiones.forEach(function(dim) {
            var inputs = $row.find('.input-dim-' + dim.id);
            var suma = 0, count = 0;

            inputs.each(function() {
                var v = parseFloat($(this).val());
                if (!isNaN(v) && $(this).val() !== '') {
                    suma += v;
                    count++;
                }
            });

            // Promedio: si 1 columna = valor directo, si múltiples = promedio de las ingresadas
            var prom = 0;
            if (count > 0) {
                prom = dim.cols === 1 ? suma : suma / count;
            }
            // Limitar al máximo de la dimensión y redondear
            prom = Math.min(prom, dim.max);
            prom = Math.round(prom);
            $row.find('.prom-dim-' + dim.id).text(prom);
            promTrim += prom;
        });

        $row.find('.prom-trim').text(Math.round(promTrim));
    }

    $('.input-nota').on('input change', function() {
        calcularFila($(this).closest('tr'));
    });

    $('#tablaNotas tbody tr').each(function() {
        calcularFila(this);
    });
});
</script>
@endsection
