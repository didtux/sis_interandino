@php
    $logoSrc = ($sistemaConfig ?? null) && $sistemaConfig->config_logo
        ? asset('storage/' . $sistemaConfig->config_logo)
        : asset('img/logo.png');
    $ueNombre = ($sistemaConfig ?? null) ? trim(($sistemaConfig->config_denominacion ?? '') . ' ' . ($sistemaConfig->config_nombre_ue ?? '')) : 'Sistema';
@endphp
<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <img class="navbar-brand-full app-header-logo" src="{{ $logoSrc }}" width="65"
             alt="{{ $ueNombre }}">
        <a href="{{ url('/') }}"></a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{{ url('/') }}" class="small-sidebar-text">
            <img class="navbar-brand-full" src="{{ $logoSrc }}" width="45px" alt="{{ $ueNombre }}"/>
        </a>
    </div>
    <ul class="sidebar-menu">
        @include('layouts.menu')
    </ul>
</aside>
