@extends('layouts.app')
@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-1"><i class="fas fa-qrcode mr-2"></i>Registrar Asistencia</h4>
                            <span class="modern-badge badge-primary-modern">{{ $categoria->actividad->act_nombre }}</span>
                            <span class="modern-badge badge-warning-modern">{{ $categoria->actcat_nombre }}</span>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('actividades-asistencia.show', $categoria->act_id) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))<div class="alert alert-success-modern"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif

            <div class="row">
                {{-- Panel de registro --}}
                <div class="col-lg-5">
                    {{-- Categoría y tipo de marca: antes la categoría venía fija en la
                         URL y sólo se podía registrar una marca por alumno --}}
                    <div class="card modern-card mb-3">
                        <div class="card-body py-3">
                            <label class="small font-weight-bold mb-1">Categoría</label>
                            <select id="selectCategoria" class="form-control form-control-sm">
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->actcat_id }}" {{ $cat->actcat_id == $categoria->actcat_id ? 'selected' : '' }}>
                                        {{ $cat->actcat_nombre }}
                                    </option>
                                @endforeach
                            </select>

                            <label class="small font-weight-bold mb-1 mt-3 d-block">Tipo de marca</label>
                            <div class="btn-group btn-block" role="group">
                                <button type="button" id="btnIngreso" class="btn btn-success" onclick="setTipo('INGRESO')">
                                    <i class="fas fa-sign-in-alt mr-1"></i>INGRESO
                                </button>
                                <button type="button" id="btnSalida" class="btn btn-outline-danger" onclick="setTipo('SALIDA')">
                                    <i class="fas fa-sign-out-alt mr-1"></i>SALIDA
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Escáner QR --}}
                    <div class="card modern-card mb-3">
                        <div class="card-header py-2"><h5 class="mb-0"><i class="fas fa-qrcode mr-2"></i>Escanear QR</h5></div>
                        <div class="card-body">
                            <div id="qr-reader" style="width:100%;"></div>
                            <div id="qr-result" class="mt-2"></div>
                        </div>
                    </div>

                    {{-- Búsqueda manual --}}
                    <div class="card modern-card">
                        <div class="card-header py-2"><h5 class="mb-0"><i class="fas fa-search mr-2"></i>Buscar Estudiante</h5></div>
                        <div class="card-body">
                            <div class="input-group">
                                <select id="selectEstudiante" class="form-control select2" style="width:100%">
                                    <option value="">Buscar por nombre...</option>
                                    @foreach($estudiantes as $e)
                                        <option value="{{ $e->est_codigo }}">{{ $e->est_apellidos }} {{ $e->est_nombres }} ({{ $e->curso->cur_nombre ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="text" id="inputObservacion" class="form-control form-control-sm mt-2" placeholder="Observación (opcional)">
                            <button class="btn btn-success btn-block mt-2" onclick="registrarManual()"><i class="fas fa-plus mr-1"></i>Registrar</button>
                            <div id="manual-result" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                {{-- Lista de registros --}}
                <div class="col-lg-7">
                    <div class="card modern-card">
                        <div class="card-header py-2 d-flex justify-content-between">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Registros <span class="badge badge-success" id="totalRegistros">{{ $registros->count() }}</span></h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="modern-table" style="font-size:0.85rem;" id="tablaRegistros">
                                <thead><tr><th>N°</th><th>Estudiante</th><th>Curso</th><th>Tipo</th><th>Hora</th><th>Acc.</th></tr></thead>
                                <tbody>
                                    @forelse($registros as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong>{{ $r->estudiante->est_apellidos ?? '' }} {{ $r->estudiante->est_nombres ?? '' }}</strong></td>
                                        <td><span class="badge badge-primary">{{ $r->estudiante->curso->cur_nombre ?? '' }}</span></td>
                                        <td><span class="badge badge-{{ ($r->actreg_tipo ?? 'INGRESO') === 'SALIDA' ? 'danger' : 'success' }}">{{ $r->actreg_tipo ?? 'INGRESO' }}</span></td>
                                        <td>{{ $r->actreg_hora }}</td>
                                        <td>
                                            <form action="{{ route('actividades-asistencia.eliminar-registro', $r->actreg_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr id="filaVacia"><td colspan="6" class="text-center text-muted py-3">Escanee un QR o busque un estudiante</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var catId = {{ $categoria->actcat_id }};
var registroCount = {{ $registros->count() }};
var tipoMarca = 'INGRESO';

/** INGRESO / SALIDA, mismo criterio que el toggle del kardex del docente. */
function setTipo(t) {
    tipoMarca = t;
    $('#btnIngreso').toggleClass('btn-success', t === 'INGRESO').toggleClass('btn-outline-success', t !== 'INGRESO');
    $('#btnSalida').toggleClass('btn-danger', t === 'SALIDA').toggleClass('btn-outline-danger', t !== 'SALIDA');
}

/** Cambiar de categoría sin salir de la pantalla de escaneo. */
$(document).on('change', '#selectCategoria', function() {
    window.location.href = '{{ url("actividades-asistencia/registrar") }}/' + $(this).val();
});

$(document).ready(function() {
    $('#selectEstudiante').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Buscar estudiante...' });

    // Iniciar escáner QR
    try {
        var scanner = new Html5QrcodeScanner("qr-reader", { fps: 5, qrbox: 250 });
        scanner.render(function(decodedText) {
            registrarAsistencia(decodedText, 'qr');
        });
    } catch(e) { console.log('QR no disponible:', e); }
});

function registrarManual() {
    var codigo = $('#selectEstudiante').val();
    if (!codigo) { alert('Seleccione un estudiante'); return; }
    registrarAsistencia(codigo, 'manual');
}

function registrarAsistencia(codigo, origen) {
    $.ajax({
        url: '{{ route("actividades-asistencia.guardar-registro") }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', actcat_id: catId, est_codigo: codigo, actreg_tipo: tipoMarca, observacion: $('#inputObservacion').val() },
        success: function(data) {
            if (data.success) {
                var est = data.estudiante;
                $('#filaVacia').remove();
                registroCount++;
                $('#totalRegistros').text(registroCount);
                var badgeTipo = '<span class="badge badge-' + (est.tipo === 'SALIDA' ? 'danger' : 'success') + '">' + est.tipo + '</span>';
                var fila = '<tr style="animation:fadeIn .5s;"><td>' + registroCount + '</td><td><strong>' + est.nombre + '</strong></td><td><span class="badge badge-primary">' + est.curso + '</span></td><td>' + badgeTipo + '</td><td>' + est.hora + '</td><td>-</td></tr>';
                $('#tablaRegistros tbody').prepend(fila);
                mostrarNotificacion(est.nombre + ' - ' + est.tipo, 'success');
                $('#selectEstudiante').val('').trigger('change');
                $('#inputObservacion').val('');
            } else {
                mostrarNotificacion(data.message, 'warning');
            }
        },
        error: function() { mostrarNotificacion('Error al registrar', 'danger'); }
    });
}

function mostrarNotificacion(msg, tipo) {
    var el = $('<div class="alert alert-' + tipo + ' py-2" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;animation:fadeIn .3s;">' + msg + '</div>');
    $('body').append(el);
    setTimeout(function() { el.fadeOut(function() { el.remove(); }); }, 3000);
}
</script>
<style>@keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}</style>
@endsection
