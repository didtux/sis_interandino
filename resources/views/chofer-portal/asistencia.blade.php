@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-1"><i class="fas fa-qrcode mr-2"></i>Asistencia Transporte</h4>
                            <span class="badge badge-{{ $tipo === 'IDA' ? 'success' : 'warning' }} px-3 py-2" style="font-size:0.9rem;">
                                <i class="fas fa-{{ $tipo === 'IDA' ? 'arrow-right' : 'arrow-left' }} mr-1"></i>{{ $tipo }}
                            </span>
                            <span class="badge badge-secondary px-3 py-2 ml-1" style="font-size:0.9rem;">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                        </div>
                        <div class="col-md-6 text-right">
                            <form method="GET" class="d-inline-flex align-items-center" style="gap:6px;">
                                <input type="hidden" name="ruta_codigo" value="{{ $rutaSeleccionada }}">
                                <select name="tipo" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="IDA" {{ $tipo === 'IDA' ? 'selected' : '' }}>🟢 IDA</option>
                                    <option value="VUELTA" {{ $tipo === 'VUELTA' ? 'selected' : '' }}>🟠 VUELTA</option>
                                </select>
                                <input type="date" name="fecha" value="{{ $fecha }}" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @include('chofer-portal._selector-ruta')

            @if(session('success'))<div class="alert alert-success py-2"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</div>@endif

            <div class="row">
                {{-- Panel de registro --}}
                <div class="col-lg-5">
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
                            <select id="selectEstudiante" class="form-control select2" style="width:100%">
                                <option value="">Buscar por nombre...</option>
                                @foreach($estudiantesRuta as $er)
                                    @if($er->estudiante)
                                    <option value="{{ $er->est_codigo }}">{{ $er->estudiante->est_apellidos }} {{ $er->estudiante->est_nombres }} ({{ $er->estudiante->curso->cur_nombre ?? '' }})</option>
                                    @endif
                                @endforeach
                            </select>
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
                            <h5 class="mb-0">
                                <i class="fas fa-list mr-2"></i>Registros
                                <span class="badge badge-success" id="totalRegistros">{{ $registros->count() }}</span>
                                / {{ $estudiantesRuta->count() }}
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem;" id="tablaRegistros">
                                <thead style="background:#f8f9fa;"><tr><th>N°</th><th>Estudiante</th><th>Curso</th><th>Hora</th><th>Acc.</th></tr></thead>
                                <tbody>
                                    @forelse($registros as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong>{{ $r->estudiante->est_apellidos ?? '' }} {{ $r->estudiante->est_nombres ?? '' }}</strong></td>
                                        <td><span class="badge badge-primary">{{ $r->estudiante->curso->cur_nombre ?? '' }}</span></td>
                                        <td>{{ $r->tasis_hora }}</td>
                                        <td>
                                            <form action="{{ route('chofer-portal.asistencia.eliminar', $r->tasis_id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr id="filaVacia"><td colspan="5" class="text-center text-muted py-3">Escanee un QR o busque un estudiante</td></tr>
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
var rutaCodigo = '{{ $rutaSeleccionada }}';
var tipo = '{{ $tipo }}';
var fecha = '{{ $fecha }}';
var registroCount = {{ $registros->count() }};

$(document).ready(function() {
    $('#selectEstudiante').select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Buscar estudiante...' });

    try {
        var scanner = new Html5QrcodeScanner("qr-reader", { fps: 5, qrbox: 250 });
        scanner.render(function(decodedText) {
            registrarAsistencia(decodedText);
        });
    } catch(e) { console.log('QR no disponible:', e); }
});

function registrarManual() {
    var codigo = $('#selectEstudiante').val();
    if (!codigo) { alert('Seleccione un estudiante'); return; }
    registrarAsistencia(codigo);
}

function registrarAsistencia(codigo) {
    $.ajax({
        url: '{{ route("chofer-portal.asistencia.guardar") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            ruta_codigo: rutaCodigo,
            est_codigo: codigo,
            tipo: tipo,
            fecha: fecha,
            observacion: $('#inputObservacion').val()
        },
        success: function(data) {
            if (data.success) {
                var est = data.estudiante;
                $('#filaVacia').remove();
                registroCount++;
                $('#totalRegistros').text(registroCount);
                var fila = '<tr style="animation:fadeIn .5s;"><td>' + registroCount + '</td><td><strong>' + est.nombre + '</strong></td><td><span class="badge badge-primary">' + est.curso + '</span></td><td>' + est.hora + '</td><td>-</td></tr>';
                $('#tablaRegistros tbody').prepend(fila);
                mostrarNotificacion('✓ ' + est.nombre, 'success');
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
