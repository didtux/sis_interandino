@extends('layouts.app')

@section('page_css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-route mr-2"></i>Editar Ruta</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('rutas.update', $ruta->ruta_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="ruta_nombre" class="form-control" value="{{ $ruta->ruta_nombre }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <input type="text" name="ruta_descripcion" class="form-control" value="{{ $ruta->ruta_descripcion }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="ruta_estado" class="form-control">
                                        <option value="1" {{ $ruta->ruta_estado ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ !$ruta->ruta_estado ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Trazar Ruta en el Mapa</label>
                            <p class="text-muted">Haz clic en el mapa para agregar puntos de la ruta</p>
                            <div id="mapa" style="height: 400px; border: 1px solid #ddd;"></div>
                            <input type="hidden" name="ruta_coordenadas" id="coordenadas" value="{{ $ruta->ruta_coordenadas }}">
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="limpiarRuta()">
                            <i class="fas fa-eraser"></i> Limpiar Ruta
                        </button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                        <a href="{{ route('rutas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </form>
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
<script>
let mapa = L.map('mapa').setView([-16.5000, -68.1500], 12);
let puntos = [];
let polyline = null;
let markers = [];

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(mapa);

// Cargar coordenadas existentes
const coordsExistentes = '{{ $ruta->ruta_coordenadas }}';
if (coordsExistentes) {
    try {
        puntos = JSON.parse(coordsExistentes);
        puntos.forEach((coord, index) => {
            const marker = L.marker(coord, {draggable: true}).addTo(mapa);
            
            marker.bindPopup(`
                <b>Punto ${index + 1}</b><br>
                <button onclick="eliminarMarcador(${index})" class="btn btn-sm btn-danger mt-2">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            `);
            
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                puntos[index] = [pos.lat, pos.lng];
                actualizarRuta();
            });
            
            markers.push(marker);
        });
        
        if (puntos.length > 1) {
            polyline = L.polyline(puntos, {color: 'blue'}).addTo(mapa);
            mapa.fitBounds(polyline.getBounds());
        }
    } catch (e) {
        console.error('Error al cargar coordenadas:', e);
    }
}

mapa.on('click', function(e) {
    const latlng = [e.latlng.lat, e.latlng.lng];
    puntos.push(latlng);
    
    const marker = L.marker(latlng, {draggable: true}).addTo(mapa);
    const index = puntos.length - 1;
    
    marker.bindPopup(`
        <b>Punto ${puntos.length}</b><br>
        <button onclick="eliminarMarcador(${index})" class="btn btn-sm btn-danger mt-2">
            <i class="fas fa-trash"></i> Eliminar
        </button>
    `);
    
    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        puntos[index] = [pos.lat, pos.lng];
        actualizarRuta();
    });
    
    markers.push(marker);
    actualizarRuta();
});

function actualizarRuta() {
    if (polyline) {
        mapa.removeLayer(polyline);
    }
    
    if (puntos.length > 1) {
        polyline = L.polyline(puntos, {color: 'blue'}).addTo(mapa);
    }
    
    $('#coordenadas').val(JSON.stringify(puntos));
}

function eliminarMarcador(index) {
    mapa.removeLayer(markers[index]);
    markers.splice(index, 1);
    puntos.splice(index, 1);
    
    markers.forEach((m, i) => {
        m.setPopupContent(`
            <b>Punto ${i + 1}</b><br>
            <button onclick="eliminarMarcador(${i})" class="btn btn-sm btn-danger mt-2">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        `);
    });
    
    actualizarRuta();
}

function limpiarRuta() {
    puntos = [];
    markers.forEach(m => mapa.removeLayer(m));
    markers = [];
    if (polyline) {
        mapa.removeLayer(polyline);
        polyline = null;
    }
    $('#coordenadas').val('');
}
</script>
@endsection
