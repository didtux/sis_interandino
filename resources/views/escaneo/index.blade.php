<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Escaneo de Asistencia — U.E. Interandino Boliviano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',Tahoma,sans-serif; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; margin:0; color:#2d3748; }
        .topbar { background:rgba(0,0,0,.18); color:#fff; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; }
        .topbar .titulo { font-size:18px; font-weight:600; }
        .topbar .user { font-size:13px; opacity:.9; }
        .wrap { max-width:560px; margin:0 auto; padding:16px; }
        .panel { background:#fff; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,.2); padding:18px; margin-bottom:16px; }
        #qr-reader { width:100%; border-radius:12px; overflow:hidden; }
        .reloj { text-align:center; color:#fff; font-size:15px; margin-bottom:8px; }
        .reloj b { font-size:18px; }
        /* Tarjeta estudiante */
        #estudiante-info { display:none; text-align:center; }
        .icono-check, .icono-x { width:64px; height:64px; border-radius:50%; margin:0 auto 12px; position:relative; }
        .icono-check { background:#48bb78; }
        .icono-x { background:#e53e3e; }
        .icono-check::after { content:'✓'; }
        .icono-x::after { content:'✕'; }
        .icono-check::after, .icono-x::after { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#fff; font-size:36px; font-weight:bold; }
        .est-nombre { font-size:26px; font-weight:bold; margin:8px 0; line-height:1.25; }
        .est-detalle { background:#f7fafc; padding:12px; border-radius:10px; margin:10px 0; }
        .est-detalle p { margin:4px 0; font-size:15px; }
        .est-detalle strong { color:#2d3748; }
        .msg-exito { background:linear-gradient(135deg,#48bb78,#38a169); color:#fff; padding:12px; border-radius:12px; font-weight:bold; margin-top:8px; }
        .msg-error { background:linear-gradient(135deg,#f56565,#c53030); color:#fff; padding:12px; border-radius:12px; font-weight:bold; margin-top:8px; }
        .reg-row { font-size:13px; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-10px);} to{opacity:1;transform:translateY(0);} }
        .toast-fly { position:fixed; top:18px; left:50%; transform:translateX(-50%); z-index:9999; min-width:280px; text-align:center; animation:fadeIn .3s; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="titulo"><i class="fas fa-qrcode mr-2"></i>Escaneo de Asistencia</div>
        <div class="d-flex align-items-center">
            <span class="user mr-3"><i class="fas fa-user-circle mr-1"></i>{{ auth()->user()->us_nombres }} {{ auth()->user()->us_apellidos }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-light"><i class="fas fa-sign-out-alt"></i></button>
            </form>
        </div>
    </div>

    <div class="wrap">
        <div class="reloj">
            <span id="txtFecha"></span> · <b id="txtHora"></b>
        </div>

        {{-- Escáner --}}
        <div class="panel">
            <div id="qr-reader"></div>
            <div id="estudiante-info" class="mt-3">
                <div id="icono" class="icono-check"></div>
                <div class="est-nombre" id="est-nombre"></div>
                <div class="est-detalle" id="est-datos"></div>
                <div id="est-msg"></div>
            </div>
        </div>

        {{-- Búsqueda / ingreso manual --}}
        <div class="panel">
            <label class="font-weight-bold mb-1"><i class="fas fa-keyboard mr-1"></i>Ingreso manual (código)</label>
            <div class="input-group">
                <input type="text" id="inputCodigo" class="form-control" placeholder="Código del estudiante" autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-primary" onclick="registrarManual()"><i class="fas fa-check"></i> Registrar</button>
                </div>
            </div>
        </div>

        {{-- Mis registros de hoy --}}
        <div class="panel">
            <h6 class="mb-2"><i class="fas fa-list-check mr-1"></i>Mis registros de hoy <span class="badge badge-success" id="totalReg">{{ $misRegistros->count() }}</span></h6>
            <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Estudiante</th><th>Curso</th><th>Hora</th><th>Vía</th></tr></thead>
                    <tbody id="tablaReg">
                        @forelse($misRegistros as $r)
                            <tr class="reg-row">
                                <td>{{ $r->est_apellidos }} {{ $r->est_nombres }}</td>
                                <td><span class="badge badge-primary">{{ $r->cur_nombre }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($r->asis_hora, 8, '') }}</td>
                                <td><span class="badge badge-secondary">{{ $r->asis_origen ?? 'QR' }}</span></td>
                            </tr>
                        @empty
                            <tr id="regVacio"><td colspan="4" class="text-center text-muted py-2">Aún no escaneaste nada hoy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const CSRF = $('meta[name="csrf-token"]').attr('content');
        const URL_REGISTRAR = '{{ route("escaneo.registrar") }}';
        let totalReg = {{ $misRegistros->count() }};
        let ultimo = '';      // evita doble lectura inmediata
        let ultimoTs = 0;

        // Reloj
        function tic() {
            const d = new Date();
            const p = n => (n < 10 ? '0' + n : n);
            $('#txtFecha').text(p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear());
            $('#txtHora').text(p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds()));
        }
        setInterval(tic, 1000); tic();

        function ahora() {
            const d = new Date();
            const p = n => (n < 10 ? '0' + n : n);
            return {
                fecha: d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()),
                hora: p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds())
            };
        }

        function registrar(codigo, origen) {
            const t = ahora();
            $.ajax({
                url: URL_REGISTRAR,
                method: 'POST',
                data: { _token: CSRF, codigo: codigo, origen: origen, fecha: t.fecha, hora: t.hora },
                success: function (resp) {
                    mostrarTarjeta(resp);
                    if (resp.success && resp.estudiante) {
                        agregarFila(resp.estudiante, origen);
                    }
                },
                error: function () { toast('Error de conexión', 'danger'); }
            });
        }

        function registrarManual() {
            const c = $('#inputCodigo').val().trim();
            if (!c) { $('#inputCodigo').focus(); return; }
            registrar(c, 'MANUAL');
            $('#inputCodigo').val('');
        }
        $('#inputCodigo').on('keypress', e => { if (e.which === 13) registrarManual(); });

        function mostrarTarjeta(resp) {
            const e = resp.estudiante;
            const ok = resp.success;
            $('#icono').attr('class', ok ? 'icono-check' : 'icono-x');
            if (e) {
                $('#est-nombre').text((e.nombres || '') + ' ' + (e.apellidos || ''));
                $('#est-datos').html(
                    '<p><strong>Código:</strong> ' + (e.codigo || '') + '</p>' +
                    '<p><strong>Curso:</strong> ' + (e.curso || '') + '</p>' +
                    '<p><strong>Turno:</strong> ' + (e.turno || '') + '</p>' +
                    '<p><strong>Fecha:</strong> ' + (e.fecha || '') + ' &nbsp; <strong>Hora:</strong> ' + (e.hora || '') + '</p>'
                );
            } else {
                $('#est-nombre').text('—');
                $('#est-datos').html('');
            }
            $('#est-msg').html('<div class="' + (ok ? 'msg-exito' : 'msg-error') + '">' + (ok ? '✓ ' : '✕ ') + resp.message + '</div>');
            $('#estudiante-info').fadeIn(200);
            clearTimeout(window._hideCard);
            window._hideCard = setTimeout(() => $('#estudiante-info').fadeOut(300), 6000);
        }

        function agregarFila(e, origen) {
            $('#regVacio').remove();
            totalReg++;
            $('#totalReg').text(totalReg);
            const fila = '<tr class="reg-row" style="animation:fadeIn .4s;">' +
                '<td>' + (e.apellidos || '') + ' ' + (e.nombres || '') + '</td>' +
                '<td><span class="badge badge-primary">' + (e.curso || '') + '</span></td>' +
                '<td>' + (e.hora || '') + '</td>' +
                '<td><span class="badge badge-secondary">' + origen + '</span></td></tr>';
            $('#tablaReg').prepend(fila);
        }

        function toast(msg, tipo) {
            const el = $('<div class="alert alert-' + tipo + ' toast-fly">' + msg + '</div>');
            $('body').append(el);
            setTimeout(() => el.fadeOut(() => el.remove()), 2500);
        }

        // Lector QR
        function onScan(decodedText) {
            const now = Date.now();
            // Ignora relecturas del mismo código en < 3s
            if (decodedText === ultimo && (now - ultimoTs) < 3000) return;
            ultimo = decodedText; ultimoTs = now;
            registrar(decodedText.trim(), 'QR');
        }

        try {
            const scanner = new Html5QrcodeScanner('qr-reader', { fps: 5, qrbox: 250 });
            scanner.render(onScan);
        } catch (err) { console.log('QR no disponible:', err); }
    </script>
</body>
</html>
