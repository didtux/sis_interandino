@extends('landing.layout')
@section('title', 'Niveles Educativos')

@push('styles')
<style>
    .nivel-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem; margin-bottom: 3.5rem;
    }
    .nivel-card {
        background: white; border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 6px 30px rgba(0,0,0,0.08);
        transition: all 0.35s ease;
        display: flex; flex-direction: column;
    }
    .nivel-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 22px 55px rgba(0,0,0,0.15);
    }
    .nivel-card-header {
        padding: 2.8rem 2rem 2rem;
        text-align: center; position: relative; overflow: hidden;
    }
    .nivel-card:nth-child(1) .nivel-card-header { background: linear-gradient(135deg, var(--primary), #2a5298); }
    .nivel-card:nth-child(2) .nivel-card-header { background: linear-gradient(135deg, #b45309, var(--accent2)); }
    .nivel-card:nth-child(3) .nivel-card-header { background: linear-gradient(135deg, #166534, var(--green)); }
    .nivel-card-header::before {
        content: ''; position: absolute; top: -30px; right: -30px;
        width: 120px; height: 120px; border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }
    .nivel-card-header::after {
        content: ''; position: absolute; bottom: -40px; left: -20px;
        width: 100px; height: 100px; border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .nivel-icon {
        width: 84px; height: 84px; border-radius: 50%;
        background: rgba(255,255,255,0.18);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.4rem; font-size: 2.2rem; color: white;
        border: 3px solid rgba(255,255,255,0.3);
        position: relative; z-index: 1;
    }
    .nivel-card-header h3 {
        font-size: 1.3rem; font-weight: 800; color: white;
        margin-bottom: 0.3rem; position: relative; z-index: 1;
    }
    .nivel-card-header .sub {
        font-size: 0.82rem; color: rgba(255,255,255,0.78);
        font-weight: 400; position: relative; z-index: 1;
    }
    .nivel-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        color: white; font-size: 0.7rem; font-weight: 600;
        letter-spacing: 1px; text-transform: uppercase;
        padding: 0.25rem 0.9rem; border-radius: 20px;
        margin-bottom: 1.2rem;
        position: relative; z-index: 1;
    }
    .nivel-card-body {
        padding: 2rem 2rem 1.5rem; flex: 1;
    }
    .nivel-card-body p {
        font-size: 0.92rem; line-height: 1.8;
        color: var(--text); margin-bottom: 1.5rem;
    }
    .nivel-features {
        list-style: none; display: flex; flex-direction: column; gap: 0.7rem;
    }
    .nivel-features li {
        display: flex; align-items: flex-start; gap: 0.8rem;
        font-size: 0.88rem; color: var(--dark); line-height: 1.5;
    }
    .nivel-features li i {
        margin-top: 2px; font-size: 0.82rem; flex-shrink: 0;
    }
    .nivel-card:nth-child(1) .nivel-features li i { color: var(--primary); }
    .nivel-card:nth-child(2) .nivel-features li i { color: var(--accent2); }
    .nivel-card:nth-child(3) .nivel-features li i { color: var(--green); }
    .nivel-card-footer {
        padding: 1.2rem 2rem;
        border-top: 1px solid var(--border);
        font-size: 0.8rem; color: var(--text);
        display: flex; align-items: center; gap: 0.6rem;
        background: var(--light);
    }

    /* CTA block */
    .cta-block {
        background: linear-gradient(135deg, var(--primary), #2a5298);
        border-radius: 22px; padding: 3.5rem;
        text-align: center; color: white;
        position: relative; overflow: hidden;
    }
    .cta-block::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(245,166,35,0.12) 0%, transparent 55%);
    }
    .cta-block h3 {
        font-size: 1.8rem; font-weight: 800; margin-bottom: 0.7rem;
        position: relative; z-index: 1;
    }
    .cta-block h3 span { color: var(--accent); }
    .cta-block p {
        color: rgba(255,255,255,0.8); font-size: 1rem;
        margin-bottom: 2rem; line-height: 1.7;
        position: relative; z-index: 1; max-width: 560px; margin-left: auto; margin-right: auto;
    }
    .cta-buttons {
        display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
        position: relative; z-index: 1;
    }
    .btn-cta {
        padding: 0.9rem 2.4rem; border-radius: 35px;
        font-size: 0.95rem; font-weight: 700;
        text-decoration: none; transition: all 0.3s ease;
        letter-spacing: 0.5px; display: inline-flex;
        align-items: center; gap: 0.6rem;
    }
    .btn-cta-primary {
        background: var(--accent); color: var(--dark);
        box-shadow: 0 6px 22px rgba(245,166,35,0.45);
    }
    .btn-cta-primary:hover {
        background: var(--accent2); transform: translateY(-3px);
        color: var(--dark); text-decoration: none;
        box-shadow: 0 12px 30px rgba(245,166,35,0.55);
    }
    .btn-cta-outline {
        background: transparent; color: white;
        border: 2px solid rgba(255,255,255,0.6);
    }
    .btn-cta-outline:hover {
        background: rgba(255,255,255,0.12); border-color: white;
        transform: translateY(-3px); color: white; text-decoration: none;
    }

    @media (max-width: 992px) {
        .nivel-grid { grid-template-columns: 1fr; max-width: 540px; margin: 0 auto 3.5rem; }
    }
    @media (max-width: 576px) {
        .cta-block { padding: 2.5rem 1.5rem; }
        .cta-block h3 { font-size: 1.4rem; }
    }
</style>
@endpush

@section('content')

<!-- PAGE HERO BANNER -->
<div class="page-hero-banner">
    <div class="pattern"></div>
    <div class="container page-hero-banner-inner">
        <div class="page-tag">Oferta Académica</div>
        <h1>Niveles <span>Educativos</span></h1>
        <p>Formación integral desde la infancia hasta el bachillerato en un ambiente comunitario y productivo.</p>
        <div class="breadcrumb-nav">
            <a href="{{ route('landing') }}"><i class="fas fa-home" style="font-size:0.75rem"></i> Inicio</a>
            <span>/</span>
            <strong>Niveles Educativos</strong>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="section-body light">
    <div class="container">

        <div class="section-header">
            <div class="section-label">Nuestra Propuesta Educativa</div>
            <h2 class="section-title">Tres Niveles de <span>Formación</span></h2>
            <div class="section-divider"></div>
            <p class="section-desc">
                Acompañamos cada etapa del desarrollo del estudiante con metodologías
                actualizadas, docentes comprometidos y un enfoque sociocomunitario productivo.
            </p>
        </div>

        <div class="nivel-grid">

            <!-- Inicial -->
            <div class="nivel-card fade-up">
                <div class="nivel-card-header">
                    <div class="nivel-badge">Nivel 1</div>
                    <div class="nivel-icon"><i class="fas fa-child"></i></div>
                    <h3>Educación Inicial</h3>
                    <span class="sub">En Familia Comunitaria</span>
                </div>
                <div class="nivel-card-body">
                    <p>
                        Sembramos las bases del aprendizaje en los primeros años de vida, trabajando
                        de la mano con la familia y la comunidad para estimular el desarrollo integral
                        del niño en un ambiente seguro, cálido y creativo.
                    </p>
                    <ul class="nivel-features">
                        <li><i class="fas fa-check-circle"></i> Estimulación temprana y desarrollo psicomotriz</li>
                        <li><i class="fas fa-check-circle"></i> Desarrollo del lenguaje oral y expresivo</li>
                        <li><i class="fas fa-check-circle"></i> Socialización y convivencia comunitaria</li>
                        <li><i class="fas fa-check-circle"></i> Arte, música, juego y expresión creativa</li>
                        <li><i class="fas fa-check-circle"></i> Aprestamiento para educación primaria</li>
                        <li><i class="fas fa-check-circle"></i> Formación en valores y hábitos saludables</li>
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--primary)"></i>
                    Niños de 3 a 5 años · Ambiente lúdico y familiar
                </div>
            </div>

            <!-- Primaria -->
            <div class="nivel-card fade-up">
                <div class="nivel-card-header">
                    <div class="nivel-badge">Nivel 2</div>
                    <div class="nivel-icon"><i class="fas fa-book-open"></i></div>
                    <h3>Educación Primaria</h3>
                    <span class="sub">Comunitaria Vocacional</span>
                </div>
                <div class="nivel-card-body">
                    <p>
                        Desarrollamos habilidades y conocimientos fundamentales integrando saberes
                        culturales, científicos y vocacionales, construyendo la base académica sólida
                        del estudiante de forma significativa y contextualizada.
                    </p>
                    <ul class="nivel-features">
                        <li><i class="fas fa-check-circle"></i> Matemáticas, Comunicación y Lenguaje</li>
                        <li><i class="fas fa-check-circle"></i> Ciencias Naturales, Sociales y Cosmos</li>
                        <li><i class="fas fa-check-circle"></i> Educación física y artística integral</li>
                        <li><i class="fas fa-check-circle"></i> Tecnología y emprendimiento básico</li>
                        <li><i class="fas fa-check-circle"></i> Valores sociocomunitarios y ciudadanía</li>
                        <li><i class="fas fa-check-circle"></i> Inglés y lengua originaria</li>
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--accent2)"></i>
                    De 1° a 6° de primaria · 6 a 12 años
                </div>
            </div>

            <!-- Secundaria -->
            <div class="nivel-card fade-up">
                <div class="nivel-card-header">
                    <div class="nivel-badge">Nivel 3</div>
                    <div class="nivel-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Educación Secundaria</h3>
                    <span class="sub">Comunitaria Productiva</span>
                </div>
                <div class="nivel-card-body">
                    <p>
                        Preparamos jóvenes para los retos del futuro con una formación productiva,
                        científica y humanística, desarrollando el pensamiento crítico y la capacidad
                        de contribuir activamente a su comunidad y al país.
                    </p>
                    <ul class="nivel-features">
                        <li><i class="fas fa-check-circle"></i> Ciencias exactas y experimentales</li>
                        <li><i class="fas fa-check-circle"></i> Humanidades y Ciencias Sociales</li>
                        <li><i class="fas fa-check-circle"></i> Técnica tecnológica productiva</li>
                        <li><i class="fas fa-check-circle"></i> Proyecto sociocomunitario productivo</li>
                        <li><i class="fas fa-check-circle"></i> Orientación hacia la educación superior</li>
                        <li><i class="fas fa-check-circle"></i> Bachillerato técnico-humanístico</li>
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--green)"></i>
                    De 1° a 6° de secundaria · 12 a 18 años
                </div>
            </div>

        </div>

        <!-- CTA -->
        <div class="cta-block fade-up">
            <h3>¿Interesado en inscribir a tu hijo/a?</h3>
            <p>
                Visítanos o escríbenos para recibir información detallada sobre el proceso de
                inscripción, calendario escolar y requisitos para cada nivel educativo.
            </p>
            <div class="cta-buttons">
                <a href="{{ route('landing.contacto') }}" class="btn-cta btn-cta-primary">
                    <i class="fas fa-user-plus"></i> Solicitar Inscripción
                </a>
                <a href="{{ route('landing.nosotros') }}" class="btn-cta btn-cta-outline">
                    <i class="fas fa-info-circle"></i> Conocer Más
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
