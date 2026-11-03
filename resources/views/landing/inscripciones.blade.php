@extends('landing.layout')
@section('title', 'Inscripciones')

@push('styles')
<style>
    .insc-top { display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; margin-bottom: 3.5rem; align-items: start; }
    .insc-intro { font-size: 1.02rem; color: var(--text); line-height: 1.8; }
    .insc-intro p { margin-bottom: 1rem; }
    .periodo-card {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
        color: #fff; border-radius: 18px; padding: 1.8rem 2rem;
    }
    .periodo-card h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 1.1rem; }
    .periodo-card h3 span { color: var(--accent); }
    .periodo-row { display: flex; gap: .8rem; align-items: flex-start; margin-bottom: .9rem; font-size: .95rem; }
    .periodo-row i { color: var(--accent); margin-top: .25rem; width: 18px; text-align: center; }
    .periodo-row small { display: block; color: rgba(255,255,255,.6); font-size: .74rem; text-transform: uppercase; letter-spacing: .5px; }

    .pasos { counter-reset: paso; list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.2rem; margin-bottom: 3.5rem; }
    .pasos li {
        counter-increment: paso; background: var(--white); border: 1px solid var(--border);
        border-radius: 14px; padding: 1.4rem 1.3rem 1.3rem 1.3rem; position: relative; color: var(--dark); font-weight: 600; line-height: 1.5;
    }
    .pasos li::before {
        content: counter(paso); display: flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 50%; background: var(--accent); color: var(--dark);
        font-weight: 800; margin-bottom: .8rem;
    }

    .req-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
    .req-card { background: var(--white); border-radius: 16px; border-top: 4px solid var(--primary); box-shadow: 0 6px 24px rgba(10,26,58,.07); padding: 1.6rem 1.7rem; }
    .req-card.inicial { border-top-color: var(--accent2); }
    .req-card.secundaria { border-top-color: var(--green); }
    .req-card h3 { font-size: 1.15rem; font-weight: 800; color: var(--dark); margin-bottom: 1rem; }
    .req-item { padding: .75rem 0; border-bottom: 1px dashed var(--border); }
    .req-item:last-child { border-bottom: 0; }
    .req-item strong { color: var(--dark); display: block; }
    .req-item .tipo { font-size: .72rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: .5px; }
    .req-item .det { font-size: .9rem; color: var(--text); line-height: 1.6; margin-top: .25rem; }
    .req-item .det p:last-child, .req-item .det ul:last-child { margin-bottom: 0; }
    .req-vacio { text-align: center; padding: 2rem; background: var(--light); border-radius: 14px; color: var(--text); margin-bottom: 3rem; }

    .docs { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 3rem; }
    .doc-link {
        display: inline-flex; align-items: center; gap: .7rem; padding: .9rem 1.3rem; border-radius: 12px;
        background: var(--white); border: 1px solid var(--border); color: var(--primary); font-weight: 600; text-decoration: none;
    }
    .doc-link:hover { border-color: var(--primary); text-decoration: none; }
    .doc-link i { font-size: 1.3rem; color: #c0392b; }

    .cta-block {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
        border-radius: 22px; padding: 3rem; text-align: center; color: #fff;
    }
    .cta-block h3 { font-size: 1.6rem; font-weight: 800; margin-bottom: .7rem; }
    .cta-block p { color: rgba(255,255,255,.8); max-width: 560px; margin: 0 auto 1.8rem; line-height: 1.7; }
    .btn-cta {
        padding: .9rem 2.4rem; border-radius: 35px; font-weight: 700; text-decoration: none;
        background: var(--accent); color: var(--dark); display: inline-flex; gap: .6rem; align-items: center;
    }
    .btn-cta:hover { background: var(--accent2); color: var(--dark); text-decoration: none; }
    .insc-nota { text-align: center; font-size: .88rem; color: var(--text); margin-top: 1.5rem; }

    @media (max-width: 992px) { .insc-top { grid-template-columns: 1fr; } }
    @media (max-width: 576px) { .cta-block { padding: 2rem 1.3rem; } }
</style>
@endpush

@section('content')
<!-- PAGE HERO BANNER -->
<div class="page-hero-banner">
    <div class="pattern"></div>
    <div class="container page-hero-banner-inner">
        <div class="page-tag">Gestión {{ $anio }}</div>
        <h1>Pre-inscripción e <span>Inscripción</span></h1>
        <p>{{ landing('inscripciones.hero_texto') }}</p>
        <div class="breadcrumb-nav">
            <a href="{{ route('landing') }}"><i class="fas fa-home" style="font-size:0.75rem"></i> Inicio</a>
            <span>/</span>
            <strong>Inscripciones</strong>
        </div>
    </div>
</div>

<div class="section-body">
    <div class="container">

        <div class="insc-top">
            <div class="insc-intro fade-up">{{ landing('inscripciones.intro') }}</div>

            <div class="periodo-card fade-up">
                <h3>Gestión <span>{{ $anio }}</span></h3>
                @if($gestion && ($gestion->ges_preinsc_desde || $gestion->ges_preinsc_hasta))
                <div class="periodo-row"><i class="fas fa-calendar-check"></i>
                    <div><small>Pre-inscripción</small>
                        @if($gestion->ges_preinsc_desde) del {{ \Carbon\Carbon::parse($gestion->ges_preinsc_desde)->format('d/m/Y') }} @endif
                        @if($gestion->ges_preinsc_hasta) al {{ \Carbon\Carbon::parse($gestion->ges_preinsc_hasta)->format('d/m/Y') }} @endif
                    </div>
                </div>
                @endif
                @if($gestion && $gestion->ges_fecha_inicio)
                <div class="periodo-row"><i class="fas fa-school"></i>
                    <div><small>Inicio de clases</small>{{ \Carbon\Carbon::parse($gestion->ges_fecha_inicio)->format('d/m/Y') }}</div>
                </div>
                @endif
                @php [$reservaWeb] = rescue(fn() => \App\Models\Tarifa::reservaPara($anio, null), [0], false); @endphp
                @if($reservaWeb > 0)
                <div class="periodo-row"><i class="fas fa-ticket-alt"></i>
                    <div><small>Reserva de cupo</small>Bs {{ number_format($reservaWeb, 2) }} (se descuenta de la inscripción; puede variar según el curso)</div>
                </div>
                @endif
                <div class="periodo-row"><i class="fas fa-map-marker-alt"></i>
                    <div><small>Dónde</small>Secretaría de la Unidad Educativa{{ optional($sistemaConfig)->config_horario ? ' · ' . $sistemaConfig->config_horario : '' }}</div>
                </div>
                @if(optional($sistemaConfig)->config_telefono)
                <div class="periodo-row"><i class="fas fa-phone"></i>
                    <div><small>Consultas</small>{{ $sistemaConfig->config_telefono }}</div>
                </div>
                @endif
            </div>
        </div>

        @php $pasos = \App\Support\Landing::lista('inscripciones.pasos'); @endphp
        @if($pasos)
        <div class="section-header" style="margin-bottom:2rem">
            <div class="section-label">Paso a paso</div>
            <h2 class="section-title">¿Cómo <span>inscribir?</span></h2>
            <div class="section-divider"></div>
        </div>
        <ol class="pasos">
            @foreach($pasos as $paso)<li class="fade-up">{{ $paso }}</li>@endforeach
        </ol>
        @endif

        <div class="section-header" style="margin-bottom:2rem">
            <div class="section-label">Documentación</div>
            <h2 class="section-title">Requisitos por <span>nivel</span></h2>
            <div class="section-divider"></div>
        </div>

        @if($generales->isEmpty() && $porNivel->isEmpty())
            <div class="req-vacio fade-up">
                <i class="fas fa-info-circle" style="color:var(--primary)"></i>
                Los requisitos de la gestión {{ $anio }} se publicarán pronto. Mientras tanto, escríbenos y te informamos.
            </div>
        @else
            <div class="req-grid">
                @if($generales->isNotEmpty())
                <div class="req-card fade-up">
                    <h3><i class="fas fa-clipboard-list" style="color:var(--primary)"></i> Todos los niveles</h3>
                    @foreach($generales as $r)
                        @include('landing._requisito', ['r' => $r])
                    @endforeach
                </div>
                @endif
                @foreach(\App\Models\LandingBloque::NIVELES as $clave => $nombre)
                    @if($porNivel->has($clave))
                    <div class="req-card {{ strtolower($clave) }} fade-up">
                        <h3>{{ $nombre }}</h3>
                        @foreach($porNivel[$clave] as $r)
                            @include('landing._requisito', ['r' => $r])
                        @endforeach
                    </div>
                    @endif
                @endforeach
            </div>
        @endif

        @if($documentos->isNotEmpty())
        <div class="section-header" style="margin-bottom:1.5rem">
            <div class="section-label">Descargas</div>
            <h2 class="section-title">Formularios y <span>documentos</span></h2>
            <div class="section-divider"></div>
        </div>
        <div class="docs">
            @foreach($documentos as $d)
                <a class="doc-link fade-up" href="{{ $d->url_archivo ?: $d->lb_link }}" target="_blank" rel="noopener">
                    <i class="fas {{ $d->lb_archivo ? 'fa-file-pdf' : 'fa-external-link-alt' }}"></i>
                    <span>{{ $d->lb_titulo }}@if($d->niv_nombre) <small class="text-muted">· {{ \App\Models\LandingBloque::NIVELES[$d->niv_nombre] ?? '' }}</small>@endif</span>
                </a>
            @endforeach
        </div>
        @endif

        <div class="cta-block fade-up">
            <h3>¿Quieres reservar un cupo?</h3>
            <p>Déjanos tus datos y el nivel que te interesa; secretaría se comunicará contigo para completar la pre-inscripción.</p>
            <a href="{{ route('landing.contacto', ['motivo' => 'inscripcion']) }}#contactForm" class="btn-cta">
                <i class="fas fa-user-plus"></i> Solicitar inscripción
            </a>
        </div>
        <p class="insc-nota">{{ landing('inscripciones.nota') }}</p>

    </div>
</div>
@endsection
