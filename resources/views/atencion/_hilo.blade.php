{{-- Hilo con refresco automático. $ticket, $lado, $urlMensajes, $urlResponder, $puedeResponder --}}
<style>
    .at-hilo { background: #f4f6fa; border-radius: 10px; padding: 14px; max-height: 60vh; overflow-y: auto; }
    .at-msg { display: flex; margin-bottom: 10px; }
    .at-mio { justify-content: flex-end; }
    .at-burbuja { max-width: 78%; border-radius: 12px; padding: 8px 12px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
    .at-mio .at-burbuja { background: #dcebff; }
    .at-autor { font-size: .75rem; font-weight: 700; color: #1a3a6c; }
    .at-texto { white-space: pre-line; word-wrap: break-word; }
    .at-hora { font-size: .7rem; color: #8a94a6; text-align: right; margin-top: 2px; }
    .at-img { max-width: 220px; max-height: 220px; border-radius: 8px; margin-top: 6px; display: block; }
    .at-sistema { text-align: center; margin: 8px 0; }
    .at-sistema span { font-size: .75rem; color: #6c757d; background: #e9ecef; border-radius: 10px; padding: 2px 10px; }
</style>
<div class="at-hilo" id="atHilo">
    @include('atencion._mensajes', ['mensajes' => $ticket->mensajes, 'lado' => $lado])
</div>
@if($puedeResponder)
<form id="atForm" method="POST" action="{{ $urlResponder }}" enctype="multipart/form-data" class="mt-3">
    @csrf
    <textarea name="texto" id="atTexto" class="form-control" rows="3" maxlength="3000" placeholder="Escriba su mensaje..." required style="height:auto"></textarea>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap">
        <input type="file" name="adjunto" accept="image/*,application/pdf" class="form-control-file" style="max-width:300px">
        <button class="btn btn-primary" id="atEnviar"><i class="fas fa-paper-plane mr-1"></i>Enviar</button>
    </div>
    <small class="text-muted">Foto o PDF de hasta 5 MB (opcional).</small>
</form>
@endif
<script>
(function () {
    var hilo = document.getElementById('atHilo');
    var ultimo = {{ (int) $ticket->mensajes->max('am_id') }};
    hilo.scrollTop = hilo.scrollHeight;

    function traer() {
        fetch('{{ $urlMensajes }}?desde=' + ultimo, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || !d.html) return;
                hilo.insertAdjacentHTML('beforeend', d.html);
                ultimo = d.ultimo;
                hilo.scrollTop = hilo.scrollHeight;
            }).catch(function () {});
    }
    setInterval(function () { if (!document.hidden) traer(); }, {{ (int) config('atencion.refresco_segundos', 30) }} * 1000);

    // Enviar sin recargar: se manda y se trae el mensaje propio con el mismo refresco
    var form = document.getElementById('atForm');
    if (form) form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('atEnviar');
        btn.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.ok) { alert((res.j.errors && Object.values(res.j.errors)[0][0]) || res.j.message || 'No se pudo enviar.'); return; }
                form.reset(); traer();
            })
            .catch(function () { alert('No hay conexión con el servidor.'); })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
