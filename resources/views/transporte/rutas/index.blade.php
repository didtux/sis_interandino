@extends('layouts.app')

@section('page_css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4><i class="fas fa-route mr-2"></i>Rutas</h4>
                    <div>
                        <button class="btn btn-danger" onclick="generarReportePDF()">
                            <i class="fas fa-file-pdf"></i> Reporte PDF
                        </button>
                        <a href="{{ route('rutas.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Ruta
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Buscar Ruta</label>
                                    <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Conductor</label>
                                    <select name="conductor" id="conductor-select" class="form-control select2">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Chofer::where('chof_estado', 1)->get() as $chofer)
                                            <option value="{{ $chofer->chof_codigo }}" {{ request('conductor') == $chofer->chof_codigo ? 'selected' : '' }}>
                                                {{ $chofer->chof_nombres }} {{ $chofer->chof_apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                                <a href="{{ route('rutas.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estudiantes</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rutas as $r)
                                <tr>
                                    <td>{{ $r->ruta_codigo }}</td>
                                    <td><strong>{{ $r->ruta_nombre }}</strong></td>
                                    <td>{{ Str::limit($r->ruta_descripcion, 50) }}</td>
                                    <td><span class="badge badge-info">{{ $r->estudiantes->count() }}</span></td>
                                    <td>
                                        <span class="badge badge-{{ $r->ruta_estado ? 'success' : 'danger' }}">
                                            {{ $r->ruta_estado ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="verDetalle({{ $r->ruta_id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="verMapa('{{ $r->ruta_codigo }}', '{{ $r->ruta_nombre }}', '{{ $r->ruta_coordenadas }}')">
                                            <i class="fas fa-map"></i>
                                        </button>
                                        <a href="{{ route('rutas.edit', $r->ruta_id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('rutas.destroy', $r->ruta_id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar ruta?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay rutas registradas</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mapa -->
<div class="modal fade" id="modalMapa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloMapa"></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="mapa" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Ruta</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="contenidoDetalle">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

let mapa = null;

function verMapa(codigo, nombre, coordenadas) {
    $('#tituloMapa').text('Ruta: ' + nombre);
    $('#modalMapa').modal('show');
    
    setTimeout(() => {
        if (mapa) {
            mapa.remove();
        }
        
        mapa = L.map('mapa').setView([-16.5000, -68.1500], 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(mapa);
        
        if (coordenadas) {
            try {
                const coords = JSON.parse(coordenadas);
                if (coords.length > 0) {
                    const polyline = L.polyline(coords, {color: 'blue'}).addTo(mapa);
                    mapa.fitBounds(polyline.getBounds());
                    
                    coords.forEach((coord, index) => {
                        L.marker(coord).addTo(mapa)
                            .bindPopup('Punto ' + (index + 1));
                    });
                }
            } catch (e) {
                console.error('Error al cargar coordenadas:', e);
            }
        }
        
        mapa.invalidateSize();
    }, 300);
}

function verDetalle(rutaId) {
    $('#modalDetalle').modal('show');
    $('#contenidoDetalle').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    
    $.get('{{ url("/rutas") }}/' + rutaId + '/detalle', function(data) {
        $('#contenidoDetalle').html(data);
    }).fail(function() {
        $('#contenidoDetalle').html('<div class="alert alert-danger">Error al cargar los datos</div>');
    });
}

function generarReportePDF() {
    const conductor = $('select[name="conductor"]').val();
    const estudiante = $('select[name="estudiante"]').val();
    const buscar = $('input[name="buscar"]').val();
    
    let url = '{{ url("/rutas/reporte-pdf") }}?';
    if (conductor) url += 'conductor=' + conductor + '&';
    if (estudiante) url += 'estudiante=' + estudiante + '&';
    if (buscar) url += 'buscar=' + buscar;
    
    window.open(url, '_blank');
}
</script>
@endsection
