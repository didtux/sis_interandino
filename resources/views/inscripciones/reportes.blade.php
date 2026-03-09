@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-file-alt mr-2"></i>Reportes de Inscripciones</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row mb-3">
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
                                <select name="est_codigo" class="form-control select2-estudiante">
                                    <option value="">Todos los estudiantes</option>
                                    @foreach($estudiantes as $est)
                                        <option value="{{ $est->est_codigo }}" {{ request('est_codigo') == $est->est_codigo ? 'selected' : '' }}>
                                            {{ $est->est_nombres }} {{ $est->est_apellidos }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Pagada</option>
                                    <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Cancelada</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                                <a href="{{ route('inscripciones.reportes') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
                                <button type="button" class="btn btn-danger" onclick="generarReportePDF()"><i class="fas fa-file-pdf"></i> Reporte PDF</button>
                                <button type="button" class="btn btn-success" onclick="generarReporteExcel()"><i class="fas fa-file-excel"></i> Reporte Excel</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped" id="tablaInscripciones">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Gestión</th>
                                <th>Fecha</th>
                                <th>Monto Total</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inscripciones ?? [] as $i)
                                <tr>
                                    <td>{{ $i->insc_codigo }}</td>
                                    <td>{{ $i->estudiante->est_nombres ?? 'N/A' }} {{ $i->estudiante->est_apellidos ?? '' }}</td>
                                    <td>{{ $i->curso->cur_nombre ?? 'N/A' }}</td>
                                    <td>{{ $i->insc_gestion }}</td>
                                    <td>{{ $i->insc_fecha->format('d/m/Y') }}</td>
                                    <td>{{ number_format($i->insc_monto_total, 2) }}</td>
                                    <td>{{ number_format($i->insc_monto_pagado, 2) }}</td>
                                    <td>{{ number_format($i->insc_saldo, 2) }}</td>
                                    <td>
                                        @if($i->insc_estado == 2)
                                            <span class="badge badge-success">Pagada</span>
                                        @elseif($i->insc_saldo > 0)
                                            <span class="badge badge-warning">Pendiente</span>
                                        @else
                                            <span class="badge badge-danger">Cancelada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">No hay inscripciones</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-estudiante').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Buscar estudiante...',
        allowClear: true
    });
});

function generarReportePDF() {
    var params = new URLSearchParams(window.location.search);
    window.open('{{ route("inscripciones.reporte-pdf") }}?' + params.toString(), '_blank');
}

function generarReporteExcel() {
    var tabla = document.getElementById('tablaInscripciones').cloneNode(true);
    var wb = XLSX.utils.table_to_book(tabla);
    XLSX.writeFile(wb, 'reporte_inscripciones_' + new Date().getTime() + '.xlsx');
}
</script>
@endsection
