<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title') | {{ config('app.name') }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 4.1.1 -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <!-- Ionicons -->
    <link href="//fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('vendor/select2-bootstrap4/select2-bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>

@yield('page_css')
<!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    <link rel="stylesheet" href="{{ asset('web/css/custom-tables.css')}}">

    @yield('css')
</head>
<body>

<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <div class="navbar-bg"></div>
        <nav class="navbar navbar-expand-lg main-navbar">
            @include('layouts.header')

        </nav>
        <div class="main-sidebar main-sidebar-postion">
            @include('layouts.sidebar')
        </div>
        <!-- Main Content -->
        <div class="main-content">
            @yield('content')
        </div>
        <footer class="main-footer">
            @include('layouts.footer')
        </footer>
    </div>
</div>

</body>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/iziToast.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.nicescroll.js') }}"></script>

<!-- Template JS File -->
<script src="{{ asset('web/js/stisla.js') }}"></script>
<script src="{{ asset('web/js/scripts.js') }}"></script>
<script src="{{ asset('web/js/custom-tables.js') }}"></script>
<script src="{{ mix('assets/js/profile.js') }}"></script>
<script src="{{ mix('assets/js/custom/custom.js') }}"></script>
<script>
    // Mensaje legible de un error AJAX. Antes cada pantalla mostraba
    // "Error al anular" sin decir si faltaba permiso, se había vencido la
    // sesión o la dirección no existía, y no había forma de diagnosticarlo.
    window.mensajeErrorAjax = function (xhr) {
        var r = (xhr && xhr.responseJSON) || {};
        var detalle = r.message || r.error || '';
        switch (xhr && xhr.status) {
            case 0:   return 'No hay conexión con el servidor.';
            case 403: return 'Su rol no tiene permiso para esta acción.' + (detalle ? ' (' + detalle + ')' : '');
            case 404: return 'No se encontró el registro o la dirección.';
            case 419: return 'La sesión expiró. Recargue la página e intente de nuevo.';
            case 422: return detalle || 'Los datos enviados no son válidos.';
            default:  return (detalle || 'Error del servidor') + ' [' + (xhr ? xhr.status : '?') + ']';
        }
    };
</script>

@include('layouts.partials.loader-reportes')

@include('profile.change_password')
@include('profile.edit_profile')

@yield('page_js')
@yield('scripts')
@stack('scripts')
<script>
    // Select2 para los selects con la clase js-select2. Las listas cortas no
    // muestran buscador; en formularios en línea el ancho sigue al del select
    // (más el espacio de la flecha) y form-control-sm se mantiene chico.
    // Para contenido cargado después: initSelect2(contenedor).
    window.initSelect2 = function (ctx) {
        $(ctx || document).find('select.js-select2').not('.select2-hidden-accessible').each(function () {
            var $s = $(this), $modal = $s.closest('.modal');
            var enLinea = $s.closest('.form-inline, .d-flex, .input-group').length > 0;
            $s.select2({
                theme: 'bootstrap4',
                width: enLinea ? ($s.outerWidth() + 30) + 'px' : '100%',
                minimumResultsForSearch: 8,
                dropdownParent: $modal.length ? $modal : $(document.body)
            });
            if ($s.hasClass('form-control-sm')) $s.next('.select2-container').addClass('select2-sm');
            if (enLinea && $s.hasClass('mr-2')) $s.next('.select2-container').addClass('mr-2');
        });
    };
    $(function () { initSelect2(); });
</script>
<script>
    let loggedInUser =@json(\Illuminate\Support\Facades\Auth::user());
    let loginUrl = '{{ route('login') }}';
    const userUrl = '{{url('users')}}';
    // Loading button plugin (removed from BS4)
    (function ($) {
        $.fn.button = function (action) {
            if (action === 'loading' && this.data('loading-text')) {
                this.data('original-text', this.html()).html(this.data('loading-text')).prop('disabled', true);
            }
            if (action === 'reset' && this.data('original-text')) {
                this.html(this.data('original-text')).prop('disabled', false);
            }
        };
    }(jQuery));

    // Auto-scroll sidebar cuando se expande un menú
    $(document).ready(function() {
        $('.menu-toggle').on('shown.bs.collapse', function(e) {
            const $sidebar = $('.main-sidebar');
            const $menuItem = $(this).closest('.side-menus');
            const targetId = $(this).attr('data-target');
            const $submenu = $(targetId);
            
            if ($menuItem.length && $menuItem.offset()) {
                const menuBottom = $menuItem.offset().top + $menuItem.outerHeight() + $submenu.outerHeight();
                const sidebarBottom = $sidebar.offset().top + $sidebar.height();
                
                if (menuBottom > sidebarBottom) {
                    const scrollAmount = menuBottom - sidebarBottom + 20;
                    $sidebar.animate({
                        scrollTop: $sidebar.scrollTop() + scrollAmount
                    }, 300);
                }
            }
        });
        
        $('.main-sidebar [data-toggle="collapse"]').on('click', function() {
            const $this = $(this);
            const targetId = $this.attr('data-target');
            
            $(targetId).on('shown.bs.collapse', function() {
                const $sidebar = $('.main-sidebar');
                const $menuItem = $this.closest('.side-menus');
                const $submenu = $(this);
                
                if ($menuItem.length && $menuItem.offset()) {
                    const menuBottom = $menuItem.offset().top + $menuItem.outerHeight() + $submenu.outerHeight();
                    const sidebarBottom = $sidebar.offset().top + $sidebar.height();
                    
                    if (menuBottom > sidebarBottom) {
                        const scrollAmount = menuBottom - sidebarBottom + 20;
                        $sidebar.animate({
                            scrollTop: $sidebar.scrollTop() + scrollAmount
                        }, 300);
                    }
                }
            });
        });
    });
</script>
</html>
