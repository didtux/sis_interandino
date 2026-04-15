<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Interandino Boliviano') — Unidad Educativa Privada</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        :root {
            --primary:   #1a3a6c;
            --secondary: #0d2247;
            --accent:    #f5a623;
            --accent2:   #e8941a;
            --light:     #f5f7fa;
            --white:     #ffffff;
            --dark:      #1e2738;
            --text:      #4a5568;
            --border:    #e2e8f0;
            --green:     #2a7d4f;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background: var(--white);
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000;
            background: rgba(26, 58, 108, 0.97);
            backdrop-filter: blur(10px);
            padding: 0 2rem; height: 70px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 20px rgba(0,0,0,0.25);
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            height: 60px;
            background: rgba(13, 34, 71, 0.99);
        }
        .navbar-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .navbar-brand img {
            height: 46px; width: 46px; object-fit: contain;
            border-radius: 50%; background: white; padding: 2px;
            box-shadow: 0 0 0 2px var(--accent);
        }
        .brand-text { display: flex; flex-direction: column; }
        .brand-text .name {
            font-size: 1rem; font-weight: 700; color: white;
            line-height: 1.2; letter-spacing: 0.3px;
        }
        .brand-text .sub {
            font-size: 0.65rem; color: var(--accent);
            font-weight: 500; letter-spacing: 1px; text-transform: uppercase;
        }
        .nav-links {
            display: flex; align-items: center;
            gap: 0.1rem; list-style: none;
        }
        .nav-links a {
            color: rgba(255,255,255,0.82);
            text-decoration: none; font-size: 0.85rem; font-weight: 500;
            padding: 0.4rem 0.9rem; border-radius: 6px;
            transition: all 0.25s ease; letter-spacing: 0.2px;
        }
        .nav-links a:hover,
        .nav-links a.nav-active {
            color: white;
            background: rgba(255,255,255,0.13);
        }
        .nav-links a.nav-active {
            font-weight: 600;
        }
        .btn-nav {
            background: var(--accent) !important;
            color: var(--dark) !important; font-weight: 700 !important;
            padding: 0.4rem 1.2rem !important; border-radius: 25px !important;
            box-shadow: 0 4px 12px rgba(245,166,35,0.4) !important;
            transition: all 0.25s ease !important;
        }
        .btn-nav:hover {
            background: var(--accent2) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(245,166,35,0.5) !important;
        }
        .hamburger {
            display: none; background: none; border: none;
            cursor: pointer; padding: 0.4rem;
        }
        .hamburger span {
            display: block; width: 24px; height: 2px;
            background: white; margin: 5px 0;
            border-radius: 2px; transition: 0.3s;
        }
        .hamburger.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        /* ===== MOBILE NAV ===== */
        .mobile-menu {
            display: none; position: fixed;
            top: 70px; left: 0; right: 0;
            background: var(--secondary); z-index: 999;
            padding: 1.5rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .mobile-menu.open { display: block; }
        .mobile-menu ul { list-style: none; display: flex; flex-direction: column; gap: 0.4rem; }
        .mobile-menu a {
            display: block; color: rgba(255,255,255,0.85);
            text-decoration: none; padding: 0.75rem 1rem;
            border-radius: 8px; font-size: 0.95rem; font-weight: 500;
            transition: all 0.2s;
        }
        .mobile-menu a:hover,
        .mobile-menu a.nav-active {
            background: rgba(255,255,255,0.1); color: white;
        }
        .mobile-menu .btn-nav {
            display: block; text-align: center;
            background: var(--accent) !important; color: var(--dark) !important;
            font-weight: 700 !important; margin-top: 0.5rem; border-radius: 8px !important;
        }

        /* ===== PAGE HERO BANNER (sub-pages) ===== */
        .page-hero-banner {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 60%, #2a5298 100%);
            padding: 7rem 0 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-hero-banner::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(245,166,35,0.08) 0%, transparent 55%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.04) 0%, transparent 45%);
        }
        .page-hero-banner .pattern {
            position: absolute; inset: 0; opacity: 0.03;
            background-image: repeating-linear-gradient(
                45deg, white 0px, white 1px, transparent 1px, transparent 40px
            );
        }
        .page-hero-banner-inner {
            position: relative; z-index: 2;
        }
        .page-tag {
            display: inline-block;
            background: rgba(245,166,35,0.18);
            border: 1px solid rgba(245,166,35,0.4);
            color: var(--accent);
            font-size: 0.72rem; font-weight: 700;
            letter-spacing: 2.5px; text-transform: uppercase;
            padding: 0.4rem 1.3rem; border-radius: 20px;
            margin-bottom: 1.2rem;
        }
        .page-hero-banner h1 {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 900; color: white;
            line-height: 1.2; margin-bottom: 1rem;
        }
        .page-hero-banner h1 span { color: var(--accent); }
        .page-hero-banner p {
            font-size: 1rem; color: rgba(255,255,255,0.75);
            max-width: 600px; margin: 0 auto 1.5rem;
            line-height: 1.75;
        }
        .breadcrumb-nav {
            display: flex; align-items: center; justify-content: center;
            gap: 0.5rem; font-size: 0.82rem;
        }
        .breadcrumb-nav a {
            color: rgba(255,255,255,0.55); text-decoration: none;
            transition: color 0.2s;
        }
        .breadcrumb-nav a:hover { color: var(--accent); }
        .breadcrumb-nav span { color: rgba(255,255,255,0.35); }
        .breadcrumb-nav strong { color: var(--accent); font-weight: 600; }

        /* ===== SECTION UTILITIES ===== */
        .section-body {
            padding: 5rem 0;
            min-height: calc(100vh - 280px);
        }
        .section-body.light { background: var(--light); }
        .container {
            max-width: 1180px; margin: 0 auto;
            padding: 0 1.5rem; width: 100%;
        }
        .section-header {
            text-align: center; margin-bottom: 4rem;
        }
        .section-label {
            display: inline-block;
            background: rgba(26,58,108,0.07);
            color: var(--primary);
            font-size: 0.72rem; font-weight: 700;
            letter-spacing: 2.5px; text-transform: uppercase;
            padding: 0.4rem 1.3rem; border-radius: 20px;
            margin-bottom: 1rem;
            border: 1px solid rgba(26,58,108,0.13);
        }
        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800; color: var(--dark);
            line-height: 1.25; margin-bottom: 1rem;
        }
        .section-title span { color: var(--primary); }
        .section-divider {
            width: 60px; height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px; margin: 1rem auto;
        }
        .section-desc {
            max-width: 660px; margin: 0 auto;
            color: var(--text); line-height: 1.9; font-size: 0.95rem;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--secondary);
            color: rgba(255,255,255,0.75);
            padding: 4rem 0 1.5rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1fr;
            gap: 2.5rem; margin-bottom: 3rem;
        }
        .footer-brand img {
            height: 60px; width: 60px; object-fit: contain;
            border-radius: 50%; background: white;
            padding: 3px; margin-bottom: 1.2rem;
            box-shadow: 0 0 0 3px rgba(245,166,35,0.4);
        }
        .footer-brand .name {
            font-weight: 800; color: white;
            font-size: 1.05rem; display: block; margin-bottom: 0.6rem;
        }
        .footer-brand p { font-size: 0.87rem; line-height: 1.8; }
        .footer-brand .motto {
            display: inline-block; margin-top: 1rem;
            font-size: 0.82rem; font-style: italic;
            color: var(--accent); border-left: 2px solid var(--accent);
            padding-left: 0.8rem;
        }
        footer h4 {
            color: white; font-size: 0.95rem; font-weight: 700;
            margin-bottom: 1.4rem; padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .footer-links { list-style: none; display: flex; flex-direction: column; gap: 0.65rem; }
        .footer-links a {
            color: rgba(255,255,255,0.65); text-decoration: none;
            font-size: 0.87rem; transition: color 0.2s;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .footer-links a:hover { color: var(--accent); }
        .footer-links a i { font-size: 0.72rem; color: var(--accent); }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.5rem;
            display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 0.8rem;
        }
        .footer-bottom p { font-size: 0.82rem; }
        .footer-bottom a { color: var(--accent); text-decoration: none; }
        .social-links {
            display: flex; gap: 0.7rem;
        }
        .social-link {
            width: 38px; height: 38px; border-radius: 9px;
            background: rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.7); font-size: 0.9rem;
            text-decoration: none; transition: all 0.25s ease;
        }
        .social-link:hover {
            background: var(--accent); color: var(--dark);
            transform: translateY(-3px);
        }

        /* Toast */
        .toast-msg {
            position: fixed; bottom: 2rem; right: 2rem;
            background: var(--green); color: white;
            padding: 1rem 1.5rem; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 9999; font-size: 0.9rem; font-weight: 500;
            display: none; align-items: center; gap: 0.7rem;
        }
        .toast-msg.show { display: flex; animation: slideIn 0.3s ease; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }

        /* Fade-in animation */
        .fade-up {
            opacity: 0; transform: translateY(22px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .fade-up.visible { opacity: 1; transform: translateY(0); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 992px) {
            .nav-links { display: none; }
            .hamburger { display: block; }
        }
        @media (max-width: 768px) {
            .section-body { padding: 3.5rem 0; }
        }
        @media (max-width: 576px) {
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- ===== NAVBAR ===== -->
@php
    $currentRoute = Route::currentRouteName();
@endphp
<nav class="navbar" id="mainNav">
    <a href="{{ route('landing') }}" class="navbar-brand">
        <img src="{{ asset('img/logo.png') }}" alt="Logo Interandino">
        <div class="brand-text">
            <span class="name">Interandino Boliviano</span>
            <span class="sub">Contribuir · Mejorar · Desarrollar</span>
        </div>
    </a>

    <ul class="nav-links">
        <li><a href="{{ route('landing') }}" class="{{ $currentRoute === 'landing' ? 'nav-active' : '' }}">
            <i class="fas fa-home" style="font-size:0.78rem;margin-right:3px"></i> Inicio
        </a></li>
        <li><a href="{{ route('landing.nosotros') }}" class="{{ $currentRoute === 'landing.nosotros' ? 'nav-active' : '' }}">
            Nosotros
        </a></li>
        <li><a href="{{ route('landing.niveles') }}" class="{{ $currentRoute === 'landing.niveles' ? 'nav-active' : '' }}">
            Niveles
        </a></li>
        <li><a href="{{ route('landing.historia') }}" class="{{ $currentRoute === 'landing.historia' ? 'nav-active' : '' }}">
            Historia
        </a></li>
        <li><a href="{{ route('landing.contacto') }}" class="{{ $currentRoute === 'landing.contacto' ? 'nav-active' : '' }}">
            Contacto
        </a></li>
        @auth
            <li><a href="{{ route('home') }}" class="btn-nav">
                <i class="fas fa-th-large" style="font-size:0.78rem"></i> Panel
            </a></li>
        @else
            <li><a href="{{ route('login') }}" class="btn-nav">
                <i class="fas fa-sign-in-alt" style="font-size:0.78rem"></i> Iniciar Sesión
            </a></li>
        @endauth
    </ul>

    <button class="hamburger" id="hamburgerBtn" aria-label="Menú">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobileMenu">
    <ul>
        <li><a href="{{ route('landing') }}" class="{{ $currentRoute === 'landing' ? 'nav-active' : '' }}"><i class="fas fa-home" style="margin-right:0.4rem"></i> Inicio</a></li>
        <li><a href="{{ route('landing.nosotros') }}" class="{{ $currentRoute === 'landing.nosotros' ? 'nav-active' : '' }}"><i class="fas fa-users" style="margin-right:0.4rem"></i> Nosotros</a></li>
        <li><a href="{{ route('landing.niveles') }}" class="{{ $currentRoute === 'landing.niveles' ? 'nav-active' : '' }}"><i class="fas fa-graduation-cap" style="margin-right:0.4rem"></i> Niveles</a></li>
        <li><a href="{{ route('landing.historia') }}" class="{{ $currentRoute === 'landing.historia' ? 'nav-active' : '' }}"><i class="fas fa-history" style="margin-right:0.4rem"></i> Historia</a></li>
        <li><a href="{{ route('landing.contacto') }}" class="{{ $currentRoute === 'landing.contacto' ? 'nav-active' : '' }}"><i class="fas fa-envelope" style="margin-right:0.4rem"></i> Contacto</a></li>
        @auth
            <li><a href="{{ route('home') }}" class="btn-nav"><i class="fas fa-th-large"></i> Panel de Control</a></li>
        @else
            <li><a href="{{ route('login') }}" class="btn-nav"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
        @endauth
    </ul>
</div>

<!-- ===== PAGE CONTENT ===== -->
@yield('content')

<!-- ===== FOOTER ===== -->
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Interandino">
                <span class="name">Interandino Boliviano</span>
                <p>Unidad Educativa Privada comprometida con la formación integral de estudiantes en El Alto, La Paz, Bolivia desde el año 2005.</p>
                <div class="motto">"Contribuir, mejorar y desarrollar"</div>
            </div>

            <div>
                <h4>Navegación</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('landing') }}"><i class="fas fa-chevron-right"></i> Inicio</a></li>
                    <li><a href="{{ route('landing.nosotros') }}"><i class="fas fa-chevron-right"></i> Nosotros</a></li>
                    <li><a href="{{ route('landing.niveles') }}"><i class="fas fa-chevron-right"></i> Niveles Educativos</a></li>
                    <li><a href="{{ route('landing.historia') }}"><i class="fas fa-chevron-right"></i> Historia</a></li>
                    <li><a href="{{ route('landing.contacto') }}"><i class="fas fa-chevron-right"></i> Contacto</a></li>
                    @auth
                        <li><a href="{{ route('home') }}"><i class="fas fa-chevron-right"></i> Panel de Control</a></li>
                    @else
                        <li><a href="{{ route('login') }}"><i class="fas fa-chevron-right"></i> Iniciar Sesión</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h4>Niveles</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.niveles') }}"><i class="fas fa-child"></i> Educación Inicial</a></li>
                    <li><a href="{{ route('landing.niveles') }}"><i class="fas fa-book-open"></i> Educación Primaria</a></li>
                    <li><a href="{{ route('landing.niveles') }}"><i class="fas fa-graduation-cap"></i> Educación Secundaria</a></li>
                </ul>
                <h4 style="margin-top:1.8rem">Valores</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.nosotros') }}"><i class="fas fa-hands-helping"></i> Inclusión</a></li>
                    <li><a href="{{ route('landing.nosotros') }}"><i class="fas fa-star"></i> Excelencia</a></li>
                    <li><a href="{{ route('landing.nosotros') }}"><i class="fas fa-lightbulb"></i> Innovación</a></li>
                </ul>
            </div>

            <div>
                <h4>Institución</h4>
                <ul class="footer-links" style="gap:1rem">
                    <li style="flex-direction:column;align-items:flex-start;gap:0.2rem">
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px">Ubicación</span>
                        <span style="color:rgba(255,255,255,0.75)">El Alto, La Paz — Bolivia</span>
                    </li>
                    <li style="flex-direction:column;align-items:flex-start;gap:0.2rem">
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px">Fundación</span>
                        <span style="color:rgba(255,255,255,0.75)">25 de Mayo de 2005</span>
                    </li>
                    <li style="flex-direction:column;align-items:flex-start;gap:0.2rem">
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px">R.A.</span>
                        <span style="color:rgba(255,255,255,0.75)">No. 311/2006</span>
                    </li>
                </ul>
                <div style="margin-top:1.5rem">
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Unidad Educativa Privada "Interandino Boliviano". Todos los derechos reservados.</p>
            <p>R.A. No. 311/2006 &nbsp;·&nbsp; <a href="{{ route('login') }}">Acceso Docente</a></p>
        </div>
    </div>
</footer>

<!-- Toast global -->
<div class="toast-msg" id="toastMsg">
    <i class="fas fa-check-circle"></i>
    <span id="toastText">¡Operación exitosa!</span>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Navbar scroll
    window.addEventListener('scroll', function () {
        document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
    });

    // Hamburger
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileMenu   = document.getElementById('mobileMenu');
    hamburgerBtn.addEventListener('click', function () {
        this.classList.toggle('open');
        mobileMenu.classList.toggle('open');
    });

    // Fade-in on scroll
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-up').forEach(function (el) { observer.observe(el); });

    // Toast helper
    function showToast(msg) {
        const t = document.getElementById('toastMsg');
        document.getElementById('toastText').textContent = msg;
        t.classList.add('show');
        setTimeout(function () { t.classList.remove('show'); }, 5000);
    }
</script>
@stack('scripts')
</body>
</html>
