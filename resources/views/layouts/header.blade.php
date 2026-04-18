<form class="form-inline mr-auto" action="#">
    <ul class="navbar-nav mr-3">
        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>
</form>
<ul class="navbar-nav navbar-right">
    @if(\Illuminate\Support\Facades\Auth::user())
        @php $authUser = \Illuminate\Support\Facades\Auth::user(); @endphp
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="image" src="{{ $authUser->us_foto ? asset('storage/' . $authUser->us_foto) : asset('img/logo.png') }}"
                     class="rounded-circle mr-1 thumbnail-rounded user-thumbnail">
                <div class="d-sm-none d-lg-inline-block">{{ $authUser->us_nombres }}</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">{{ $authUser->us_nombres }} {{ $authUser->us_apellidos }}</div>
                <a class="dropdown-item has-icon" href="#" data-toggle="modal" data-target="#EditProfileModal">
                    <i class="fa fa-user"></i> Mi Perfil
                </a>
                <a class="dropdown-item has-icon" href="#" data-toggle="modal" data-target="#changePasswordModal">
                    <i class="fa fa-lock"></i> Cambiar Contraseña
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ url('logout') }}" class="dropdown-item has-icon text-danger"
                   onclick="event.preventDefault(); localStorage.clear(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
                <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="d-none">
                    {{ csrf_field() }}
                </form>
            </div>
        </li>
    @else
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <div class="d-sm-none d-lg-inline-block">Cuenta</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('login') }}" class="dropdown-item has-icon">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
            </div>
        </li>
    @endif
</ul>
