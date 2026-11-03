{{-- Pestañas de Web pública --}}
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ Request::is('web-publica/textos*') || Request::is('web-publica') ? 'active' : '' }}" href="{{ route('web-publica.textos') }}"><i class="fas fa-font mr-1"></i>Textos de las páginas</a></li>
    <li class="nav-item"><a class="nav-link {{ Request::is('web-publica/bloques*') ? 'active' : '' }}" href="{{ route('web-publica.bloques') }}"><i class="fas fa-th-large mr-1"></i>Avisos, requisitos y más</a></li>
    <li class="nav-item"><a class="nav-link {{ Request::is('web-publica/redes*') ? 'active' : '' }}" href="{{ route('web-publica.redes') }}"><i class="fas fa-share-alt mr-1"></i>Contacto y redes</a></li>
    <li class="nav-item ml-auto"><a class="nav-link" href="{{ route('landing') }}" target="_blank"><i class="fas fa-external-link-alt mr-1"></i>Ver la web</a></li>
</ul>
