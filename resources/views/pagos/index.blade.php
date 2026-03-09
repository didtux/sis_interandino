
@extends('layouts.app')

@section('content')
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
                                    <option value="">Todos los estados</option>
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
                                        <a class="dropdown-item" href="{{ route('pagos.reporte-pdf', request()->all()) }}" target="_blank">
                                            <i class="fas fa-list"></i> Listado de Pagos
                                        </a>
                                        <a class="dropdown-item" href="{{ route('pagos.resumen-anual-pdf', request()->all()) }}" target="_blank">
                                            <i class="fas fa-calendar-alt"></i> Resumen Anual
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-file-excel"></i> Reportes Excel
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('pagos.reporte-excel', request()->all()) }}">
                                            <i class="fas fa-list"></i> Listado de Pagos
                                        </a>
                                        <a class="dropdown-item" href="{{ route('pagos.resumen-anual-excel', request()->all()) }}">
                                            <i class="fas fa-calendar-alt"></i> Resumen Anual
                                        </a>
                                    </div>
                                </div>
                                
                                <a href="{{ route('pagos.resumen-anual') }}" class="btn btn-info">
                                    <i class="fas fa-chart-bar"></i> Resumen Anual
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive-modern">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>N° Recibo</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Padre</th>
                                    <th>Concepto</th>
                                    <th>Total</th>
                                    <th>Tipo Factura</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pagos as $pago)
                                    <tr>
                                        <td data-label="Fecha">
                                            <i class="fas fa-calendar mr-1"></i>{{ $pago->pagos_fecha->format('d/m/Y') }}
                                        </td>
                                        <td data-label="N° Recibo">
                                            <strong>{{ $pago->pagos_codigo ?? 'N/A' }}</strong>
                                        </td>
                                        <td data-label="Estudiante">{{ $pago->estudiante->est_nombres ?? 'N/A' }} {{ $pago->estudiante->est_apellidos ?? '' }}</td>
                                        <td data-label="Curso">{{ $pago->estudiante->curso->cur_nombre ?? 'N/A' }}</td>
                                        <td data-label="Padre">{{ $pago->padreFamilia->pfam_nombres ?? 'N/A' }}</td>
                                        <td data-label="Concepto">
                                            <span class="modern-badge badge-primary-modern">{{ $pago->concepto }}</span>
                                        </td>
                                        <td data-label="Total">
                                            <strong class="text-success">Bs. {{ number_format($pago->pagos_precio - $pago->pagos_descuento, 2) }}</strong>
                                        </td>
                                        <td data-label="Tipo Factura">
                                            @if($pago->pagos_sin_factura)
                                                <span class="badge badge-warning">Sin Factura</span>
                                            @else
                                                <span class="badge badge-success">Con Factura</span>
                                            @endif
                                        </td>
                                        <td data-label="Estado">
                                            @if($pago->pagos_estado == 0)
                                                <span class="badge badge-danger">Anulado</span>
                                            @else
                                                <span class="badge badge-success">Activo</span>
                                            @endif
                                        </td>
                                        <td data-label="Acciones">
                                            @if($pago->pagos_estado != 0)
                                                <button class="btn btn-sm btn-info" onclick="generarReciboPago('{{ $pago->pagos_codigo ?? 'N/A' }}', '{{ addslashes($pago->estudiante->est_nombres ?? '') }} {{ addslashes($pago->estudiante->est_apellidos ?? '') }}', '{{ addslashes($pago->padreFamilia->pfam_nombres ?? '') }}', '{{ addslashes($pago->estudiante->curso->cur_nombre ?? '') }}', '{{ addslashes($pago->concepto) }}', {{ $pago->pagos_precio - $pago->pagos_descuento }}, '{{ $pago->pagos_fecha->format('d/m/Y') }}')">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="anularPago({{ $pago->pagos_id }})">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <h5>No hay pagos registrados</h5>
                                                <p>Comienza registrando el primer pago</p>
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
$(document).ready(function() {
    $('#estudiante-select, #curso-select').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        width: '100%'
    });
});

function generarReciboPago(codigo, estudiante, padre, curso, concepto, monto, fecha) {
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
        doc.rect(10, 10, 592, 360);
        
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('U.E. PRIVADA INTERANDINO BOLIVIANO', 15, 25);
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('C/ VICTOR GUTIERREZ Nº 3339', 15, 35);
        doc.text('TELÉFONO: 2840320 - 67304340', 15, 43);
        
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBO', 306, 32, { align: 'center' });
        
        doc.setLineWidth(0.5);
        doc.rect(485, 17, 110, 35);
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('Día/Mes/Año:', 490, 27);
        doc.text(fecha, 490, 35);
        doc.line(485, 39, 595, 39);
        doc.text('Bs.', 490, 47);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text(monto.toFixed(2), 510, 47);
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        doc.text('$us.', 560, 47);
        doc.text('Bolivianos/Dólares', 525, 55, { align: 'center' });
        
        doc.setLineWidth(0.5);
        doc.line(15, 57, 597, 57);
        
        doc.setFontSize(8);
        doc.setFont(undefined, 'bold');
        doc.text('Cancelado por:', 15, 72);
        doc.setFont(undefined, 'normal');
        doc.text(padre, 80, 72);
        
        const parteEntera = Math.floor(monto);
        const parteDecimal = Math.round((monto - parteEntera) * 100);
        const montoLiteral = numeroATexto(parteEntera) + ' ' + String(parteDecimal).padStart(2, '0') + '/100';
        
        doc.setFont(undefined, 'bold');
        doc.text('La suma de:', 15, 85);
        doc.setFont(undefined, 'normal');
        doc.text(montoLiteral, 65, 85);
        
        doc.setFont(undefined, 'bold');
        doc.text('Por concepto de:', 15, 98);
        doc.setFont(undefined, 'normal');
        doc.text(concepto, 90, 98);
        
        let yPos = 111;
        doc.setFontSize(7);
        
        doc.setFont(undefined, 'bold');
        doc.text('Cód.', 20, yPos);
        doc.text('Descripción', 145, yPos);
        doc.text('Monto', 555, yPos);
        
        doc.line(15, yPos + 3, 597, yPos + 3);
        yPos += 15;
        
        doc.setFont(undefined, 'normal');
        doc.text(codigo, 20, yPos);
        
        let descripcion = concepto + ' - Estudiante: ' + estudiante + ' - Curso: ' + curso;
        const maxWidth = 390;
        const lines = doc.splitTextToSize(descripcion, maxWidth);
        doc.text(lines, 85, yPos);
        
        doc.text(monto.toFixed(2), 555, yPos);
        
        yPos += (lines.length * 10) + 10;
        
        for (let i = 0; i < 3; i++) {
            doc.setLineDash([1, 2]);
            doc.line(15, yPos, 597, yPos);
            yPos += 15;
        }
        doc.setLineDash([]);
        
        doc.setLineWidth(1);
        doc.line(15, yPos, 597, yPos);
        yPos += 15;
        
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text('SON: ' + montoLiteral + ' Bolivianos', 20, yPos);
        yPos += 12;
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text('TOTAL', 485, yPos);
        doc.text(monto.toFixed(2), 555, yPos);
        
        yPos += 5;
        doc.line(15, yPos, 597, yPos);
        doc.line(15, yPos + 2, 597, yPos + 2);
        
        yPos = 325;
        doc.setFontSize(7);
        doc.setFont(undefined, 'normal');
        
        doc.text('Firma:........................C.I.........................', 35, yPos);
        doc.text('Nom. Y Ap.:........................................', 35, yPos + 12);
        doc.setFont(undefined, 'bold');
        doc.text('RECIBÍ CONFORME', 95, yPos + 30, { align: 'center' });
        
        doc.setFont(undefined, 'normal');
        doc.text('Firma:........................C.I.........................', 345, yPos);
        doc.text('Nom. Y Ap.:........................................', 345, yPos + 12);
        doc.setFont(undefined, 'bold');
        doc.text('ENTREGUÉ CONFORME', 425, yPos + 30, { align: 'center' });
    }
    
    dibujarRecibo('ORIGINAL');
    doc.addPage([612, 396]);
    dibujarRecibo('COPIA');
    
    doc.save('recibo_' + codigo + '.pdf');
}

function anularPago(id) {
    if (confirm('¿Está seguro de anular este pago?')) {
        $.ajax({
            url: '/pagos/' + id + '/anular',
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                alert('Pago anulado');
                location.reload();
            },
            error: function() {
                alert('Error al anular');
            }
        });
    }
}
</script>
@endsection
