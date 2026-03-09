@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-file-pdf mr-2"></i>Reportes de Asistencia</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Fecha Inicio</label>
                            <input type="date" id="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Fecha Fin</label>
                            <input type="date" id="fecha_fin" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Curso</label>
                            <select id="curso_id" class="form-control select2">
                                <option value="">Todos</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->cur_codigo }}">{{ $curso->cur_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Estudiante</label>
                            <select id="estudiante_id" class="form-control select2">
                                <option value="">Todos</option>
                                @foreach($estudiantes as $est)
                                    <option value="{{ $est->est_codigo }}">{{ $est->est_nombres }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>Tipo de Reporte</label>
                            <select id="tipo_reporte" class="form-control select2" required>
                                <option value="general">General</option>
                                <option value="atrasos">Solo Atrasos</option>
                                <option value="permisos">Solo Permisos</option>
                                <option value="completo">Completo</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>&nbsp;</label>
                            <div>
                                <button class="btn btn-success btn-lg" onclick="generarReporte('excel')">
                                    <i class="fas fa-file-excel"></i> Generar Excel
                                </button>
                                <button class="btn btn-danger btn-lg" onclick="generarReporte('pdf')">
                                    <i class="fas fa-file-pdf"></i> Generar PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="resultadoReporte" class="mt-4" style="display:none">
                        <h5>Resultado del Reporte</h5>
                        <table class="table table-striped" id="tablaReporte">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bodyReporte"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
});

function generarReporte(formato) {
    var fechaInicio = $('#fecha_inicio').val();
    var fechaFin = $('#fecha_fin').val();
    var tipoReporte = $('#tipo_reporte').val();
    
    if(!fechaInicio || !fechaFin) {
        alert('Por favor seleccione las fechas');
        return;
    }
    
    // Simular datos del reporte
    var datos = [
        ['EST001', 'Juan Pérez', '1ro Primaria', '2026-02-10', '08:00', 'Presente'],
        ['EST002', 'María López', '1ro Primaria', '2026-02-10', '08:15', 'Atraso'],
        ['EST003', 'Carlos Ruiz', '2do Primaria', '2026-02-10', '08:00', 'Presente']
    ];
    
    if(formato === 'pdf') {
        generarPDF(datos, tipoReporte, fechaInicio, fechaFin);
    } else {
        generarExcel(datos, tipoReporte);
    }
}

function generarPDF(datos, tipoReporte, fechaInicio, fechaFin) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Encabezado
    doc.setFontSize(16);
    var titulo = 'REPORTE DE ASISTENCIA - ' + tipoReporte.toUpperCase();
    doc.text(titulo, 105, 15, { align: 'center' });
    
    doc.setFontSize(10);
    doc.text('Usuario: {{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}', 14, 25);
    doc.text('Fecha Reporte: ' + new Date().toLocaleDateString('es-BO'), 14, 30);
    doc.text('Hora: ' + new Date().toLocaleTimeString('es-BO'), 14, 35);
    doc.text('Período: ' + fechaInicio + ' al ' + fechaFin, 14, 40);
    
    // Tabla
    doc.autoTable({
        head: [['Código', 'Estudiante', 'Curso', 'Fecha', 'Hora', 'Estado']],
        body: datos,
        startY: 45,
        headStyles: { fillColor: [41, 128, 185] },
        styles: { fontSize: 9 }
    });
    
    // Pie de página
    var finalY = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(10);
    doc.text('Total de registros: ' + datos.length, 14, finalY);
    
    doc.save('reporte_asistencia_' + new Date().getTime() + '.pdf');
}

function generarExcel(datos, tipoReporte) {
    var ws_data = [['Código', 'Estudiante', 'Curso', 'Fecha', 'Hora', 'Estado']];
    ws_data = ws_data.concat(datos);
    
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Asistencias');
    XLSX.writeFile(wb, 'reporte_asistencia_' + new Date().getTime() + '.xlsx');
}
</script>
@endsection
@endsection
