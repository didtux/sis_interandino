@extends('landing.layout')
@section('title', 'Inicio')

@push('styles')
<style>
    /* ===== HERO ===== */
    #hero {
        position: relative;
        height: 100vh; min-height: 700px;
        overflow: hidden;
    }
    .carousel, .carousel-inner, .carousel-item { height: 100%; }
    .slide {
        height: 100vh; min-height: 700px;
        display: flex; align-items: center;
        justify-content: center; position: relative; overflow: hidden;
    }
    .slide-1 { background: linear-gradient(135deg, #0a1a3a 0%, #1a3a6c 45%, #2a5298 100%); }
    .slide-2 { background: linear-gradient(135deg, #1a3a6c 0%, #0d2247 50%, #162040 100%); }
    .slide-3 { background: linear-gradient(135deg, #162040 0%, #1e3a5f 50%, #1a3a6c 100%); }
    .slide-4 { background: linear-gradient(135deg, #0a1a3a 0%, #1e3a6c 50%, #162040 100%); }
    .slide::before {
        content: ''; position: absolute; inset: 0;
        background:
            radial-gradient(circle at 15% 50%, rgba(245,166,35,0.1) 0%, transparent 55%),
            radial-gradient(circle at 85% 20%, rgba(255,255,255,0.04) 0%, transparent 45%);
    }
    .slide-pattern {
        position: absolute; inset: 0; opacity: 0.03;
        background-image: repeating-linear-gradient(
            45deg, white 0px, white 1px, transparent 1px, transparent 40px
        );
    }
    .slide-deco {
        position: absolute; border-radius: 50%; opacity: 0.06;
        animation: floatDeco 7s ease-in-out infinite;
    }
    .slide-deco-1 { width: 550px; height: 550px; background: var(--accent); top: -200px; right: -150px; }
    .slide-deco-2 { width: 320px; height: 320px; background: white; bottom: -100px; left: -80px; animation-delay: 3s; }
    @keyframes floatDeco {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%       { transform: translateY(-22px) rotate(6deg); }
    }
    .slide-content {
        position: relative; z-index: 2;
        text-align: center; padding: 2rem;
        max-width: 900px; animation: fadeInUp 0.9s ease;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(35px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .slide-badge {
        display: inline-block;
        background: rgba(245,166,35,0.18);
        border: 1px solid rgba(245,166,35,0.5);
        color: var(--accent); font-size: 0.75rem; font-weight: 600;
        letter-spacing: 2px; text-transform: uppercase;
        padding: 0.4rem 1.3rem; border-radius: 20px; margin-bottom: 1.6rem;
    }
    .slide-logo {
        width: 120px; height: 120px; object-fit: contain;
        border-radius: 50%; background: rgba(255,255,255,0.1);
        padding: 8px; border: 3px solid rgba(245,166,35,0.7);
        margin-bottom: 2rem;
        box-shadow: 0 8px 40px rgba(0,0,0,0.35), 0 0 0 8px rgba(245,166,35,0.08);
    }
    .slide-title {
        font-size: clamp(2.2rem, 5.5vw, 4rem);
        font-weight: 900; color: white;
        line-height: 1.15; margin-bottom: 1.2rem;
        text-shadow: 0 2px 25px rgba(0,0,0,0.4);
    }
    .slide-title span { color: var(--accent); }
    .slide-subtitle {
        font-size: clamp(1rem, 2vw, 1.2rem);
        color: rgba(255,255,255,0.82);
        margin-bottom: 2.5rem; line-height: 1.8; font-weight: 300;
    }
    .slide-actions {
        display: flex; gap: 1rem;
        justify-content: center; flex-wrap: wrap;
    }
    .btn-hero {
        padding: 0.9rem 2.4rem; border-radius: 35px;
        font-size: 0.95rem; font-weight: 600;
        text-decoration: none; transition: all 0.3s ease;
        letter-spacing: 0.5px; display: inline-flex;
        align-items: center; gap: 0.6rem;
    }
    .btn-hero-primary {
        background: var(--accent); color: var(--dark);
        box-shadow: 0 6px 22px rgba(245,166,35,0.45);
    }
    .btn-hero-primary:hover {
        background: var(--accent2); transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(245,166,35,0.55);
        color: var(--dark); text-decoration: none;
    }
    .btn-hero-outline {
        background: transparent; color: white;
        border: 2px solid rgba(255,255,255,0.6);
    }
    .btn-hero-outline:hover {
        background: rgba(255,255,255,0.12); border-color: white;
        transform: translateY(-3px); color: white; text-decoration: none;
    }
    .carousel-control-prev, .carousel-control-next { width: 5%; opacity: 0.65; }
    .carousel-control-prev:hover, .carousel-control-next:hover { opacity: 1; }
    .carousel-indicators li {
        width: 10px; height: 10px; border-radius: 50%;
        background: rgba(255,255,255,0.4); border: none;
        transition: all 0.3s ease;
    }
    .carousel-indicators li.active {
        background: var(--accent); width: 28px; border-radius: 5px;
    }
    .scroll-indicator {
        position: absolute; bottom: 35px; left: 50%;
        transform: translateX(-50%); z-index: 10;
        animation: bounceDown 2s infinite; cursor: pointer;
    }
    .scroll-indicator span {
        display: block; width: 28px; height: 28px;
        border-right: 2px solid rgba(255,255,255,0.65);
        border-bottom: 2px solid rgba(255,255,255,0.65);
        transform: rotate(45deg); margin: -10px auto;
    }
    @keyframes bounceDown {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50%       { transform: translateX(-50%) translateY(10px); }
    }

    /* ===== STATS BAR ===== */
    .stats-bar {
        background: var(--primary); padding: 1.8rem 0;
        position: relative; overflow: hidden;
    }
    .stats-bar::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent, rgba(245,166,35,0.05), transparent);
    }
    .stats-bar .container {
        display: flex; justify-content: space-around;
        flex-wrap: wrap; gap: 1rem;
    }
    .stat-item {
        text-align: center; color: white;
        padding: 0.5rem 2rem; position: relative;
    }
    .stat-item:not(:last-child)::after {
        content: ''; position: absolute; right: 0; top: 50%;
        transform: translateY(-50%);
        height: 40px; width: 1px; background: rgba(255,255,255,0.15);
    }
    .stat-number {
        font-size: 2.2rem; font-weight: 900;
        color: var(--accent); line-height: 1;
    }
    .stat-label {
        font-size: 0.78rem; opacity: 0.82;
        letter-spacing: 0.8px; margin-top: 0.3rem;
        text-transform: uppercase; font-weight: 500;
    }

    /* ===== QUICK LINKS ===== */
    .quick-links {
        padding: 5rem 0; background: var(--light);
    }
    .quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    .quick-card {
        background: white; border-radius: 18px;
        padding: 2.5rem 1.8rem; text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        text-decoration: none;
        transition: all 0.3s ease;
        display: block;
        border-bottom: 4px solid transparent;
    }
    .quick-card:nth-child(1) { border-bottom-color: var(--primary); }
    .quick-card:nth-child(2) { border-bottom-color: var(--accent); }
    .quick-card:nth-child(3) { border-bottom-color: var(--green); }
    .quick-card:nth-child(4) { border-bottom-color: #8b5cf6; }
    .quick-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 45px rgba(0,0,0,0.13);
        text-decoration: none;
    }
    .quick-icon {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.4rem; font-size: 1.7rem;
    }
    .quick-card:nth-child(1) .quick-icon { background: rgba(26,58,108,0.1); color: var(--primary); }
    .quick-card:nth-child(2) .quick-icon { background: rgba(245,166,35,0.12); color: var(--accent2); }
    .quick-card:nth-child(3) .quick-icon { background: rgba(42,125,79,0.1); color: var(--green); }
    .quick-card:nth-child(4) .quick-icon { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .quick-card h3 {
        font-size: 1.05rem; font-weight: 700;
        color: var(--dark); margin-bottom: 0.5rem;
    }
    .quick-card p { font-size: 0.83rem; color: var(--text); line-height: 1.6; }
    .quick-card .arrow {
        display: inline-flex; align-items: center; gap: 0.4rem;
        font-size: 0.8rem; font-weight: 600; margin-top: 1.2rem;
        opacity: 0.6; transition: all 0.25s ease;
    }
    .quick-card:nth-child(1) .arrow { color: var(--primary); }
    .quick-card:nth-child(2) .arrow { color: var(--accent2); }
    .quick-card:nth-child(3) .arrow { color: var(--green); }
    .quick-card:nth-child(4) .arrow { color: #8b5cf6; }
    .quick-card:hover .arrow { opacity: 1; gap: 0.7rem; }

    @media (max-width: 992px) {
        .quick-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 576px) {
        .quick-grid { grid-template-columns: 1fr; }
        .slide-actions { flex-direction: column; align-items: center; }
        .stats-bar .container { flex-direction: column; align-items: center; }
        .stat-item::after { display: none; }
    }
</style>
@endpush

@section('content')

<!-- ===== HERO CAROUSEL ===== -->
<section id="hero">
    <div id="heroCarousel" class="carousel slide" data-ride="carousel" data-interval="6000" style="height:100%">

        <ol class="carousel-indicators">
            <li data-target="#heroCarousel" data-slide-to="0" class="active"></li>
            <li data-target="#heroCarousel" data-slide-to="1"></li>
            <li data-target="#heroCarousel" data-slide-to="2"></li>
            <li data-target="#heroCarousel" data-slide-to="3"></li>
        </ol>

        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="slide slide-1">
                    <div class="slide-pattern"></div>
                    <div class="slide-deco slide-deco-1"></div>
                    <div class="slide-deco slide-deco-2"></div>
                    <div class="slide-content">
                        <div class="slide-badge">Desde 2005 · El Alto, La Paz · Bolivia</div>
                        <img src="{{ asset('img/logo.png') }}" alt="Logo" class="slide-logo">
                        <h1 class="slide-title">
                            Unidad Educativa Privada<br><span>"Interandino Boliviano"</span>
                        </h1>
                        <p class="slide-subtitle">
                            Formando estudiantes autónomos, reflexivos, críticos y productivos<br>
                            con capacidad creativa e intelectual para el vivir bien.
                        </p>
                        <div class="slide-actions">
                            <a href="{{ route('landing.nosotros') }}" class="btn-hero btn-hero-primary">
                                <i class="fas fa-info-circle"></i> Conócenos
                            </a>
                            <a href="{{ route('landing.contacto') }}" class="btn-hero btn-hero-outline">
                                <i class="fas fa-envelope"></i> Contáctanos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="slide slide-2">
                    <div class="slide-pattern"></div>
                    <div class="slide-deco slide-deco-1"></div>
                    <div class="slide-content">
                        <div class="slide-badge">Nuestra Visión Institucional</div>
                        <div style="font-size:4rem;margin-bottom:1.2rem;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.3))">🎯</div>
                        <h1 class="slide-title">
                            Educación <span>Inclusiva</span><br>y Comunitaria
                        </h1>
                        <p class="slide-subtitle">
                            Institución pluralista con valores democráticos, intercultural y plurilingüe,<br>
                            en armonía con la madre naturaleza y la humanidad.
                        </p>
                        <div class="slide-actions">
                            <a href="{{ route('landing.nosotros') }}" class="btn-hero btn-hero-primary">
                                <i class="fas fa-eye"></i> Misión y Visión
                            </a>
                            <a href="{{ route('landing.historia') }}" class="btn-hero btn-hero-outline">
                                <i class="fas fa-history"></i> Nuestra Historia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="carousel-item">
                <div class="slide slide-3">
                    <div class="slide-pattern"></div>
                    <div class="slide-deco slide-deco-2"></div>
                    <div class="slide-content">
                        <div class="slide-badge">Oferta Educativa Completa</div>
                        <div style="font-size:4rem;margin-bottom:1.2rem;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.3))">📚</div>
                        <h1 class="slide-title">
                            <span>Tres Niveles</span><br>de Formación
                        </h1>
                        <p class="slide-subtitle">
                            Educación Inicial en Familia Comunitaria<br>
                            Primaria Vocacional · Secundaria Comunitaria Productiva
                        </p>
                        <div class="slide-actions">
                            <a href="{{ route('landing.niveles') }}" class="btn-hero btn-hero-primary">
                                <i class="fas fa-graduation-cap"></i> Ver Niveles
                            </a>
                            <a href="{{ route('landing.contacto') }}" class="btn-hero btn-hero-outline">
                                <i class="fas fa-user-plus"></i> Inscripciones
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 4 -->
            <div class="carousel-item">
                <div class="slide slide-4">
                    <div class="slide-pattern"></div>
                    <div class="slide-deco slide-deco-1"></div>
                    <div class="slide-content">
                        <div class="slide-badge">¡Celebrando 20 Años!</div>
                        <div style="font-size:4rem;margin-bottom:1.2rem;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.3))">🏆</div>
                        <h1 class="slide-title">
                            <span>20 Años</span> de<br>Excelencia Educativa
                        </h1>
                        <p class="slide-subtitle">
                            Desde el 25 de mayo de 2005 al servicio de El Alto,<br>
                            construyendo el futuro de Bolivia con dedicación y vocación.
                        </p>
                        <div class="slide-actions">
                            <a href="{{ route('landing.historia') }}" class="btn-hero btn-hero-primary">
                                <i class="fas fa-medal"></i> Nuestra Trayectoria
                            </a>
                            <a href="{{ route('landing.nosotros') }}" class="btn-hero btn-hero-outline">
                                <i class="fas fa-users"></i> Nuestros Valores
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a class="carousel-control-prev" href="#heroCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </a>
        <a class="carousel-control-next" href="#heroCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon"></span>
        </a>
    </div>

    <div class="scroll-indicator" id="scrollDown">
        <span></span><span></span>
    </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
    <div class="container">
        <div class="stat-item">
            <div class="stat-number">20+</div>
            <div class="stat-label">Años de Excelencia</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">3</div>
            <div class="stat-label">Niveles Educativos</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">2005</div>
            <div class="stat-label">Año de Fundación</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">R.A.</div>
            <div class="stat-label">No. 311/2006</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">3</div>
            <div class="stat-label">Fundadores</div>
        </div>
    </div>
</div>

<!-- QUICK LINKS -->
<div class="quick-links">
    <div class="container">
        <div class="section-header" style="margin-bottom:3rem">
            <div class="section-label">Explora la Institución</div>
            <h2 class="section-title">¿Qué deseas <span>conocer?</span></h2>
            <div class="section-divider"></div>
        </div>
        <div class="quick-grid">
            <a href="{{ route('landing.nosotros') }}" class="quick-card fade-up">
                <div class="quick-icon"><i class="fas fa-eye"></i></div>
                <h3>Nosotros</h3>
                <p>Misión, visión y valores institucionales que nos definen.</p>
                <div class="arrow">Ver más <i class="fas fa-arrow-right"></i></div>
            </a>
            <a href="{{ route('landing.niveles') }}" class="quick-card fade-up">
                <div class="quick-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Niveles Educativos</h3>
                <p>Inicial, Primaria y Secundaria con formación integral.</p>
                <div class="arrow">Ver más <i class="fas fa-arrow-right"></i></div>
            </a>
            <a href="{{ route('landing.historia') }}" class="quick-card fade-up">
                <div class="quick-icon"><i class="fas fa-history"></i></div>
                <h3>Nuestra Historia</h3>
                <p>20 años de trayectoria y compromiso con El Alto.</p>
                <div class="arrow">Ver más <i class="fas fa-arrow-right"></i></div>
            </a>
            <a href="{{ route('landing.contacto') }}" class="quick-card fade-up">
                <div class="quick-icon"><i class="fas fa-envelope"></i></div>
                <h3>Contacto</h3>
                <p>Inscripciones e información sobre nuestros servicios.</p>
                <div class="arrow">Ver más <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $('#heroCarousel').carousel({ interval: 6000, ride: 'carousel' });
    document.getElementById('scrollDown').addEventListener('click', function () {
        document.querySelector('.stats-bar').scrollIntoView({ behavior: 'smooth' });
    });
</script>
@endpush
