<div id="changePasswordModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lock mr-2"></i>Cambiar Contraseña</h5>
                <button type="button" aria-label="Cerrar" class="close outline-none" data-dismiss="modal">×</button>
            </div>
            <form id="changePasswordForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Contraseña Actual: <span class="text-danger">*</span></label>
                        <input class="form-control" id="pfCurrentPassword" type="password" name="password_current" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña: <span class="text-danger">*</span></label>
                        <input class="form-control" id="pfNewPassword" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña: <span class="text-danger">*</span></label>
                        <input class="form-control" id="pfNewConfirmPassword" type="password" name="password_confirmation" required>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="btnCambiarPassword">
                            <i class="fas fa-save mr-1"></i>Guardar
                        </button>
                        <button type="button" class="btn btn-secondary ml-1" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();

        var actual = $('#pfCurrentPassword').val();
        var nueva = $('#pfNewPassword').val();
        var confirmar = $('#pfNewConfirmPassword').val();

        if (!actual) {
            swal('Campo requerido', 'Debe ingresar su contraseña actual.', 'warning');
            return;
        }
        if (!nueva) {
            swal('Campo requerido', 'Debe ingresar la nueva contraseña.', 'warning');
            return;
        }
        if (nueva.length < 6) {
            swal('Contraseña muy corta', 'La nueva contraseña debe tener al menos 6 caracteres.', 'warning');
            return;
        }
        if (nueva !== confirmar) {
            swal('No coinciden', 'La nueva contraseña y su confirmación no coinciden.', 'error');
            return;
        }

        $('#btnCambiarPassword').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

        $.ajax({
            url: '/perfil/cambiar-password',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { password_current: actual, password: nueva, password_confirmation: confirmar },
            success: function(data) {
                $('#btnCambiarPassword').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar');
                if (data.success) {
                    $('#changePasswordModal').modal('hide');
                    $('#pfCurrentPassword, #pfNewPassword, #pfNewConfirmPassword').val('');
                    swal({
                        title: '¡Contraseña actualizada!',
                        text: 'Debe iniciar sesión nuevamente con su nueva contraseña.',
                        icon: 'success',
                        buttons: { confirm: { text: 'Ir al Login', className: 'btn btn-primary' } },
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    }, function() {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/logout';
                        var csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = $('meta[name="csrf-token"]').attr('content');
                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    });
                } else {
                    swal('Error', data.message, 'error');
                }
            },
            error: function(xhr) {
                $('#btnCambiarPassword').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar');
                var msg = 'Error al cambiar la contraseña.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                swal('Error', msg, 'error');
            }
        });
    });
});
</script>
