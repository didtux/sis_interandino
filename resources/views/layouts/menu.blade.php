<li class="side-menus {{ Request::is('home') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('home') }}">
        <i class="fas fa-home"></i><span>Dashboard</span>
    </a>
</li>

<li class="side-menus {{ Request::is('usuarios*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('usuarios.index') }}">
        <i class="fas fa-users-cog"></i><span>Usuarios</span>
    </a>
</li>

<li class="side-menus {{ Request::is('estudiantes*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('estudiantes.index') }}">
        <i class="fas fa-user-graduate"></i><span>Estudiantes</span>
    </a>
</li>

<li class="side-menus {{ Request::is('cursos*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('cursos.index') }}">
        <i class="fas fa-chalkboard"></i><span>Cursos</span>
    </a>
</li>

<li class="side-menus {{ Request::is('docentes*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('docentes.index') }}">
        <i class="fas fa-chalkboard-teacher"></i><span>Docentes</span>
    </a>
</li>

<li class="side-menus {{ Request::is('materias*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('materias.index') }}">
        <i class="fas fa-book"></i><span>Materias</span>
    </a>
</li>

<li class="side-menus {{ Request::is('asistencias*') || Request::is('asistencia-config*') ? 'active' : '' }}">
    <a class="nav-link menu-toggle" href="#" data-toggle="collapse" data-target="#asistenciaMenu">
        <i class="fas fa-clipboard-check"></i><span>Asistencias</span>
    </a>
    <ul class="collapse {{ Request::is('asistencias*') || Request::is('asistencia-config*') ? 'show' : '' }}" id="asistenciaMenu">
        <li><a href="{{ route('asistencias.index') }}"><i class="fas fa-list"></i> Registro</a></li>
        <li><a href="{{ route('asistencia-config.index') }}"><i class="fas fa-cog"></i> Configuración</a></li>
        <li><a href="{{ route('asistencia-config.atrasos') }}"><i class="fas fa-clock"></i> Atrasos</a></li>
        <li><a href="{{ route('asistencia-config.permisos') }}"><i class="fas fa-file-alt"></i> Permisos</a></li>
        <li><a href="{{ route('asistencia-config.festivos') }}"><i class="fas fa-calendar-day"></i> Festivos</a></li>
        <li><a href="{{ route('asistencia-config.reportes') }}"><i class="fas fa-file-pdf"></i> Reportes</a></li>
    </ul>
</li>

<li class="side-menus {{ Request::is('notas*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('notas.index') }}">
        <i class="fas fa-star"></i><span>Notas</span>
    </a>
</li>

<li class="side-menus {{ Request::is('padres*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('padres.index') }}">
        <i class="fas fa-users"></i><span>Padres de Familia</span>
    </a>
</li>

<li class="side-menus {{ Request::is('pagos*') || Request::is('inscripciones*') || Request::is('servicios*') ? 'active' : '' }}">
    <a class="nav-link menu-toggle" href="#" data-toggle="collapse" data-target="#pagosMenu">
        <i class="fas fa-money-bill-wave"></i><span>Pagos</span>
    </a>
    <ul class="collapse {{ Request::is('pagos*') || Request::is('inscripciones*') || Request::is('servicios*') || Request::is('descuentos*') ? 'show' : '' }}" id="pagosMenu">
        <li><a href="{{ route('inscripciones.index') }}"><i class="fas fa-user-plus"></i> Inscripciones</a></li>
        <li><a href="{{ route('inscripciones.reportes') }}"><i class="fas fa-file-pdf"></i> Reportes</a></li>
        <li><a href="{{ route('pagos.index') }}"><i class="fas fa-money-check"></i> Mensualidades</a></li>
        <li><a href="{{ route('descuentos.index') }}"><i class="fas fa-percent"></i> Descuentos</a></li>
        <li><a href="{{ route('servicios.index') }}"><i class="fas fa-concierge-bell"></i> Servicios</a></li>
        <li><a href="{{ route('pagos-servicios.index') }}"><i class="fas fa-file-invoice-dollar"></i> Pagos Servicios</a></li>
    </ul>
</li>

<li class="side-menus {{ Request::is('categorias*') || Request::is('productos*') || Request::is('ventas*') || Request::is('proveedores*') || Request::is('movimientos*') ? 'active' : '' }}">
    <a class="nav-link menu-toggle" href="#" data-toggle="collapse" data-target="#ventasMenu">
        <i class="fas fa-shopping-cart"></i><span>Ventas y Almacén</span>
    </a>
    <ul class="collapse {{ Request::is('categorias*') || Request::is('productos*') || Request::is('ventas*') || Request::is('proveedores*') || Request::is('movimientos*') ? 'show' : '' }}" id="ventasMenu">
        <li><a href="{{ route('proveedores.index') }}"><i class="fas fa-truck"></i> Proveedores</a></li>
        <li><a href="{{ route('categorias.index') }}"><i class="fas fa-tags"></i> Categorías</a></li>
        <li><a href="{{ route('productos.index') }}"><i class="fas fa-box"></i> Productos</a></li>
        <li><a href="{{ route('ventas.index') }}"><i class="fas fa-cash-register"></i> Ventas</a></li>
        <li><a href="{{ route('movimientos.index') }}"><i class="fas fa-exchange-alt"></i> Movimientos</a></li>
        <li><a href="{{ route('movimientos.reporte-stock') }}"><i class="fas fa-warehouse"></i> Reporte Stock</a></li>
    </ul>
</li>

<li class="side-menus {{ Request::is('agenda*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('agenda.index') }}">
        <i class="fas fa-calendar-alt"></i><span>Agenda</span>
    </a>
</li>

<li class="side-menus {{ Request::is('psicopedagogia*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('psicopedagogia.index') }}">
        <i class="fas fa-brain"></i><span>Psicopedagogía</span>
    </a>
</li>

<li class="side-menus {{ Request::is('enfermeria*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('enfermeria.index') }}">
        <i class="fas fa-heartbeat"></i><span>Enfermería</span>
    </a>
</li>

<li class="side-menus {{ Request::is('vehiculos*') || Request::is('choferes*') || Request::is('rutas*') || Request::is('asignaciones-transporte*') || Request::is('pagos-transporte*') || Request::is('estudiantes-rutas*') ? 'active' : '' }}">
    <a class="nav-link menu-toggle" href="#" data-toggle="collapse" data-target="#transporteMenu">
        <i class="fas fa-bus"></i><span>Transporte</span>
    </a>
    <ul class="collapse {{ Request::is('vehiculos*') || Request::is('choferes*') || Request::is('rutas*') || Request::is('asignaciones-transporte*') || Request::is('pagos-transporte*') || Request::is('estudiantes-rutas*') ? 'show' : '' }}" id="transporteMenu">
        <li><a href="{{ route('vehiculos.index') }}"><i class="fas fa-car"></i> Vehículos</a></li>
        <li><a href="{{ route('choferes.index') }}"><i class="fas fa-id-card"></i> Choferes</a></li>
        <li><a href="{{ route('rutas.index') }}"><i class="fas fa-route"></i> Rutas</a></li>
        <li><a href="{{ route('asignaciones-transporte.index') }}"><i class="fas fa-tasks"></i> Asignaciones</a></li>
        <li><a href="{{ route('pagos-transporte.index') }}"><i class="fas fa-money-bill"></i> Pagos</a></li>
        <li><a href="{{ route('estudiantes-rutas.index') }}"><i class="fas fa-users"></i> Estudiantes</a></li>
    </ul>
</li>
