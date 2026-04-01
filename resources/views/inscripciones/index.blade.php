@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-user-plus mr-2"></i>Inscripciones</h4>
                    <div>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#modalCargarExcel">
                            <i class="fas fa-file-upload"></i> Cargar Excel
                        </button>
                        <button class="btn btn-dark" onclick="eliminarCargaMasiva()">
                            <i class="fas fa-trash"></i> Eliminar Carga
                        </button>
                        <a href="{{ route('estudiantes.index') }}?incompletos=1" class="btn btn-info" target="_blank">
                            <i class="fas fa-exclamation-triangle"></i> Ver Incompletos
                        </a>
                        <a href="{{ route('inscripciones.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Inscripción
                        </a>
                        <button class="btn btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, apellido o CI..." value="{{ request('buscar') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="pfam_codigo" class="form-control select2" style="width: 100%">
                                    <option value="">Buscar padre...</option>
                                    @foreach($inscripciones->pluck('padreFamilia')->unique('pfam_codigo')->sortBy('pfam_nombres') as $padre)
                                        @if($padre)
                                            <option value="{{ $padre->pfam_codigo }}" {{ request('pfam_codigo') == $padre->pfam_codigo ? 'selected' : '' }}>
                                                {{ $padre->pfam_nombres }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_inicio" class="form-control" placeholder="Fecha inicio" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_fin" class="form-control" placeholder="Fecha fin" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-control select2" style="width: 100%">
                                    <option value="">Todos los estados</option>
                                    <option value="activas" {{ request('estado') == 'activas' ? 'selected' : '' }}>Activas</option>
                                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Anuladas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="tipo_factura" class="form-control select2" style="width: 100%">
                                    <option value="">Todos los tipos</option>
                                    <option value="1" {{ request('tipo_factura') === '1' ? 'selected' : '' }}>Sin Factura</option>
                                    <option value="0" {{ request('tipo_factura') === '0' ? 'selected' : '' }}>Con Factura</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-2">
                                <select name="descuento" class="form-control select2" style="width: 100%">
                                    <option value="">Todos los descuentos</option>
                                    <option value="con_descuento" {{ request('descuento') === 'con_descuento' ? 'selected' : '' }}>Con Descuento</option>
                                    <option value="sin_descuento" {{ request('descuento') === 'sin_descuento' ? 'selected' : '' }}>Sin Descuento</option>
                                    @foreach($descuentos ?? [] as $d)
                                        <option value="{{ $d->desc_id }}" {{ request('descuento') == $d->desc_id ? 'selected' : '' }}>{{ $d->desc_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-10 text-right">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Buscar</button>
                                <a href="{{ route('inscripciones.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaInscripciones">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Recibo</th>
                                <th>Estudiante</th>
                                <th>Padre</th>
                                <th>Curso</th>
                                <th>Gestión</th>
                                <th>Fecha</th>
                                <th>Monto a Cobrar</th>
                                <th>Descuento</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $totalMonto = 0;
                                $totalPagado = 0;
                                $totalSaldo = 0;
                            @endphp
                            @forelse($inscripciones as $i)
                                @php
                                    $mensPagadas = $mensualidadesPagadas[$i->est_codigo] ?? 0;
                                    $esSoloRegistro = $i->insc_monto_pagado == 0;
                                    $totalPagadoEst = $i->insc_monto_pagado + $mensPagadas;

                                    // Meses vencidos: usar primer_mes del estudiante
                                    $primerMesEst = $primerMesPorEst[$i->est_codigo] ?? $mesActualNum;
                                    $mesLimite = max($mesActualNum, $primerMesEst);
                                    $mesesVencidosEst = 0;
                                    for ($mv = 2; $mv < $mesLimite; $mv++) $mesesVencidosEst++;
                                    $mesesCobrables = 10 - $mesesVencidosEst;
                                    $mensualidad = $i->insc_monto_final > 0 ? $i->insc_monto_final / 10 : 0;
                                    $montoACobrar = $mensualidad * $mesesCobrables;
                                    $saldoReal = max(0, $montoACobrar - $totalPagadoEst);

                                    if($i->insc_estado != 0) {
                                        $totalMonto += $montoACobrar;
                                        $totalPagado += $totalPagadoEst;
                                        $totalSaldo += $saldoReal;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $i->insc_codigo }}</td>
                                    <td>{{ $i->pagos->first()->inscpago_recibo ?? 'N/A' }}</td>
                                    <td>{{ $i->estudiante->est_nombres ?? 'N/A' }} {{ $i->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $i->padreFamilia->pfam_nombres ?? 'N/A' }}</td>
                                    <td>{{ $i->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>{{ $i->insc_gestion }}</td>
                                    <td>{{ $i->insc_fecha ? $i->insc_fecha->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        {{ number_format($montoACobrar, 2) }}
                                        @if($mesesVencidosEst > 0)
                                            <br><small class="text-muted">{{ $mesesCobrables }} meses × {{ number_format($mensualidad, 0) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($i->descuentos->count() > 0)
                                            @php $desc = $i->descuentos->first(); @endphp
                                            {{ $desc->desc_nombre }}
                                            @if($desc->desc_porcentaje > 0)
                                                ({{ $desc->desc_porcentaje }}%)
                                            @endif
                                            <br><small>Bs. {{ number_format($i->insc_monto_descuento, 2) }}</small>
                                            <br><button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modalEditarDescuento{{ $i->insc_id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($totalPagadoEst, 2) }}
                                        @if($esSoloRegistro)
                                            <br><small class="text-muted">Mens: {{ number_format($mensPagadas, 0) }}</small>
                                        @else
                                            <br><small class="text-muted">Insc: {{ number_format($i->insc_monto_pagado, 0) }} + Mens: {{ number_format($mensPagadas, 0) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $saldoReal > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                            {{ number_format($saldoReal, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($i->insc_estado == 0)
                                            <span class="badge badge-danger">Anulada</span>
                                        @elseif($esSoloRegistro)
                                            <span class="badge badge-info" title="Pago fue a mensualidad, no a inscripción">Solo Registro</span>
                                        @elseif($i->insc_monto_pagado >= 300)
                                            <span class="badge badge-success">Inscripción Pagada</span>
                                        @else
                                            <span class="badge badge-warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($i->insc_estado != 0)
                                            @if(!$esSoloRegistro && $i->insc_monto_pagado < 300)
                                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalPago{{ $i->insc_id }}">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                            @endif
                                            @if($i->pagos->count() > 0)
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                                                        <i class="fas fa-receipt"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        @foreach($i->pagos as $pago)
                                                            <a class="dropdown-item" onclick="generarRecibo('{{ $pago->inscpago_recibo }}', '{{ addslashes($i->estudiante->est_nombres ?? '') }} {{ addslashes($i->estudiante->est_apellidos ?? '') }}', '{{ addslashes($i->padreFamilia->pfam_nombres ?? '') }}', '{{ addslashes($i->curso->cur_nombre ?? '') }}', '{{ $i->insc_gestion }}', '{{ addslashes($pago->inscpago_concepto) }}', {{ $pago->inscpago_monto }}, '{{ $pago->inscpago_fecha->format('d/m/Y') }}')" href="#">
                                                                {{ $pago->inscpago_recibo }} - Bs. {{ number_format($pago->inscpago_monto, 2) }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            <button class="btn btn-sm btn-danger" onclick="anularInscripcion({{ $i->insc_id }})">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Modal Pago -->
                                <div class="modal fade" id="modalPago{{ $i->insc_id }}">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('inscripciones.pagar', $i->insc_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5>Registrar Pago</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Estudiante:</strong> {{ $i->estudiante->est_nombres }}</p>
                                                    <p><strong>Pago Actual:</strong> Bs. {{ number_format($i->insc_monto_pagado, 2) }}</p>
                                                    <p><strong>Falta para completar 300 Bs:</strong> Bs. {{ number_format(300 - $i->insc_monto_pagado, 2) }}</p>
                                                    <div class="form-group">
                                                        <label>Monto a Pagar</label>
                                                        <input type="number" step="0.01" name="monto" class="form-control" max="{{ 300 - $i->insc_monto_pagado }}" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Concepto</label>
                                                        <input type="text" name="concepto" class="form-control" value="Pago de inscripción">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Registrar Pago</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Editar Descuento -->
                                <div class="modal fade" id="modalEditarDescuento{{ $i->insc_id }}">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('inscripciones.actualizar-descuento', $i->insc_id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5>Editar Inscripción</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Estudiante:</strong> {{ $i->estudiante->est_nombres }} {{ $i->estudiante->est_apellidos }}</p>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-10" id="divPadreEdit{{ $i->insc_id }}">
                                                            <div class="form-group">
                                                                <label>Padre/Tutor</label>
                                                                <select name="pfam_codigo" id="selectPadreEdit{{ $i->insc_id }}" class="form-control select2-modal" style="width: 100%" data-est-codigo="{{ $i->est_codigo }}" data-pfam-actual="{{ $i->pfam_codigo }}">
                                                                    <option value="">Cargando...</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-10" id="divOtroPadreEdit{{ $i->insc_id }}" style="display:none;">
                                                            <div class="form-group">
                                                                <label>Nombre del Padre/Tutor</label>
                                                                <input type="text" name="pfam_nombre_nuevo" id="pfamNuevoEdit{{ $i->insc_id }}" class="form-control" placeholder="Ingrese nombre completo">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-group">
                                                                <label>&nbsp;</label>
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input" id="checkOtroPadreEdit{{ $i->insc_id }}">
                                                                    <label class="form-check-label" for="checkOtroPadreEdit{{ $i->insc_id }}" title="Registrar nuevo padre">Otro</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Tipo de Descuento</label>
                                                        <select name="desc_id" class="form-control select2-modal" required style="width: 100%">
                                                            <option value="">Seleccione...</option>
                                                            @foreach($descuentos ?? [] as $d)
                                                                <option value="{{ $d->desc_id }}" 
                                                                    {{ $i->descuentos->first() && $i->descuentos->first()->desc_id == $d->desc_id ? 'selected' : '' }}>
                                                                    {{ $d->desc_nombre }} ({{ $d->desc_porcentaje }}%)
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Actualizar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr><td colspan="13" class="text-center">No hay inscripciones</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="7" class="text-right"><strong>TOTALES:</strong></td>
                                <td><strong>{{ number_format($totalMonto, 2) }}</strong></td>
                                <td></td>
                                <td><strong>{{ number_format($totalPagado, 2) }}</strong></td>
                                <td><strong>{{ number_format($totalSaldo, 2) }}</strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        </table>
                    </div>
                    {{ $inscripciones->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cargar Excel -->
<div class="modal fade" id="modalCargarExcel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('inscripciones.cargar-excel') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5>Cargar Inscripciones desde Excel</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Formato del Excel:</strong><br>
                        - Columna C: CI/ESTUDIANTE<br>
                        - Columna D: NOMBRE<br>
                        - Columna E: CURSO<br>
                        - Columna I: SUB TOTAL (monto con descuento)<br>
                        - Columna K: TOTAL/SALDO (monto sin descuento)<br>
                        - Columna L: % DESCUENTO<br>
                        - Columna M: DESCUENTO (monto)<br>
                        - Columna N: CANTIDAD DE MESES<br>
                        - Columna O: CUOTA<br>
                        - Columna P: MESES PAGADOS
                    </div>
                    <div class="form-group">
                        <label>Mes de Inscripción *</label>
                        <select name="mes_inscripcion" class="form-control" required>
                            <option value="1">Enero</option>
                            <option value="2" selected>Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                        <small class="form-text text-muted">Mes en que se realizó el pago de inscripción</small>
                    </div>
                    <div class="form-group">
                        <label>Archivo Excel *</label>
                        <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cargar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2 en filtros
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: function() {
            return $(this).find('option:first').text();
        },
        allowClear: true
    });

    // Inicializar Select2 en modales
    $('.modal').on('shown.bs.modal', function() {
        var modal = $(this);
        modal.find('.select2-modal').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: modal
        });

        // Cargar padres del estudiante en modales de edición
        modal.find('[id^="selectPadreEdit"]').each(function() {
            var select = $(this);
            var estCodigo = select.data('est-codigo');
            var pfamActual = select.data('pfam-actual');
            var modalId = select.attr('id').replace('selectPadreEdit', '');

            if (estCodigo) {
                @php
                // Cargar padres directamente desde el servidor
                @endphp
                var inscId = modalId;
                var inscripcion = @json($inscripciones->keyBy('insc_id'));
                
                if (inscripcion[inscId]) {
                    var estudiante = inscripcion[inscId].estudiante;
                    if (estudiante && estudiante.padres) {
                        select.empty().append('<option value="">Seleccione padre/tutor...</option>');
                        estudiante.padres.forEach(function(padre) {
                            var selected = padre.pfam_codigo == pfamActual ? 'selected' : '';
                            select.append('<option value="' + padre.pfam_codigo + '" ' + selected + '>' + padre.pfam_nombres + ' - CI: ' + (padre.pfam_ci || 'N/A') + '</option>');
                        });
                        select.trigger('change');
                    }
                }
            }

            // Manejar checkbox de otro padre
            $('#checkOtroPadreEdit' + modalId).on('change', function() {
                if ($(this).is(':checked')) {
                    $('#divPadreEdit' + modalId).hide();
                    $('#divOtroPadreEdit' + modalId).show();
                    $('#selectPadreEdit' + modalId).prop('required', false).val('').trigger('change');
                    $('#pfamNuevoEdit' + modalId).prop('required', true);
                } else {
                    $('#divPadreEdit' + modalId).show();
                    $('#divOtroPadreEdit' + modalId).hide();
                    $('#selectPadreEdit' + modalId).prop('required', true);
                    $('#pfamNuevoEdit' + modalId).prop('required', false).val('');
                }
            });
        });
    });
});

function exportarExcel() {
    var wb = XLSX.utils.table_to_book(document.getElementById('tablaInscripciones'));
    XLSX.writeFile(wb, 'inscripciones_' + new Date().getTime() + '.xlsx');
}

function exportarPDF() {
    var params = new URLSearchParams(window.location.search);
    window.open('{{ route("inscripciones.reporte-pdf") }}?' + params.toString(), '_blank');
}

function generarRecibo(recibo, estudiante, padre, curso, gestion, concepto, monto, fecha) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        unit: 'pt',
        format: [612, 396],
        orientation: 'landscape'
    });
    
    function numeroATexto(num) {
        const unidades = ['', 'Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis', 'Siete', 'Ocho', 'Nueve'];
        const decenas = ['', '', 'Veinte', 'Treinta', 'Cuarenta', 'Cincuenta', 'Sesenta', 'Setenta', 'Ochenta', 'Noventa'];
        const especiales = ['Diez', 'Once', 'Doce', 'Trece', 'Catorce', 'Quince', 'Dieciséis', 'Diecisiete', 'Dieciocho', 'Diecinueve'];
        const centenas = ['', 'Ciento', 'Doscientos', 'Trescientos', 'Cuatrocientos', 'Quinientos', 'Seiscientos', 'Setecientos', 'Ochocientos', 'Novecientos'];
        
        if (num === 0) return 'Cero';
        if (num === 100) return 'Cien';
        
        let texto = '';
        
        if (num >= 100) {
            texto += centenas[Math.floor(num / 100)] + ' ';
            num %= 100;
        }
        
        if (num >= 20) {
            texto += decenas[Math.floor(num / 10)];
            if (num % 10 > 0) texto += ' y ' + unidades[num % 10];
        } else if (num >= 10) {
            texto += especiales[num - 10];
        } else if (num > 0) {
            texto += unidades[num];
        }
        
        return texto.trim();
    }
    
    function dibujarRecibo(tipoRecibo) {
        doc.setLineWidth(1);
        doc.setDrawColor(0, 0, 0);
        doc.setLineDash([0.5, 1]);
        doc.rect(10, 10, 592, 376);
        doc.setLineDash([]);
        
        // Encabezado izquierdo
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('U.E. PRIVADA INTERANDINO BOLIVIANO', 15, 25);
        doc.setFontSize(6.5);
        doc.setFont(undefined, 'normal');
        doc.text('C/ VICTOR GUTIERREZ Nº 3339', 15, 35);
        doc.text('TELÉFONO: 2840320 - 67304340', 15, 43);
        
        // Fecha y monto (derecha, 3 cuadros separados con bordes redondeados)
        doc.setLineWidth(1);
        
        // Cuadro 1: Fecha (superior)
        doc.roundedRect(470, 10, 128, 21, 2, 2);
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('Día/Mes/Año', 534, 18, { align: 'center' });
        doc.setFontSize(12);
        doc.text(fecha, 534, 28, { align: 'center' });
        
        // Cuadro 2: Monto Bs. (medio con separación)
        doc.roundedRect(470, 34, 128, 16, 2, 2);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('Bs.', 478, 44);
        doc.setFontSize(10);
        doc.text(monto.toFixed(2), 588, 44, { align: 'right' });
        
        // Cuadro 3: Monto $us. (inferior con separación)
        doc.roundedRect(470, 53, 128, 16, 2, 2);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('$us.', 478, 63);
        doc.line(505, 61, 588, 61);
        
        // RECIBO con código numérico
        const soloNumero = recibo.replace(/\D/g, '');
        const numeroFormateado = soloNumero.padStart(5, '0');
        doc.setFontSize(28);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBO-' + numeroFormateado, 306, 85, { align: 'center' });
        
        // Cancelado por
        doc.setFontSize(11);
        doc.setFont(undefined, 'bold');
        doc.text('Cancelado por:', 15, 110);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([0.5, 1]);
        doc.line(100, 112, 585, 112);
        doc.setLineDash([]);
        doc.text(padre, 105, 110);
        
        // La suma de
        const parteEntera = Math.floor(monto);
        const parteDecimal = Math.round((monto - parteEntera) * 100);
        const montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
        
        doc.setFontSize(11);
        doc.setFont(undefined, 'bold');
        doc.text('La suma de:', 15, 130);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([0.5, 1]);
        doc.line(85, 132, 585, 132);
        doc.setLineDash([]);
        doc.text(montoLiteral, 90, 130);
        
        // Por concepto de - concepto arriba, estudiante y curso abajo
        doc.setFontSize(11);
        doc.setFont(undefined, 'bold');
        doc.text('Por concepto de:', 15, 150);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([0.5, 1]);
        doc.line(110, 152, 585, 152);
        doc.setLineDash([]);
        doc.text(concepto + ' - Gestión: ' + gestion, 115, 150);
        
        // Estudiante y curso en línea siguiente
        doc.setLineDash([0.5, 1]);
        doc.line(15, 172, 585, 172);
        doc.setLineDash([]);
        doc.text('Est: ' + estudiante + '  -  Curso: ' + curso, 15, 170);
        
        // Líneas punteadas
        let yPos = 190;
        for (let i = 0; i < 4; i++) {
            doc.setLineDash([0.5, 1]);
            doc.line(15, yPos, 597, yPos);
            yPos += 22;
        }
        doc.setLineDash([]);
        
        // TOTAL
        yPos = 295;
        doc.setLineWidth(1);
        doc.setLineDash([0.5, 1]);
        doc.line(15, yPos, 597, yPos);
        doc.setLineDash([]);
        doc.setFontSize(13);
        doc.setFont(undefined, 'bold');
        doc.text('TOTAL', 480, yPos + 15);
        doc.text(monto.toFixed(2), 585, yPos + 15, { align: 'right' });
        
        // Sección de firmas
        yPos = 320;
        
        // Izquierda
        doc.setLineDash([0.5, 1]);
        doc.line(80, yPos + 35, 220, yPos + 35);
        doc.setLineDash([]);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBÍ CONFORME', 150, yPos + 60, { align: 'center' });
        
        // Derecha - Firmas completas
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text('Firma:', 320, yPos + 20);
        doc.setLineDash([0.5, 1]);
        doc.line(350, yPos + 22, 450, yPos + 22);
        doc.setLineDash([]);
        doc.text('C.I.:', 460, yPos + 20);
        doc.setLineDash([0.5, 1]);
        doc.line(480, yPos + 22, 580, yPos + 22);
        doc.setLineDash([]);
        doc.text('Nom. Y Ap.:', 320, yPos + 38);
        doc.setLineDash([0.5, 1]);
        doc.line(370, yPos + 40, 580, yPos + 40);
        doc.setLineDash([]);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('ENTREGUÉ CONFORME', 450, yPos + 60, { align: 'center' });
    }
    
    dibujarRecibo('ORIGINAL');
    doc.addPage([612, 396]);
    dibujarRecibo('COPIA');
    
    doc.save('recibo_' + recibo + '.pdf');
}

function anularInscripcion(id) {
    if (confirm('¿Está seguro de anular esta inscripción?')) {
        $.ajax({
            url: '/inscripciones/' + id + '/anular',
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                alert('Inscripción anulada');
                location.reload();
            },
            error: function() {
                alert('Error al anular');
            }
        });
    }
}

function eliminarCargaMasiva() {
    if (confirm('¿Está seguro de eliminar TODAS las inscripciones cargadas desde Excel de esta gestión?\n\nEsto eliminará inscripciones, pagos y mensualidades relacionadas.')) {
        $.ajax({
            url: '{{ route("inscripciones.eliminar-carga") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                alert('Registros eliminados');
                location.reload();
            },
            error: function() {
                alert('Error al eliminar');
            }
        });
    }
}
</script>
@endsection
