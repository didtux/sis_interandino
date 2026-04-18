@php $authUser = \Illuminate\Support\Facades\Auth::user(); @endphp
<div id="EditProfileModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user mr-2"></i>Mi Perfil</h5>
                <button type="button" aria-label="Cerrar" class="close outline-none" data-dismiss="modal">×</button>
            </div>
            <form method="POST" action="{{ route('perfil.actualizar') }}" id="editProfileForm">
                @csrf
                <div class="modal-body">
                    <div id="perfilAlerta" class="alert d-none"></div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Nombres:</label>
                            <input type="text" name="us_nombres" class="form-control" value="{{ $authUser->us_nombres ?? '' }}" readonly>
                        </div>
                        <div class="form-group col-sm-6">
                            <label>Apellidos:</label>
                            <input type="text" name="us_apellidos" class="form-control" value="{{ $authUser->us_apellidos ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>CI:</label>
                            <input type="text" class="form-control" value="{{ $authUser->us_ci ?? '' }}" readonly>
                        </div>
                        <div class="form-group col-sm-6">
                            <label>Usuario:</label>
                            <input type="text" class="form-control" value="{{ $authUser->us_user ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Rol:</label>
                            <input type="text" class="form-control" value="{{ $authUser->rol->rol_nombre ?? '-' }}" readonly>
                        </div>
                        <div class="form-group col-sm-6">
                            <label>Código:</label>
                            <input type="text" class="form-control" value="{{ $authUser->us_codigo ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
