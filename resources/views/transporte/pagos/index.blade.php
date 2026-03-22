@extends('layouts.app')

@section('content')
<style>
    .grupo-header { cursor: pointer; background: #f0f7ff !important; }
    .grupo-header:hover { background: #e3f0ff !important; }
    .grupo-header td { border-bottom: none !important; }
    .grupo-detalle { display: none; }
    .grupo-detalle td { padding: 6px 12px !important; background: #fafbfc; font-size: 13px; }
    .grupo-badge { background: #17a2b8; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
    .grupo-chevron { transition: transform 0.2s; display: inline-block; margin-right: 5px; }
    .grupo-header.open .grupo-chevron { transform: rotate(90deg); }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-bus mr-2"></i>Pagos de Transporte</h4>
                    <div>
                        <button class="btn btn-success" data-toggle="modal" data-target="#modalReporteIngresos">
                            <i class="fas fa-file-invoice-dollar"></i> Reporte Ingresos
                        </button>
                        <button class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <a href="{{ route('pagos-transporte.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Pago
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Estudiante</label>
                                <select name="estudiante" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($estudiantes as $est)
                                        <option value="{{ $est->est_codigo }}" {{ request('estudiante') == $est->est_codigo ? 'selected' : '' }}>
                                            {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="vigente" {{ request('estado') == 'vigente' ? 'selected' : '' }}>Vigente</option>
                                    <option value="vencido" {{ request('estado') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                                    <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
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
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Fecha Pago</th>
                                <th>Vigencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $codigosMostrados = []; $totalMonto = 0; @endphp
                            @forelse($pagos as $p)
                                @if(in_array($p->tpago_codigo, $codigosConjuntos))
                                    @if(!in_array($p->tpago_codigo, $codigosMostrados))
                                        @php
                                            $codigosMostrados[] = $p->tpago_codigo;
                                            $itemsGrupo = $pagosRecibo[$p->tpago_codigo] ?? [];
                                            $totalGrupo = array_sum(array_column($itemsGrupo, 'monto'));
                                            $cantItems = count($itemsGrupo);
                                            $porEstudiante = [];
                                            foreach ($itemsGrupo as $it) {
                                                $porEstudiante[$it['estudiante']][] = $it;
                                            }
                                            $todosVigentes = collect($itemsGrupo)->every(fn($i) => $i['estado'] != 'cancelado');
                                            if ($todosVigentes) $totalMonto += $totalGrupo;
                                        @endphp
                                        <tr class="grupo-header" data-codigo="{{ $p->tpago_codigo }}">
                                            <td>
                                                <span class="grupo-chevron"><i class="fas fa-chevron-right"></i></span>
                                                {{ $p->tpago_codigo }}
                                                <span class="grupo-badge">{{ $cantItems }} pagos</span>
                                            </td>
                                            <td>
                                                @foreach($porEstudiante as $nombre => $items)
                                                    <div><strong>{{ $nombre }}</strong> <small class="text-muted">({{ $items[0]['curso'] }})</small></div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @foreach($itemsGrupo as $it)
                                                    <span class="badge badge-info">{{ ucfirst($it['tipo']) }}</span>
                                                @endforeach
                                            </td>
                                            <td><strong>Bs. {{ number_format($totalGrupo, 2) }}</strong></td>
                                            <td>{{ $p->tpago_fecha_pago }}</td>
                                            <td>
                                                @foreach($itemsGrupo as $it)
                                                    <div><small>{{ $it['fecha_inicio'] }} - {{ $it['fecha_fin'] }}</small></div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($todosVigentes)
                                                    <span class="badge badge-success">Vigente</span>
                                                @else
                                                    <span class="badge badge-secondary">Cancelado</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($todosVigentes)
                                                    <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); generarReciboGrupo('{{ $p->tpago_codigo }}')" title="Recibo">
                                                        <i class="fas fa-receipt"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); anularPago({{ $p->tpago_id }})" title="Anular grupo">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @foreach($itemsGrupo as $item)
                                            <tr class="grupo-detalle" data-grupo="{{ $p->tpago_codigo }}">
                                                <td></td>
                                                <td>{{ $item['estudiante'] }} <small class="text-muted">({{ $item['curso'] }})</small></td>
                                                <td><span class="badge badge-info">{{ ucfirst($item['tipo']) }}</span></td>
                                                <td>Bs. {{ number_format($item['monto'], 2) }}</td>
                                                <td></td>
                                                <td><small>{{ $item['fecha_inicio'] }} - {{ $item['fecha_fin'] }}</small></td>
                                                <td>
                                                    @if($item['estado'] == 'cancelado')
                                                        <span class="badge badge-secondary">Cancelado</span>
                                                    @else
                                                        <span class="badge badge-success">Vigente</span>
                                                    @endif
                                                </td>
                                                <td></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @else
                                    @php if ($p->tpago_estado != 'cancelado') $totalMonto += $p->tpago_monto; @endphp
                                    <tr>
                                        <td>{{ $p->tpago_codigo }}</td>
                                        <td><strong>{{ $p->estudiante ? $p->estudiante->est_nombres . ' ' . $p->estudiante->est_apellidos : 'N/A' }}</strong>
                                            <small class="text-muted">({{ $p->estudiante->curso->cur_nombre ?? '' }})</small>
                                        </td>
                                        <td><span class="badge badge-info">{{ ucfirst($p->tpago_tipo) }}</span></td>
                                        <td>Bs. {{ number_format($p->tpago_monto, 2) }}</td>
                                        <td>{{ $p->tpago_fecha_pago }}</td>
                                        <td><small>{{ $p->tpago_fecha_inicio }} - {{ $p->tpago_fecha_fin }}</small></td>
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
                                            @if($p->tpago_estado != 'cancelado')
                                                <button class="btn btn-sm btn-info" onclick="generarReciboGrupo('{{ $p->tpago_codigo }}')" title="Recibo">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                                <a href="{{ route('pagos-transporte.edit', $p->tpago_id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" onclick="anularPago({{ $p->tpago_id }})" title="Anular">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="8" class="text-center">No hay pagos registrados</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                                <td colspan="5"><strong>Bs. {{ number_format($totalMonto, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $pagos->appends(request()->all())->links() }}
                    </div>
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
                            <label>Mes Inicio</label>
                            <select name="mes_inicio" class="form-control">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == 2 ? 'selected' : '' }}>{{ ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$i] }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Mes Fin</label>
                            <select name="mes_fin" class="form-control">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == 11 ? 'selected' : '' }}>{{ ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$i] }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-pdf"></i> Generar</button>
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
var pagosRecibo = @json($pagosRecibo);

$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%', allowClear: true });
    $('.grupo-header').on('click', function() {
        var codigo = $(this).data('codigo');
        $(this).toggleClass('open');
        $('tr.grupo-detalle[data-grupo="' + codigo + '"]').slideToggle(200);
    });
});

function numeroATexto(num) {
    var unidades = ['', 'Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis', 'Siete', 'Ocho', 'Nueve'];
    var decenas = ['', '', 'Veinte', 'Treinta', 'Cuarenta', 'Cincuenta', 'Sesenta', 'Setenta', 'Ochenta', 'Noventa'];
    var especiales = ['Diez', 'Once', 'Doce', 'Trece', 'Catorce', 'Quince', 'Dieciséis', 'Diecisiete', 'Dieciocho', 'Diecinueve'];
    var centenas = ['', 'Ciento', 'Doscientos', 'Trescientos', 'Cuatrocientos', 'Quinientos', 'Seiscientos', 'Setecientos', 'Ochocientos', 'Novecientos'];
    num = Math.floor(num);
    if (num === 0) return 'Cero';
    if (num === 100) return 'Cien';
    var texto = '';
    if (num >= 1000) { var mil = Math.floor(num / 1000); texto += (mil === 1 ? 'Mil' : numeroATexto(mil) + ' Mil') + ' '; num %= 1000; }
    if (num >= 100) { texto += centenas[Math.floor(num / 100)] + ' '; num %= 100; }
    if (num >= 20) { texto += decenas[Math.floor(num / 10)]; if (num % 10 > 0) texto += ' y ' + unidades[num % 10]; }
    else if (num >= 10) { texto += especiales[num - 10]; }
    else if (num > 0) { texto += unidades[num]; }
    return texto.trim();
}

function generarReciboGrupo(codigo) {
    var items = pagosRecibo[codigo];
    if (!items || items.length === 0) return;
    items = items.filter(function(it) { return it.estado !== 'cancelado'; });
    if (items.length === 0) return;

    var montoTotal = 0;
    items.forEach(function(it) { montoTotal += parseFloat(it.monto); });

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ unit: 'pt', format: [612, 396], orientation: 'landscape' });

    var mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    function getMeses(fi, ff) {
        var inicio = new Date(fi), fin = new Date(ff), meses = [];
        var current = new Date(inicio.getFullYear(), inicio.getMonth(), 1);
        var finMes = new Date(fin.getFullYear(), fin.getMonth(), 1);
        while (current < finMes) {
            meses.push(mesesNombres[current.getMonth()] + ' ' + current.getFullYear());
            current.setMonth(current.getMonth() + 1);
        }
        return meses;
    }

    function dibujarRecibo(tipoRecibo) {
        doc.setLineWidth(1.5);
        doc.setDrawColor(0, 0, 0);
        doc.rect(10, 10, 592, 376);

        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(100, 100, 100);
        doc.text('U.E PRIVADA INTERANDINO BOLIVIANO', 15, 28);
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text('C/ VICTOR GUTIERREZ N° 3339', 15, 39);
        doc.text('TELEFONO: 2840320 - 67304340', 15, 49);

        var soloNumero = codigo.replace(/\D/g, '').padStart(5, '0');
        doc.setFontSize(18);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text('Nº ' + soloNumero, 597, 30, { align: 'right' });

        doc.setFontSize(36);
        doc.text('RECIBO', 306, 75, { align: 'center' });

        doc.setFontSize(11);
        doc.setFont(undefined, 'normal');
        var fechaPago = items[0].fecha_inicio ? items[0].fecha_inicio : '';
        doc.text('Fecha: ' + new Date().toLocaleDateString('es-BO'), 15, 92);

        doc.setLineWidth(1);
        doc.line(15, 100, 597, 100);

        // Encabezado tabla
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.setFillColor(240, 240, 240);
        doc.rect(15, 103, 582, 18, 'F');
        doc.text('ESTUDIANTE', 20, 115);
        doc.text('CONCEPTO', 280, 115);
        doc.text('MONTO', 570, 115, { align: 'right' });

        var yPos = 135;

        if (items.length === 1) {
            var item = items[0];
            var meses = getMeses(item.fecha_inicio, item.fecha_fin);
            var cantMeses = meses.length;
            var montoPorMes = parseFloat(item.monto) / Math.max(cantMeses, 1);

            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.text(item.estudiante, 20, yPos);
            doc.text('TRANSPORTE ' + item.tipo.toUpperCase() + ' - ' + cantMeses + ' CUOTAS', 280, yPos);
            doc.setFont(undefined, 'normal');
            yPos += 14;

            if (cantMeses > 5) {
                var mitad = Math.ceil(cantMeses / 2);
                for (var i = 0; i < mitad; i++) {
                    doc.text((i + 1) + '. ' + (meses[i] || ''), 20, yPos);
                    doc.text(montoPorMes.toFixed(2), 230, yPos, { align: 'right' });
                    var j = i + mitad;
                    if (j < cantMeses) {
                        doc.text((j + 1) + '. ' + (meses[j] || ''), 310, yPos);
                        doc.text(montoPorMes.toFixed(2), 570, yPos, { align: 'right' });
                    }
                    yPos += 12;
                }
            } else {
                for (var i = 0; i < cantMeses; i++) {
                    doc.text('  ' + (i + 1) + '. ' + (meses[i] || ''), 280, yPos);
                    doc.text(montoPorMes.toFixed(2), 570, yPos, { align: 'right' });
                    yPos += 13;
                }
            }
        } else {
            // Agrupar por estudiante
            var porEstudiante = {};
            items.forEach(function(it) {
                if (!porEstudiante[it.estudiante]) porEstudiante[it.estudiante] = { curso: it.curso, pagos: [], subtotal: 0 };
                porEstudiante[it.estudiante].pagos.push(it);
                porEstudiante[it.estudiante].subtotal += parseFloat(it.monto);
            });

            doc.setFontSize(10);
            for (var nombre in porEstudiante) {
                var est = porEstudiante[nombre];
                doc.setFont(undefined, 'bold');
                doc.text(nombre + '  (' + est.curso + ')', 20, yPos);
                doc.text('Bs. ' + est.subtotal.toFixed(2), 570, yPos, { align: 'right' });
                doc.setLineDash([0.5, 1]);
                doc.line(15, yPos + 3, 597, yPos + 3);
                doc.setLineDash([]);
                yPos += 14;

                doc.setFont(undefined, 'normal');
                doc.setFontSize(9);
                est.pagos.forEach(function(pago) {
                    var meses = getMeses(pago.fecha_inicio, pago.fecha_fin);
                    doc.text('   Transporte ' + pago.tipo + ': ' + meses.join(', '), 30, yPos);
                    yPos += 12;
                });
                doc.setFontSize(10);
                yPos += 3;
            }
        }

        // TOTAL
        var lineaTotal = Math.max(yPos + 10, 290);
        doc.setLineWidth(1);
        doc.line(15, lineaTotal, 597, lineaTotal);
        doc.setFontSize(13);
        doc.setFont(undefined, 'bold');
        doc.text('TOTAL Bs.', 470, lineaTotal + 18);
        doc.text(montoTotal.toFixed(2), 580, lineaTotal + 18, { align: 'right' });
        doc.setLineWidth(2);
        doc.line(465, lineaTotal + 23, 597, lineaTotal + 23);

        // SON
        var parteEntera = Math.floor(montoTotal);
        var parteDecimal = Math.round((montoTotal - parteEntera) * 100);
        var montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('SON: ' + montoLiteral.toUpperCase(), 20, lineaTotal + 18);

        doc.setFont(undefined, 'normal');
        doc.setFontSize(8);
        doc.text('Usuario: {{ auth()->user()->us_nombres ?? "SISTEMA" }}', 20, 370);
        var ahora = new Date();
        doc.text('Hora: ' + ahora.getHours().toString().padStart(2, '0') + ':' + ahora.getMinutes().toString().padStart(2, '0'), 200, 370);
        doc.text(tipoRecibo, 597, 370, { align: 'right' });
    }

    dibujarRecibo('ORIGINAL');
    doc.addPage([612, 396]);
    dibujarRecibo('COPIA');
    doc.save('recibo_transporte_' + codigo + '.pdf');
}

function anularPago(id) {
    if (confirm('¿Está seguro de anular este pago? Se anularán todos los pagos del mismo recibo.')) {
        $.ajax({
            url: '/pagos-transporte/' + id + '/anular',
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}' },
            success: function() { alert('Pago(s) anulado(s)'); location.reload(); },
            error: function() { alert('Error al anular'); }
        });
    }
}

function exportarPDF() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF('landscape', 'pt', 'letter');

    doc.setTextColor(0, 0, 0);
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text('Unidad Educativa INTERANDINO BOLIVIANO', 400, 25, { align: 'center' });
    doc.setFontSize(14);
    doc.text('REPORTE DE PAGOS DE TRANSPORTE', 400, 45, { align: 'center' });
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 20, 60);
    doc.text('Fecha: ' + new Date().toLocaleDateString('es-BO'), 700, 60, { align: 'right' });

    var datos = [];
    $('#tablaPagos tbody tr:not(.grupo-detalle)').each(function() {
        if ($(this).find('td').length > 1) {
            var fila = [];
            $(this).find('td').each(function(i) { if (i < 7) fila.push($(this).text().trim()); });
            if (fila.length > 0) datos.push(fila);
        }
    });

    doc.autoTable({
        head: [['Código', 'Estudiante', 'Tipo', 'Monto', 'Fecha', 'Vigencia', 'Estado']],
        body: datos,
        startY: 75,
        headStyles: { fillColor: [44, 62, 80], fontSize: 8 },
        styles: { fontSize: 7, cellPadding: 3 },
        alternateRowStyles: { fillColor: [245, 245, 245] }
    });

    doc.save('pagos_transporte_' + new Date().getTime() + '.pdf');
}
</script>
@endsection
