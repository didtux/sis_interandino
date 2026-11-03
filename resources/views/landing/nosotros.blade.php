@extends('landing.layout')
@section('title', 'Nosotros')

@push('styles')
<style>
    .vm-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 2rem; margin-bottom: 3.5rem;
    }
    .vm-card {
        background: white; border-radius: 20px;
        padding: 2.8rem;
        box-shadow: 0 6px 30px rgba(0,0,0,0.08);
        position: relative; overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-top: 5px solid var(--primary);
    }
    .vm-card.accent { border-top-color: var(--accent); }
    .vm-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 45px rgba(0,0,0,0.14);
    }
    .vm-card-bg {
        position: absolute; bottom: -30px; right: -30px;
        width: 140px; height: 140px; border-radius: 50%;
        opacity: 0.04; background: var(--primary);
    }
    .vm-card.accent .vm-card-bg { background: var(--accent); }
    .vm-icon {
        width: 64px; height: 64px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 1.5rem; font-size: 1.6rem;
        background: rgba(26,58,108,0.08); color: var(--primary);
    }
    .vm-card.accent .vm-icon {
        background: rgba(245,166,35,0.12); color: var(--accent2);
    }
    .vm-card h3 {
        font-size: 1.35rem; font-weight: 800;
        color: var(--dark); margin-bottom: 1.1rem;
    }
    .vm-card p {
        font-size: 0.93rem; line-height: 1.85;
        color: var(--text); margin-bottom: 0.8rem;
    }

    /* Values */
    .values-title {
        text-align: center; margin-bottom: 2rem;
    }
    .values-grid {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem; margin-bottom: 3.5rem;
    }
    .value-card {
        background: white; border-radius: 16px;
        padding: 2rem 1.5rem; text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        transition: all 0.3s ease;
        border-bottom: 4px solid transparent;
    }
    .value-card:nth-child(1) { border-bottom-color: var(--primary); }
    .value-card:nth-child(2) { border-bottom-color: var(--accent); }
    .value-card:nth-child(3) { border-bottom-color: var(--green); }
    .value-card:nth-child(4) { border-bottom-color: #8b5cf6; }
    .value-card:hover { transform: translateY(-6px); box-shadow: 0 14px 40px rgba(0,0,0,0.12); }
    .value-icon {
        width: 64px; height: 64px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.2rem; font-size: 1.5rem;
    }
    .value-card:nth-child(1) .value-icon { background: rgba(26,58,108,0.1); color: var(--primary); }
    .value-card:nth-child(2) .value-icon { background: rgba(245,166,35,0.12); color: var(--accent2); }
    .value-card:nth-child(3) .value-icon { background: rgba(42,125,79,0.1); color: var(--green); }
    .value-card:nth-child(4) .value-icon { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .value-card h4 { font-size: 1rem; font-weight: 700; color: var(--dark); margin-bottom: 0.6rem; }
    .value-card p { font-size: 0.83rem; color: var(--text); line-height: 1.6; }

    /* Info strip */
    .info-strip {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 0; background: var(--primary);
        border-radius: 16px; overflow: hidden;
    }
    .info-strip-item {
        display: flex; align-items: center;
        gap: 1rem; padding: 1.8rem;
        border-right: 1px solid rgba(255,255,255,0.1);
        transition: background 0.25s ease;
    }
    .info-strip-item:last-child { border-right: none; }
    .info-strip-item:hover { background: rgba(255,255,255,0.05); }
    .info-strip-icon {
        width: 44px; height: 44px; border-radius: 10px;
        background: rgba(255,255,255,0.12);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .info-strip-icon i { color: var(--accent); font-size: 1.1rem; }
    .info-strip-text strong {
        display: block; font-size: 0.7rem; color: rgba(255,255,255,0.55);
        font-weight: 600; text-transform: uppercase;
        letter-spacing: 0.7px; margin-bottom: 0.25rem;
    }
    .info-strip-text span { font-size: 0.88rem; color: white; font-weight: 600; }

    @media (max-width: 992px) {
        .vm-grid { grid-template-columns: 1fr; }
        .values-grid { grid-template-columns: 1fr 1fr; }
        .info-strip { grid-template-columns: 1fr 1fr; }
        .info-strip-item:nth-child(2) { border-right: none; }
        .info-strip-item:nth-child(3) { border-right: 1px solid rgba(255,255,255,0.1); border-top: 1px solid rgba(255,255,255,0.1); }
        .info-strip-item:nth-child(4) { border-right: none; border-top: 1px solid rgba(255,255,255,0.1); }
    }
    @media (max-width: 576px) {
        .values-grid { grid-template-columns: 1fr 1fr; }
        .info-strip { grid-template-columns: 1fr; }
        .info-strip-item { border-right: none !important; border-bottom: 1px solid rgba(255,255,255,0.1) !important; }
        .info-strip-item:last-child { border-bottom: none !important; }
    }
</style>
@endpush

@section('content')

<!-- PAGE HERO BANNER -->
<div class="page-hero-banner">
    <div class="pattern"></div>
    <div class="container page-hero-banner-inner">
        <div class="page-tag">Quiénes Somos</div>
        <h1>Filosofía e <span>Identidad</span> Institucional</h1>
        <p>{{ landing('nosotros.hero_texto') }}</p>
        <div class="breadcrumb-nav">
            <a href="{{ route('landing') }}"><i class="fas fa-home" style="font-size:0.75rem"></i> Inicio</a>
            <span>/</span>
            <strong>Nosotros</strong>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="section-body">
    <div class="container">

        <!-- Visión y Misión -->
        <div class="section-header">
            <div class="section-label">Identidad Institucional</div>
            <h2 class="section-title">Misión y <span>Visión</span></h2>
            <div class="section-divider"></div>
            <p class="section-desc">{{ landing('nosotros.intro') }}</p>
        </div>

        <div class="vm-grid">
            <div class="vm-card fade-up">
                <div class="vm-card-bg"></div>
                <div class="vm-icon"><i class="fas fa-eye"></i></div>
                <h3>Nuestra Visión</h3>
                {{ landing('nosotros.vision') }}
            </div>
            <div class="vm-card accent fade-up">
                <div class="vm-card-bg"></div>
                <div class="vm-icon"><i class="fas fa-bullseye"></i></div>
                <h3>Nuestra Misión</h3>
                {{ landing('nosotros.mision') }}
            </div>
        </div>

        <!-- Valores -->
        <div class="values-title">
            <div class="section-label">Pilares Institucionales</div>
            <h2 class="section-title">Nuestros <span>Valores</span></h2>
            <div class="section-divider"></div>
        </div>

        <div class="values-grid">
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-hands-helping"></i></div>
                <h4>{{ landing('nosotros.valor1.titulo') }}</h4>
                <p>{{ landing('nosotros.valor1.texto') }}</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-star"></i></div>
                <h4>{{ landing('nosotros.valor2.titulo') }}</h4>
                <p>{{ landing('nosotros.valor2.texto') }}</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-users"></i></div>
                <h4>{{ landing('nosotros.valor3.titulo') }}</h4>
                <p>{{ landing('nosotros.valor3.texto') }}</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-lightbulb"></i></div>
                <h4>{{ landing('nosotros.valor4.titulo') }}</h4>
                <p>{{ landing('nosotros.valor4.texto') }}</p>
            </div>
        </div>

        <!-- Info strip -->
        {{-- Autoridades: las de Web pública → Bloques; sin ninguna cargada, las originales --}}
        @php
            $autoridades = \App\Support\Landing::bloques('AUTORIDAD');
            if ($autoridades->isEmpty()) {
                $autoridades = collect([
                    new \App\Models\LandingBloque(['lb_subtitulo' => 'Director General', 'lb_titulo' => 'Arq. Ovidio Luis Quispe Moya']),
                    new \App\Models\LandingBloque(['lb_subtitulo' => 'Coordinadora Académica', 'lb_titulo' => 'Prof. Adela Guerra Castaños']),
                ]);
            }
        @endphp
        <div class="info-strip fade-up">
            @foreach($autoridades as $i => $aut)
            <div class="info-strip-item">
                <div class="info-strip-icon">
                    @if($aut->url_imagen)<img src="{{ $aut->url_imagen }}" alt="{{ $aut->lb_titulo }}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
                    @else<i class="fas {{ $i === 0 ? 'fa-user-tie' : 'fa-chalkboard-teacher' }}"></i>@endif
                </div>
                <div class="info-strip-text">
                    <strong>{{ $aut->lb_subtitulo }}</strong>
                    <span>{{ $aut->lb_titulo }}</span>
                </div>
            </div>
            @endforeach
            <div class="info-strip-item">
                <div class="info-strip-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="info-strip-text">
                    <strong>Fundación</strong>
                    <span>{{ landing('general.fundacion') }}</span>
                </div>
            </div>
            <div class="info-strip-item">
                <div class="info-strip-icon"><i class="fas fa-file-alt"></i></div>
                <div class="info-strip-text">
                    <strong>Resolución Adm.</strong>
                    <span>{{ landing('general.resolucion') }}</span>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
