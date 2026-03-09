@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-money-bill mr-2"></i>Pagos de Transporte</h4>
                    <div>
                        <button class="btn btn-success" data-toggle="modal" data-target="#modalReporteIngresos">
                            <i class="fas fa-file-invoice-dollar"></i> Reporte de Ingresos
                        </button>
                        <button class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                        <a href="{{ route('pagos-transporte.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Pago
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha Fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estudiante</label>
                                    <select name="estudiante" id="estudiante-select" class="form-control select2">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Estudiante::visible()->get() as $est)
                                            <option value="{{ $est->est_codigo }}" {{ request('estudiante') == $est->est_codigo ? 'selected' : '' }}>
                                                {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ruta</label>
                                    <select name="ruta" id="ruta-select" class="form-control select2">
                                        <option value="">Todas</option>
                                        @foreach(\App\Models\Ruta::where('ruta_estado', 1)->with('asignaciones.vehiculo')->get() as $ruta)
                                            @php
                                                $asignacion = $ruta->asignaciones ? $ruta->asignaciones->where('asig_estado', 1)->first() : null;
                                                $nombreBus = '';
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    if ($asignacion->vehiculo->veh_numero_bus) {
                                                        $nombreBus = ' - Bus ' . $asignacion->vehiculo->veh_numero_bus;
                                                    }
                                                    $nombreBus .= ' - ' . $asignacion->vehiculo->veh_placa;
                                                }
                                            @endphp
                                            <option value="{{ $ruta->ruta_codigo }}" {{ request('ruta') == $ruta->ruta_codigo ? 'selected' : '' }}>
                                                {{ $ruta->ruta_nombre }}{{ $nombreBus }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="vigente" {{ request('estado') == 'vigente' ? 'selected' : '' }}>Vigente</option>
                                        <option value="vencido" {{ request('estado') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                                        <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                                <a href="{{ route('pagos-transporte.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped" id="tablaPagos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Vehículo</th>
                                <th>Ruta</th>
                                <th>Chofer</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Fecha Pago</th>
                                <th>Vigencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalMonto = 0; @endphp
                            @forelse($pagos as $p)
                                @php $totalMonto += $p->tpago_monto; @endphp
                                <tr>
                                    <td>{{ $p->tpago_codigo }}</td>
                                    <td><strong>{{ $p->estudiante ? $p->estudiante->est_nombres . ' ' . $p->estudiante->est_apellidos : 'Sin estudiante' }}</strong></td>
                                    <td>
                                        @php
                                            $vehiculoInfo = '';
                                            if ($p->estudiante && $p->estudiante->rutaTransporte && $p->estudiante->rutaTransporte->ruta) {
                                                $asignacion = $p->estudiante->rutaTransporte->ruta->asignaciones->where('asig_estado', 1)->first();
                                                if ($asignacion && $asignacion->vehiculo) {
                                                    $vehiculoInfo = $asignacion->vehiculo->veh_placa;
                                                }
                                            }
                                        @endphp
                                        @if($vehiculoInfo)
                                            <span class="badge badge-secondary">{{ $vehiculoInfo }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $rutaInfo = '';
                                            if ($p->estudiante && $p->estudiante->rutaTransporte && $p->estudiante->rutaTransporte->ruta) {
                                                $rutaInfo = $p->estudiante->rutaTransporte->ruta->ruta_nombre;
                                            }
                                        @endphp
                                        @if($rutaInfo)
                                            <small>{{ $rutaInfo }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $choferInfo = '';
                                            if ($p->estudiante && $p->estudiante->rutaTransporte && $p->estudiante->rutaTransporte->ruta) {
                                                $asignacion = $p->estudiante->rutaTransporte->ruta->asignaciones->where('asig_estado', 1)->first();
                                                if ($asignacion && $asignacion->chofer) {
                                                    $choferInfo = $asignacion->chofer->chof_nombres . ' ' . $asignacion->chofer->chof_apellidos;
                                                }
                                            }
                                        @endphp
                                        @if($choferInfo)
                                            <small>{{ $choferInfo }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-info">{{ ucfirst($p->tpago_tipo) }}</span></td>
                                    <td>Bs. {{ number_format($p->tpago_monto, 2) }}</td>
                                    <td>{{ $p->tpago_fecha_pago }}</td>
                                    <td>{{ $p->tpago_fecha_inicio }} - {{ $p->tpago_fecha_fin }}</td>
                                    <td>
                                        @if($p->tpago_estado == 'vigente')
                                            <span class="badge badge-success">Vigente</span>
                                        @elseif($p->tpago_estado == 'vencido')
                                            <span class="badge badge-danger">Vencido</span>
                                        @else
                                            <span class="badge badge-secondary">Cancelado</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="generarReciboTransporte('{{ $p->tpago_codigo }}', '{{ addslashes($p->estudiante->est_nombres ?? 'Sin estudiante') }} {{ addslashes($p->estudiante->est_apellidos ?? '') }}', '{{ addslashes($p->estudiante->curso->cur_nombre ?? '') }}', '{{ ucfirst($p->tpago_tipo) }}', {{ $p->tpago_monto }}, '{{ $p->tpago_fecha_pago }}', '{{ $p->tpago_fecha_inicio }}', '{{ $p->tpago_fecha_fin }}')">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <a href="{{ route('pagos-transporte.edit', $p->tpago_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('pagos-transporte.destroy', $p->tpago_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar pago?')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center">No hay pagos registrados</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                                <td colspan="6"><strong>Bs. {{ number_format($totalMonto, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reporte de Ingresos -->
<div class="modal fade" id="modalReporteIngresos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ingresos por Ruta</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('pagos-transporte.reporte-ingresos') }}" method="GET" target="_blank">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Gestión</label>
                        <input type="number" name="gestion" class="form-control" value="{{ date('Y') }}" min="2020" max="2100" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mes Inicio</label>
                                <select name="mes_inicio" class="form-control" required>
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
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mes Fin</label>
                                <select name="mes_fin" class="form-control" required>
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11" selected>Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-pdf"></i> Generar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });
});

function generarReciboTransporte(codigo, estudiante, curso, tipo, monto, fechaPago, fechaInicio, fechaFin) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        unit: 'pt',
        format: [612, 396],
        orientation: 'landscape'
    });
    
    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    const meses = [];
    const mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    let current = new Date(inicio.getFullYear(), inicio.getMonth(), 1);
    const finMes = new Date(fin.getFullYear(), fin.getMonth(), 1);
    
    while (current < finMes) {
        meses.push(mesesNombres[current.getMonth()] + ' ' + current.getFullYear());
        current.setMonth(current.getMonth() + 1);
    }
    
    const cantidadMeses = meses.length;
    
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
        doc.setLineWidth(2);
        doc.setDrawColor(0, 0, 0);
        doc.rect(10, 10, 592, 376);
        
        // Encabezado izquierdo
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('U.E. PRIVADA INTERANDINO BOLIVIANO', 15, 25);
        doc.setFontSize(6.5);
        doc.setFont(undefined, 'normal');
        doc.text('C/ VICTOR GUTIERREZ Nº 3339', 15, 35);
        doc.text('TELÉFONO: 2840320 - 67304340', 15, 43);
        
        // Cuadro de fecha y monto (derecha)
        doc.setLineWidth(1.5);
        doc.rect(470, 15, 125, 45);
        doc.setFontSize(6.5);
        doc.setFont(undefined, 'normal');
        doc.text('Día/Mes/Año', 532, 25, { align: 'center' });
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text(fechaPago, 532, 35, { align: 'center' });
        doc.setLineWidth(0.5);
        doc.line(470, 40, 595, 40);
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('Bs.', 478, 52);
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(monto.toFixed(2), 535, 52, { align: 'right' });
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('$us.', 545, 52);
        
        // Línea separadora
        doc.setLineWidth(1.5);
        doc.line(15, 65, 597, 65);
        
        // RECIBO con código
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBO - ' + codigo, 306, 85, { align: 'center' });
        
        // Cancelado por
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('Cancelado por:', 15, 110);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([2, 2]);
        doc.line(85, 112, 585, 112);
        doc.setLineDash([]);
        doc.text(estudiante, 90, 110);
        
        // La suma de
        doc.setFont(undefined, 'bold');
        doc.text('La suma de:', 15, 130);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([2, 2]);
        doc.line(70, 132, 585, 132);
        doc.setLineDash([]);
        const parteEntera = Math.floor(monto);
        const parteDecimal = Math.round((monto - parteEntera) * 100);
        const montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
        doc.text(montoLiteral, 75, 130);tos', 'Trescientos', 'Cuatrocientos', 'Quinientos', 'Seiscientos', 'Setecientos', 'Ochocientos', 'Novecientos'];
        
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
        doc.setLineWidth(2);
        doc.setDrawColor(0, 0, 0);
        doc.rect(10, 10, 592, 376);
        
        // Encabezado izquierdo
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('U.E. PRIVADA INTERANDINO BOLIVIANO', 15, 25);
        doc.setFontSize(6.5);
        doc.setFont(undefined, 'normal');
        doc.text('C/ VICTOR GUTIERREZ Nº 3339', 15, 35);
        doc.text('TELÉFONO: 2840320 - 67304340', 15, 43);
        
        // Cuadro de fecha y monto (derecha)
        doc.setLineWidth(1.5);
        doc.rect(470, 15, 125, 45);
        doc.setFontSize(6.5);
        doc.setFont(undefined, 'normal');
        doc.text('Día/Mes/Año', 532, 25, { align: 'center' });
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text(fechaPago, 532, 35, { align: 'center' });
        doc.setLineWidth(0.5);
        doc.line(470, 40, 595, 40);
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('Bs.', 478, 52);
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(monto.toFixed(2), 535, 52, { align: 'right' });
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('$us.', 545, 52);
        
        // Línea separadora
        doc.setLineWidth(1.5);
        doc.line(15, 65, 597, 65);
        
        // RECIBO con código
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBO - ' + codigo, 306, 85, { align: 'center' });
        
        // Cancelado por
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('Cancelado por:', 15, 110);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([2, 2]);
        doc.line(85, 112, 597, 112);
        doc.setLineDash([]);
        doc.text(estudiante, 90, 110);
        
        // La suma de
        doc.setFont(undefined, 'bold');
        doc.text('La suma de:', 15, 130);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([2, 2]);
        doc.line(70, 132, 597, 132);
        doc.setLineDash([]);
        const parteEntera = Math.floor(monto);
        const parteDecimal = Math.round((monto - parteEntera) * 100);
        const montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
        doc.text(montoLiteral, 75, 130);
        
        // Por concepto de
        doc.setFont(undefined, 'bold');
        doc.text('Por concepto de:', 15, 150);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([2, 2]);
        doc.line(90, 152, 597, 152);
        doc.setLineDash([]);
        let concepto = 'Pago Transporte Escolar ' + tipo + ' - Est: ' + estudiante + ' - Curso: ' + curso;
        if (meses.length > 0) {
            concepto += '\nMeses: ' + meses.join(', ');
        }
        const conceptoLines = doc.splitTextToSize(concepto, 500);
        let yConcepto = 150;
        conceptoLines.forEach((line, index) => {
            doc.text(line, 95, yConcepto + (index * 10));
        });
        
        // Líneas punteadas
        let yPos = 185;
        for (let i = 0; i < 5; i++) {
            doc.setLineDash([1, 2]);
            doc.line(15, yPos, 597, yPos);
            yPos += 20;
        }
        doc.setLineDash([]);
        
        // TOTAL
        yPos = 295;
        doc.setLineWidth(1.5);
        doc.line(15, yPos, 597, yPos);
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('TOTAL', 540, yPos + 15);
        doc.text(monto.toFixed(2), 585, yPos + 15, { align: 'right' });
        
        // Sección de firmas
        yPos = 320;
        doc.setLineWidth(1.5);
        doc.line(15, yPos, 597, yPos);
        
        // Línea vertical divisoria
        doc.line(306, yPos, 306, 386);
        
        // Izquierda - Solo línea punteada
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.setLineDash([1, 2]);
        doc.line(50, yPos + 30, 260, yPos + 30);
        doc.setLineDash([]);
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBÍ CONFORME', 150, yPos + 60, { align: 'center' });
        
        // Derecha - Firmas completas
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text('Firma:', 320, yPos + 20);
        doc.setLineDash([1, 2]);
        doc.line(350, yPos + 22, 450, yPos + 22);
        doc.setLineDash([]);
        doc.text('C.I.:', 460, yPos + 20);
        doc.setLineDash([1, 2]);
        doc.line(480, yPos + 22, 580, yPos + 22);
        doc.setLineDash([]);
        
        doc.text('Nom. Y Ap.:', 320, yPos + 38);
        doc.setLineDash([1, 2]);
        doc.line(370, yPos + 40, 580, yPos + 40);
        doc.setLineDash([]);
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text('ENTREGUÉ CONFORME', 450, yPos + 60, { align: 'center' });
    }
    
    dibujarRecibo('ORIGINAL');
    doc.addPage([612, 396]);
    dibujarRecibo('COPIA');
    
    doc.save('recibo_transporte_' + codigo + '.pdf');
}

function exportarPDF() {
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape', 'pt', 'letter');
        
        // Cargar logo
        const logoPath = '{{ asset("img/logo.png") }}';
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        
        img.onload = function() {
            doc.addImage(img, 'PNG', 20, 15, 30, 30);
            generarContenidoReportePDF(doc);
        };
        
        img.onerror = function() {
            generarContenidoReportePDF(doc);
        };
        
        img.src = logoPath;
    } catch (error) {
        console.error('Error al generar PDF:', error);
        alert('Error al generar el PDF: ' + error.message);
    }
}

function generarContenidoReportePDF(doc) {
    // Badge de fecha
    doc.setFillColor(220, 53, 69);
    doc.roundedRect(720, 15, 50, 18, 3, 3, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(8);
    doc.setFont(undefined, 'bold');
    doc.text('Fecha', 745, 22, { align: 'center' });
    doc.text(new Date().toLocaleDateString('es-BO'), 745, 30, { align: 'center' });
    
    // Encabezado institucional
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('Unidad Educativa', 400, 20, { align: 'center' });
    doc.setFontSize(13);
    doc.text('INTERANDINO BOLIVIANO', 400, 35, { align: 'center' });
    doc.setFontSize(8);
    doc.setFont(undefined, 'normal');
    doc.text('Dir. Calle Victor Gutierrez Nro 3339 - Tel: 2840320', 400, 47, { align: 'center' });
    
    // Línea separadora
    doc.setLineWidth(0.5);
    doc.line(20, 52, 780, 52);
    
    // Título
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('REPORTE DE PAGOS DE TRANSPORTE', 400, 65, { align: 'center' });
    
    // Información del reporte
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    let yPos = 78;
    doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 20, yPos);
    
    @if(request('fecha_inicio') && request('fecha_fin'))
    doc.text('Periodo: {{ request("fecha_inicio") }} - {{ request("fecha_fin") }}', 400, yPos);
    @endif
    @if(request('estado'))
    doc.text('Estado: {{ ucfirst(request("estado")) }}', 650, yPos);
    @endif
    yPos += 15;
    
    // Recopilar datos con información completa
    var datos = [];
    $('#tablaPagos tbody tr').each(function() {
        if($(this).find('td').length > 1) {
            var codigo = $(this).find('td').eq(0).text().trim();
            var estudiante = $(this).find('td').eq(1).text().trim();
            var vehiculo = $(this).find('td').eq(2).text().trim();
            var ruta = $(this).find('td').eq(3).text().trim();
            var chofer = $(this).find('td').eq(4).text().trim();
            var tipo = $(this).find('td').eq(5).text().trim();
            var monto = $(this).find('td').eq(6).text().trim();
            var fechaPago = $(this).find('td').eq(7).text().trim();
            var vigencia = $(this).find('td').eq(8).text().trim();
            var estado = $(this).find('td').eq(9).text().trim();
            
            datos.push([codigo, estudiante, vehiculo, ruta, chofer, tipo, monto, fechaPago, vigencia, estado]);
        }
    });
    
    // Tabla con formato horizontal
    doc.autoTable({
        head: [['Cód.', 'Estudiante', 'Vehículo', 'Ruta', 'Chofer', 'Tipo', 'Monto', 'F. Pago', 'Vigencia', 'Estado']],
        body: datos,
        startY: yPos,
        margin: { left: 20, right: 20 },
        headStyles: { 
            fillColor: [44, 62, 80],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 7,
            halign: 'center'
        },
        styles: { 
            fontSize: 6,
            cellPadding: 2,
            overflow: 'linebreak',
            cellWidth: 'wrap'
        },
        columnStyles: {
            0: { cellWidth: 40 },
            1: { cellWidth: 90 },
            2: { cellWidth: 50, halign: 'center' },
            3: { cellWidth: 80 },
            4: { cellWidth: 80 },
            5: { cellWidth: 40, halign: 'center' },
            6: { cellWidth: 50, halign: 'right' },
            7: { cellWidth: 55, halign: 'center' },
            8: { cellWidth: 90, halign: 'center' },
            9: { cellWidth: 45, halign: 'center' }
        },
        alternateRowStyles: {
            fillColor: [245, 245, 245]
        },
        foot: [['', '', '', '', '', 'TOTAL:', 'Bs. {{ number_format($totalMonto, 2) }}', '', '', '']],
        footStyles: { 
            fillColor: [44, 62, 80],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 7
        }
    });
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for(let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(7);
        doc.setTextColor(128, 128, 128);
        doc.text('Fecha y hora de impresión: ' + new Date().toLocaleString('es-BO'), 20, 585);
        doc.text('Página ' + i + ' de ' + pageCount, 780, 585, { align: 'right' });
    }
    
    doc.save('pagos_transporte_' + new Date().getTime() + '.pdf');
}
</script>
@endsection
