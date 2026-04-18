<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var qrScanner = null;
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    // Cargar padres al seleccionar estudiante
    $('#est_codigo').on('change', function() {
        var codigo = $(this).val();
        if (!codigo) {
            $('#padres-container').html('<p class="text-muted" style="font-size:0.85rem;"><i class="fas fa-info-circle mr-1"></i>Seleccione un estudiante para ver sus padres</p>');
            return;
        }
        $.get('/agenda/padres-estudiante/' + codigo, function(padres) {
            if (padres.length === 0) {
                $('#padres-container').html('<p class="text-muted" style="font-size:0.85rem;"><i class="fas fa-exclamation-circle mr-1"></i>Sin padres registrados</p>');
                return;
            }
            var html = '';
            padres.forEach(function(p) {
                html += '<div class="alert alert-info py-2 px-3 mb-2" style="font-size:0.85rem;">';
                html += '<strong><i class="fas fa-user mr-1"></i>' + p.pfam_nombres + '</strong>';
                if (p.pfam_numeroscelular) html += '<br><i class="fas fa-phone mr-1"></i>' + p.pfam_numeroscelular;
                if (p.pfam_correo) html += '<br><i class="fas fa-envelope mr-1"></i>' + p.pfam_correo;
                html += '</div>';
            });
            $('#padres-container').html(html);
        });
    });

    // Si ya hay estudiante seleccionado (edit), cargar padres
    if ($('#est_codigo').val()) {
        $('#est_codigo').trigger('change');
    }

    // QR Scanner
    $('#btnToggleQR').on('click', function() {
        var $container = $('#qr-reader-container');
        if ($container.is(':visible')) {
            $container.slideUp();
            if (qrScanner) { qrScanner.clear(); qrScanner = null; }
            $(this).html('<i class="fas fa-qrcode mr-1"></i>Escanear QR');
        } else {
            $container.slideDown();
            $(this).html('<i class="fas fa-times mr-1"></i>Cerrar Escáner');
            qrScanner = new Html5QrcodeScanner("qr-reader", { fps: 5, qrbox: 250 });
            qrScanner.render(function(decodedText) {
                var $option = $('#est_codigo option[value="' + decodedText + '"]');
                if ($option.length) {
                    $('#est_codigo').val(decodedText).trigger('change');
                    swal('Estudiante encontrado', $option.text().trim(), 'success');
                    qrScanner.clear(); qrScanner = null;
                    $('#qr-reader-container').slideUp();
                    $('#btnToggleQR').html('<i class="fas fa-qrcode mr-1"></i>Escanear QR');
                } else {
                    swal('No encontrado', 'El código escaneado no corresponde a ningún estudiante.', 'error');
                }
            });
        }
    });
});
</script>
