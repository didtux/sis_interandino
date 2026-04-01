
@extends('layouts.app')

@section('content')
<style>
    .grupo-header { cursor: pointer; background: #f0f7ff !important; }
    .grupo-header:hover { background: #e3f0ff !important; }
    .grupo-header td { border-bottom: none !important; }
    .grupo-detalle { display: none; }
    .grupo-detalle td { padding: 6px 12px !important; background: #fafbfc; font-size: 13px; }
    .grupo-badge { background: #007bff; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
    .grupo-chevron { transition: transform 0.2s; display: inline-block; margin-right: 5px; }
    .grupo-header.open .grupo-chevron { transform: rotate(90deg); }
    .detalle-tabla { width: 100%; margin: 0; }
    .detalle-tabla td { padding: 4px 8px !important; border: none !important; background: transparent !important; }
    .detalle-tabla tr:not(:last-child) td { border-bottom: 1px solid #eee !important; }
</style>

<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-money-bill-wave mr-2"></i>Pagos y Mensualidades</h4>
                    <div>
                        <a href="{{ route('pagos.mora') }}" class="btn btn-warning mr-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Estudiantes en Mora
                        </a>
                        <a href="{{ route('pagos.create') }}" class="btn btn-primary-modern">
                            <i class="fas fa-plus mr-1"></i>Registrar Pago
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success-modern">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                            <div class="col-md-2">
                                <label>Curso</label>
                                <select name="cur_codigo" id="curso-select" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->cur_codigo }}" {{ request('cur_codigo') == $curso->cur_codigo ? 'selected' : '' }}>{{ $curso->cur_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estudiante</label>
                                <select name="est_codigo" id="estudiante-select" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($estudiantes as $e)
                                        <option value="{{ $e->est_codigo }}" {{ request('est_codigo') == $e->est_codigo ? 'selected' : '' }}>{{ $e->est_nombres }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="activos" {{ request('estado') == 'activos' ? 'selected' : '' }}>Activos</option>
                                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Anulados</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                                <a href="{{ route('pagos.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-file-pdf"></i> Reportes PDF
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('pagos.reporte-pdf', request()->all()) }}" target="_blank"><i class="fas fa-list"></i> Listado de Pagos</a>
                                        <a class="dropdown-item" href="{{ route('pagos.resumen-anual-pdf', request()->all()) }}" target="_blank"><i class="fas fa-calendar-alt"></i> Resumen Anual</a>
                                    </div>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-file-excel"></i> Reportes Excel
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('pagos.reporte-excel', request()->all()) }}"><i class="fas fa-list"></i> Listado de Pagos</a>
                                        <a class="dropdown-item" href="{{ route('pagos.resumen-anual-excel', request()->all()) }}"><i class="fas fa-calendar-alt"></i> Resumen Anual</a>
                                    </div>
                                </div>
                                <a href="{{ route('pagos.resumen-anual') }}" class="btn btn-info"><i class="fas fa-chart-bar"></i> Resumen Anual</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Estudiante</th>
                                    <th>Concepto</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $codigosMostrados = []; @endphp
                                @forelse($pagos as $pago)
                                    @if(in_array($pago->pagos_codigo, $codigosConjuntos))
                                        {{-- PAGO CONJUNTO: mostrar bloque colapsable solo la primera vez --}}
                                        @if(!in_array($pago->pagos_codigo, $codigosMostrados))
                                            @php
                                                $codigosMostrados[] = $pago->pagos_codigo;
                                                $itemsGrupo = $pagosRecibo[$pago->pagos_codigo] ?? [];
                                                $totalGrupo = array_sum(array_column($itemsGrupo, 'monto'));
                                                $cantItems = count($itemsGrupo);
                                                // Agrupar por estudiante para resumen
                                                $porEstudiante = [];
                                                foreach ($itemsGrupo as $it) {
                                                    $porEstudiante[$it['estudiante']][] = $it;
                                                }
                                                $resumenEstudiantes = [];
                                                foreach ($porEstudiante as $nombre => $items) {
                                                    $conceptos = array_column($items, 'concepto');
                                                    $resumenEstudiantes[] = $nombre . ': ' . implode(', ', $conceptos);
                                                }
                                                $todosActivos = collect($itemsGrupo)->every(fn($i) => $i['estado'] == 1);
                                                $algunoAnulado = collect($itemsGrupo)->contains(fn($i) => $i['estado'] == 0);
                                            @endphp
                                            <tr class="grupo-header" data-codigo="{{ $pago->pagos_codigo }}">
                                                <td>
                                                    <span class="grupo-chevron"><i class="fas fa-chevron-right"></i></span>
                                                    {{ $pago->pagos_codigo }}
                                                    <span class="grupo-badge">{{ $cantItems }} pagos</span>
                                                </td>
                                                <td>{{ $pago->pagos_fecha->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    @foreach($porEstudiante as $nombre => $items)
                                                        <div><strong>{{ $nombre }}</strong> <small class="text-muted">({{ $items[0]['curso'] }})</small></div>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @foreach($porEstudiante as $nombre => $items)
                                                        <div>{{ implode(', ', array_column($items, 'concepto')) }}</div>
                                                    @endforeach
                                                </td>
                                                <td><strong>Bs. {{ number_format($totalGrupo, 2) }}</strong></td>
                                                <td>
                                                    @if($todosActivos)
                                                        <span class="badge badge-success">Activo</span>
                                                    @elseif($algunoAnulado)
                                                        <span class="badge badge-warning">Parcial</span>
                                                    @else
                                                        <span class="badge badge-danger">Anulado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($todosActivos)
                                                        <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); generarRecibo('{{ $pago->pagos_codigo }}', '{{ addslashes($pago->padreFamilia->pfam_nombres ?? '') }}', '{{ $pago->pagos_fecha->format('d/m/Y') }}')" title="Imprimir recibo">
                                                            <i class="fas fa-receipt"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); anularPago({{ $pago->pagos_id }})" title="Anular todo el recibo">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            {{-- Filas de detalle ocultas --}}
                                            @foreach($itemsGrupo as $item)
                                                <tr class="grupo-detalle" data-grupo="{{ $pago->pagos_codigo }}">
                                                    <td></td>
                                                    <td></td>
                                                    <td>{{ $item['estudiante'] }} <small class="text-muted">({{ $item['curso'] }})</small></td>
                                                    <td>{{ $item['concepto'] }}</td>
                                                    <td>Bs. {{ number_format($item['monto'], 2) }}</td>
                                                    <td>
                                                        @if($item['estado'] == 0)
                                                            <span class="badge badge-danger">Anulado</span>
                                                        @else
                                                            <span class="badge badge-success">Activo</span>
                                                        @endif
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @else
                                        {{-- PAGO INDIVIDUAL: fila normal --}}
                                        <tr>
                                            <td>{{ $pago->pagos_codigo }}</td>
                                            <td>{{ $pago->pagos_fecha->format('d/m/Y H:i') }}</td>
                                            <td>{{ $pago->estudiante->est_nombres ?? 'N/A' }} <small class="text-muted">({{ $pago->estudiante->curso->cur_nombre ?? '' }})</small></td>
                                            <td>{{ $pago->concepto }}</td>
                                            <td><strong>Bs. {{ number_format($pago->pagos_precio - $pago->pagos_descuento, 2) }}</strong></td>
                                            <td>
                                                @if($pago->pagos_estado == 0)
                                                    <span class="badge badge-danger">Anulado</span>
                                                @else
                                                    <span class="badge badge-success">Activo</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($pago->pagos_estado != 0)
                                                    <button class="btn btn-sm btn-info" onclick="generarRecibo('{{ $pago->pagos_codigo }}', '{{ addslashes($pago->padreFamilia->pfam_nombres ?? '') }}', '{{ $pago->pagos_fecha->format('d/m/Y') }}')" title="Imprimir recibo">
                                                        <i class="fas fa-receipt"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="anularPago({{ $pago->pagos_id }})">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <h5>No hay pagos registrados</h5>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $pagos->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
var pagosRecibo = @json($pagosRecibo);

$(document).ready(function() {
    $('.select2').select2({ placeholder: 'Seleccione una opción', allowClear: true, width: '100%' });

    // Toggle grupo
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
    if (num === 0) return 'Cero';
    if (num === 100) return 'Cien';
    var texto = '';
    if (num >= 1000) {
        var miles = Math.floor(num / 1000);
        texto += (miles === 1 ? 'Mil' : numeroATexto(miles) + ' Mil') + ' ';
        num %= 1000;
    }
    if (num >= 100) { texto += centenas[Math.floor(num / 100)] + ' '; num %= 100; }
    if (num >= 20) {
        texto += decenas[Math.floor(num / 10)];
        if (num % 10 > 0) texto += ' y ' + unidades[num % 10];
    } else if (num >= 10) { texto += especiales[num - 10]; }
    else if (num > 0) { texto += unidades[num]; }
    return texto.trim();
}

function generarRecibo(codigo, padre, fecha) {
    var items = pagosRecibo[codigo];
    if (!items || items.length === 0) return;

    // Solo items activos
    items = items.filter(function(it) { return it.estado == 1; });
    if (items.length === 0) return;

    var montoTotal = 0;
    items.forEach(function(it) { montoTotal += it.monto; });

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ unit: 'pt', format: [612, 396], orientation: 'landscape' });

    dibujarRecibo(doc, codigo, padre, fecha, montoTotal, items);
    doc.addPage([612, 396]);
    dibujarRecibo(doc, codigo, padre, fecha, montoTotal, items);

    doc.save('recibo_' + codigo + '.pdf');
}

function dibujarRecibo(doc, codigo, padre, fecha, monto, items) {
    doc.setLineWidth(1);
    doc.setDrawColor(0, 0, 0);
    doc.setLineDash([0.5, 1]);
    doc.rect(10, 10, 592, 376);
    doc.setLineDash([]);

    // Encabezado
    doc.setFontSize(8);
    doc.setFont(undefined, 'bold');
    doc.text('U.E. PRIVADA INTERANDINO BOLIVIANO', 15, 25);
    doc.setFontSize(6.5);
    doc.setFont(undefined, 'normal');
    doc.text('C/ VICTOR GUTIERREZ Nº 3339', 15, 35);
    doc.text('TELÉFONO: 2840320 - 67304340', 15, 43);

    // Cuadros derecha
    doc.setLineWidth(1);
    doc.roundedRect(470, 10, 128, 21, 2, 2);
    doc.setFontSize(8);
    doc.setFont(undefined, 'bold');
    doc.text('Día/Mes/Año', 534, 18, { align: 'center' });
    doc.setFontSize(12);
    doc.text(fecha, 534, 28, { align: 'center' });

    doc.roundedRect(470, 34, 128, 16, 2, 2);
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    doc.text('Bs.', 478, 44);
    doc.setFontSize(10);
    doc.text(monto.toFixed(2), 588, 44, { align: 'right' });

    doc.roundedRect(470, 53, 128, 16, 2, 2);
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    doc.text('$us.', 478, 63);
    doc.line(505, 61, 588, 61);

    // RECIBO título
    var soloNumero = codigo.replace(/\D/g, '');
    doc.setFontSize(28);
    doc.setFont(undefined, 'bold');
    doc.text('RECIBO-' + soloNumero.padStart(5, '0'), 306, 85, { align: 'center' });

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
    var parteEntera = Math.floor(monto);
    var parteDecimal = Math.round((monto - parteEntera) * 100);
    var montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text('La suma de:', 15, 130);
    doc.setFont(undefined, 'normal');
    doc.setLineDash([0.5, 1]);
    doc.line(85, 132, 585, 132);
    doc.setLineDash([]);
    doc.text(montoLiteral, 90, 130);

    // Por concepto de
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text('Por concepto de:', 15, 150);
    doc.setLineDash([0.5, 1]);
    doc.line(110, 152, 585, 152);
    doc.setLineDash([]);

    var yPos = 150;

    if (items.length === 1) {
        var item = items[0];
        doc.setFont(undefined, 'normal');
        // Si el concepto es muy largo, truncar
        var textoConcepto = item.concepto;
        if (textoConcepto.length > 70) textoConcepto = textoConcepto.substring(0, 70) + '...';
        doc.text(textoConcepto, 115, yPos);
        yPos += 20;
        doc.setLineDash([0.5, 1]);
        doc.line(15, yPos + 2, 585, yPos + 2);
        doc.setLineDash([]);
        doc.text('Est: ' + item.estudiante + '  -  Curso: ' + item.curso, 15, yPos);
        yPos += 22;
        for (var i = 0; i < 3; i++) {
            doc.setLineDash([0.5, 1]);
            doc.line(15, yPos, 597, yPos);
            doc.setLineDash([]);
            yPos += 22;
        }
    } else {
        // Conjunto: agrupar por estudiante
        var porEstudiante = {};
        items.forEach(function(it) {
            var key = it.estudiante;
            if (!porEstudiante[key]) porEstudiante[key] = { curso: it.curso, conceptos: [], subtotal: 0 };
            porEstudiante[key].conceptos.push(it.concepto);
            porEstudiante[key].subtotal += it.monto;
        });

        doc.setFont(undefined, 'normal');
        doc.text('Pago conjunto de mensualidades', 115, yPos);
        yPos += 18;

        doc.setFontSize(10);
        for (var nombre in porEstudiante) {
            var est = porEstudiante[nombre];
            // Nombre del estudiante + curso (una sola vez)
            doc.setFont(undefined, 'bold');
            doc.text(nombre + '  (' + est.curso + ')', 20, yPos);
            doc.setFont(undefined, 'normal');
            doc.text('Bs. ' + est.subtotal.toFixed(2), 560, yPos, { align: 'right' });
            doc.setLineDash([0.5, 1]);
            doc.line(15, yPos + 3, 597, yPos + 3);
            doc.setLineDash([]);
            yPos += 14;

            // Conceptos en 2 columnas si son muchos
            doc.setFontSize(9);
            var conceptos = est.conceptos;
            if (conceptos.length <= 3) {
                doc.text('   ' + conceptos.join(', '), 30, yPos);
                yPos += 15;
            } else {
                var mitad = Math.ceil(conceptos.length / 2);
                for (var ci = 0; ci < mitad; ci++) {
                    doc.text('  ' + conceptos[ci], 30, yPos);
                    var ci2 = ci + mitad;
                    if (ci2 < conceptos.length) {
                        doc.text('  ' + conceptos[ci2], 310, yPos);
                    }
                    yPos += 12;
                }
            }
            doc.setFontSize(10);
        }
    }

    // TOTAL
    var totalY = Math.max(yPos + 5, 295);
    doc.setLineWidth(1);
    doc.setLineDash([0.5, 1]);
    doc.line(15, totalY, 597, totalY);
    doc.setLineDash([]);
    doc.setFontSize(13);
    doc.setFont(undefined, 'bold');
    doc.text('TOTAL', 480, totalY + 15);
    doc.text('Bs. ' + monto.toFixed(2), 585, totalY + 15, { align: 'right' });

    // Firmas
    var firmaY = Math.max(totalY + 25, 320);
    doc.setLineDash([0.5, 1]);
    doc.line(80, firmaY + 35, 220, firmaY + 35);
    doc.setLineDash([]);
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    doc.text('RECIBÍ CONFORME', 150, firmaY + 50, { align: 'center' });

    doc.setFontSize(8);
    doc.setFont(undefined, 'normal');
    doc.text('Firma:', 320, firmaY + 20);
    doc.setLineDash([0.5, 1]);
    doc.line(350, firmaY + 22, 450, firmaY + 22);
    doc.setLineDash([]);
    doc.text('C.I.:', 460, firmaY + 20);
    doc.setLineDash([0.5, 1]);
    doc.line(480, firmaY + 22, 580, firmaY + 22);
    doc.setLineDash([]);
    doc.text('Nom. Y Ap.:', 320, firmaY + 38);
    doc.setLineDash([0.5, 1]);
    doc.line(370, firmaY + 40, 580, firmaY + 40);
    doc.setLineDash([]);
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    doc.text('ENTREGUÉ CONFORME', 450, firmaY + 50, { align: 'center' });
}

function anularPago(id) {
    if (confirm('¿Está seguro de anular este pago?')) {
        $.ajax({
            url: '{{ url("/pagos") }}/' + id + '/anular',
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) { alert('Pago anulado'); location.reload(); },
            error: function() { alert('Error al anular'); }
        });
    }
}
</script>
@endsection
