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
        <p>{{ landing('niveles.hero_texto') }}</p>
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
            <p class="section-desc">{{ landing('niveles.intro') }}</p>
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
                    <p>{{ landing('niveles.inicial.descripcion') }}</p>
                    <ul class="nivel-features">
                        @foreach(\App\Support\Landing::lista('niveles.inicial.caracteristicas') as $item)
                        <li><i class="fas fa-check-circle"></i> {{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--primary)"></i>
                    {{ landing('niveles.inicial.nota') }}
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
                    <p>{{ landing('niveles.primaria.descripcion') }}</p>
                    <ul class="nivel-features">
                        @foreach(\App\Support\Landing::lista('niveles.primaria.caracteristicas') as $item)
                        <li><i class="fas fa-check-circle"></i> {{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--accent2)"></i>
                    {{ landing('niveles.primaria.nota') }}
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
                    <p>{{ landing('niveles.secundaria.descripcion') }}</p>
                    <ul class="nivel-features">
                        @foreach(\App\Support\Landing::lista('niveles.secundaria.caracteristicas') as $item)
                        <li><i class="fas fa-check-circle"></i> {{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="nivel-card-footer">
                    <i class="fas fa-info-circle" style="color:var(--green)"></i>
                    {{ landing('niveles.secundaria.nota') }}
                </div>
            </div>

        </div>

        <!-- CTA -->
        <div class="cta-block fade-up">
            <h3>{{ landing('niveles.cta_titulo') }}</h3>
            <p>{{ landing('niveles.cta_texto') }}</p>
            <div class="cta-buttons">
                <a href="{{ route('landing.contacto', ['motivo' => 'inscripcion']) }}#contactForm" class="btn-cta btn-cta-primary">
                    <i class="fas fa-user-plus"></i> Solicitar Inscripción
                </a>
                <a href="{{ route('landing.inscripciones') }}" class="btn-cta btn-cta-outline">
                    <i class="fas fa-clipboard-check"></i> Ver Requisitos
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
