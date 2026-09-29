@extends('layouts.app')

{{--
    Escaneo QR de asistencia POR CLASE, para el docente.

    El escáner que ya existía es el de portería (/escaneo): escribe en
    colegio_asistencia, no distingue materia y le devuelve 403 al docente.
    Éste escribe en notas_asistencia_clases, la tabla de asistencia por clase,
    y respeta las mismas reglas que la carga manual: sólo el docente titular,
    sólo dentro del periodo, y editar únicamente el día de hoy.
--}}

@section('content')
<div class="section-body">
    <div class="card modern-card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h4 class="mb-1"><i class="fas fa-qrcode mr-2"></i>Asistencia por QR</h4>
                    <span class="modern-badge badge-primary-modern">{{ $asignacion->curso->cur_nombre ?? '' }}</span>
                    <span class="modern-badge badge-warning-modern">{{ $asignacion->materia->mat_nombre ?? '' }}</span>
                    <span class="badge badge-light">{{ $periodo->periodo_nombre ?? ('Periodo ' . $periodo->periodo_numero) }}</span>
                </div>
                <div class="col-md-5 text-right">
                    <a href="{{ route('asistencia-clases.registrar', [$asignacion->curmatdoc_id, $periodo->periodo_id]) }}?fecha={{ $fecha }}"
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-list mr-1"></i>Carga manual
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(!$enRango)
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            La fecha <strong>{{ $fecha }}</strong> está fuera del periodo
            ({{ $periodo->periodo_fecha_inicio->format('d/m/Y') }} – {{ $periodo->periodo_fecha_fin->format('d/m/Y') }}).
            No se puede registrar.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="form-row">
                        <div class="col-7">
                            <label class="small font-weight-bold mb-1">Fecha</label>
                            <input type="date" id="fecha" class="form-control form-control-sm" value="{{ $fecha }}"
                                   min="{{ $periodo->periodo_fecha_inicio->format('Y-m-d') }}"
                                   max="{{ $periodo->periodo_fecha_fin->format('Y-m-d') }}"
                                   onchange="window.location.href='?fecha=' + this.value">
                        </div>
                        <div class="col-5">
                            <label class="small font-weight-bold mb-1">Marca</label>
                            <select id="estado" class="form-control form-control-sm">
                                <option value="P" selected>P — Presente</option>
                                <option value="A">A — Atraso</option>
                                <option value="F">F — Falta</option>
                                <option value="L">L — Licencia</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card modern-card mb-3">
                <div class="card-header py-2"><h5 class="mb-0"><i class="fas fa-camera mr-2"></i>Escanear</h5></div>
                <div class="card-body">
                    <div id="qr-reader" style="width:100%;"></div>
                </div>
            </div>

            <div class="card modern-card">
                <div class="card-header py-2"><h5 class="mb-0"><i class="fas fa-keyboard mr-2"></i>Código manual</h5></div>
                <div class="card-body">
                    <div class="input-group input-group-sm">
                        <input type="text" id="codigoManual" class="form-control" placeholder="Est00001">
                        <div class="input-group-append">
                            <button class="btn btn-success" onclick="marcar($('#codigoManual').val(), true)">
                                <i class="fas fa-plus"></i> Marcar
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Sirve cuando el alumno no trae su credencial.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card modern-card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Curso</h5>
                    <span>
                        <span class="badge badge-success" id="totalMarcados">{{ $asistencias->count() }}</span>
                        <span class="text-muted small">de {{ $estudiantes->count() }}</span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="modern-table" style="font-size:0.85rem;">
                        <thead><tr><th>N°</th><th>Estudiante</th><th class="text-center">Marca</th><th>Hora</th></tr></thead>
                        <tbody>
                            @forelse($estudiantes as $i => $est)
                                @php $a = $asistencias[$est->est_codigo] ?? null; @endphp
                                <tr id="fila-{{ $est->est_codigo }}">
                                    <td class="text-center">{{ $est->lista_numero ?? ($i + 1) }}</td>
                                    <td>{{ $est->est_apellidos }} {{ $est->est_nombres }}</td>
                                    <td class="text-center estado-celda">
                                        @if($a)
                                            <span class="badge badge-{{ ['P'=>'success','A'=>'warning','F'=>'danger','L'=>'info'][$a->asiscl_estado] ?? 'secondary' }}">{{ $a->asiscl_estado }}</span>
                                        @else
                                            <span class="badge badge-light">—</span>
                                        @endif
                                    </td>
                                    <td class="hora-celda text-muted small">
                                        {{ $a && $a->asiscl_fecha_registro ? $a->asiscl_fecha_registro->format('H:i') : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No hay estudiantes en este curso</td></tr>
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
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var puedeMarcar = {{ $enRango ? 'true' : 'false' }};
var ultimoCodigo = '', ultimoMomento = 0;

$(document).ready(function() {
    if (!puedeMarcar) return;
    try {
        var scanner = new Html5QrcodeScanner("qr-reader", { fps: 5, qrbox: 250 });
        scanner.render(function(texto) { marcar(texto, false); });
    } catch (e) { console.log('QR no disponible:', e); }
});

/**
 * El lector dispara el mismo código varias veces por segundo mientras la
 * credencial sigue delante de la cámara; se ignoran las repeticiones dentro
 * de 3 segundos para no mandar decenas de peticiones iguales.
 */
function marcar(codigo, esManual) {
    codigo = (codigo || '').trim();
    if (!codigo) return;
    if (!puedeMarcar) { aviso('La fecha está fuera del periodo', 'danger'); return; }

    var ahora = Date.now();
    if (!esManual && codigo === ultimoCodigo && (ahora - ultimoMomento) < 3000) return;
    ultimoCodigo = codigo; ultimoMomento = ahora;

    $.post('{{ route("asistencia-clases.qr-marcar") }}', {
        _token: '{{ csrf_token() }}',
        curmatdoc_id: {{ $asignacion->curmatdoc_id }},
        periodo_id: {{ $periodo->periodo_id }},
        fecha: $('#fecha').val(),
        estado: $('#estado').val(),
        codigo: codigo
    }).done(function(data) {
        if (!data.success) { aviso(data.message, 'warning'); return; }

        var e = data.estudiante;
        var color = { P: 'success', A: 'warning', F: 'danger', L: 'info' }[e.estado] || 'secondary';
        var $fila = $('#fila-' + e.codigo);
        $fila.find('.estado-celda').html('<span class="badge badge-' + color + '">' + e.estado + '</span>');
        $fila.find('.hora-celda').text(e.hora.substring(0, 5));
        $fila.css('background', '#fff8dc');
        setTimeout(function() { $fila.css('background', ''); }, 1500);

        $('#totalMarcados').text(data.total_marcados);
        $('#codigoManual').val('');
        aviso(e.nombre + ' — ' + e.estado + (data.repetido ? ' (actualizado)' : ''), data.repetido ? 'info' : 'success');
    }).fail(function() { aviso('Error al registrar', 'danger'); });
}

function aviso(msg, tipo) {
    var el = $('<div class="alert alert-' + tipo + ' py-2" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;">' + msg + '</div>');
    $('body').append(el);
    setTimeout(function() { el.fadeOut(function() { el.remove(); }); }, 2500);
}

$('#codigoManual').on('keypress', function(e) {
    if (e.which === 13) { e.preventDefault(); marcar($(this).val(), true); }
});
</script>
@endsection
