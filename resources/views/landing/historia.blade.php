@extends('landing.layout')
@section('title', 'Historia')

@push('styles')
<style>
    .historia-layout {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 4rem; align-items: start;
    }
    .historia-text {
        font-size: 0.95rem; line-height: 1.9; color: var(--text);
    }
    .historia-text p + p { margin-top: 1.3rem; }
    .historia-text strong { color: var(--dark); }

    .motto-box {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 18px; padding: 2.5rem;
        text-align: center; color: white;
        margin-top: 2.5rem; position: relative; overflow: hidden;
    }
    .motto-box::before {
        content: '\f10d'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        position: absolute; top: 1rem; left: 1.5rem;
        font-size: 3rem; color: rgba(255,255,255,0.07);
    }
    .motto-box blockquote {
        font-size: 1.15rem; font-style: italic; font-weight: 500;
        margin-bottom: 0.6rem; color: rgba(255,255,255,0.95); line-height: 1.7;
    }
    .motto-box cite {
        font-size: 0.82rem; color: var(--accent);
        font-style: normal; font-weight: 600; letter-spacing: 0.5px;
    }

    /* Timeline */
    .timeline-wrapper {
        position: sticky; top: 90px;
    }
    .timeline-title {
        font-size: 1.1rem; font-weight: 800; color: var(--dark);
        margin-bottom: 1.8rem;
        display: flex; align-items: center; gap: 0.7rem;
    }
    .timeline-title i { color: var(--primary); }
    .timeline {
        position: relative; padding-left: 1.8rem;
    }
    .timeline::before {
        content: ''; position: absolute; left: 8px; top: 0; bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, var(--primary), var(--accent), var(--green));
    }
    .tl-item {
        position: relative; padding-left: 2rem; margin-bottom: 2.2rem;
    }
    .tl-item:last-child { margin-bottom: 0; }
    .tl-dot {
        position: absolute; left: -2.3rem; top: 4px;
        width: 20px; height: 20px; border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 2px var(--primary);
        background: var(--primary);
    }
    .tl-item.gold .tl-dot  { background: var(--accent);  box-shadow: 0 0 0 2px var(--accent); }
    .tl-item.green .tl-dot { background: var(--green);   box-shadow: 0 0 0 2px var(--green); }

    .tl-year {
        font-size: 0.7rem; font-weight: 700; letter-spacing: 1.5px;
        text-transform: uppercase; margin-bottom: 0.3rem; color: var(--primary);
    }
    .tl-item.gold .tl-year  { color: var(--accent2); }
    .tl-item.green .tl-year { color: var(--green); }
    .tl-title {
        font-size: 0.97rem; font-weight: 700;
        color: var(--dark); margin-bottom: 0.4rem;
    }
    .tl-desc {
        font-size: 0.85rem; color: var(--text); line-height: 1.7;
    }

    /* Founders card */
    .founders-card {
        background: var(--primary); border-radius: 18px;
        padding: 2rem; color: white; margin-top: 2rem;
    }
    .founders-card h4 {
        font-size: 0.95rem; font-weight: 700; color: var(--accent);
        margin-bottom: 1.2rem;
        display: flex; align-items: center; gap: 0.6rem;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .founder-item {
        display: flex; align-items: center; gap: 0.9rem;
        padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.08);
        font-size: 0.9rem;
    }
    .founder-item:last-child { border-bottom: none; }
    .founder-item i { color: var(--accent); font-size: 0.9rem; flex-shrink: 0; }
    .founder-item .name { font-weight: 600; display: block; }
    .founder-item .role { font-size: 0.78rem; color: rgba(255,255,255,0.5); }

    .inst-data {
        margin-top: 1.5rem; padding-top: 1.2rem;
        border-top: 1px solid rgba(255,255,255,0.12);
    }
    .inst-data h5 {
        font-size: 0.85rem; font-weight: 700; color: var(--accent);
        margin-bottom: 0.9rem;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .inst-row {
        display: flex; align-items: center; gap: 0.8rem;
        padding: 0.55rem 0; font-size: 0.85rem; color: rgba(255,255,255,0.8);
        border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .inst-row:last-child { border-bottom: none; }
    .inst-row i { color: var(--accent); font-size: 0.82rem; width: 16px; text-align: center; }

    @media (max-width: 992px) {
        .historia-layout { grid-template-columns: 1fr; }
        .timeline-wrapper { position: static; margin-top: 2rem; }
    }
</style>
@endpush

@section('content')

<!-- PAGE HERO BANNER -->
<div class="page-hero-banner">
    <div class="pattern"></div>
    <div class="container page-hero-banner-inner">
        <div class="page-tag">Nuestra Trayectoria</div>
        <h1>Reseña <span>Histórica</span></h1>
        <p>Veinte años de compromiso con la educación en El Alto, construyendo historia y formando generaciones.</p>
        <div class="breadcrumb-nav">
            <a href="{{ route('landing') }}"><i class="fas fa-home" style="font-size:0.75rem"></i> Inicio</a>
            <span>/</span>
            <strong>Historia</strong>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="section-body">
    <div class="container">

        <div class="section-header">
            <div class="section-label">Desde 2005</div>
            <h2 class="section-title">20 Años <span>Construyendo Futuro</span></h2>
            <div class="section-divider"></div>
            <p class="section-desc">
                La historia de una institución que nació del compromiso de tres hermanos con la
                educación de El Alto y que hoy celebra dos décadas de excelencia.
            </p>
        </div>

        <div class="historia-layout">

            <!-- Left: Narrative -->
            <div>
                <div class="historia-text fade-up">
                    <p>
                        La Unidad Educativa Privada <strong>"INTERANDINO BOLIVIANO"</strong> de la Ciudad de
                        El Alto del Departamento de La Paz, fue fundada el <strong>25 de mayo del año 2005</strong>
                        por iniciativa propia de los Señores Néstor Quispe Copana, René Víctor Quispe Copana
                        y Juan Quispe Copana.
                    </p>
                    <p>
                        Quienes al observar la problemática educativa en la ciudad de El Alto decidieron
                        invertir en la construcción de una Unidad Educativa diferente de las demás.
                        Su visión principal era ser una institución <strong>inclusiva, pluralista y comunitaria</strong>
                        con valores y principios democráticos.
                    </p>
                    <p>
                        Desde sus inicios, la institución se propuso formar estudiantes autónomos, reflexivos,
                        críticos y productivos con capacidad creativa e intelectual en la construcción
                        de sus conocimientos científicos, técnicos, tecnológicos y artísticos bajo la
                        concepción del <strong>vivir bien en la diversidad</strong>.
                    </p>
                    <p>
                        Interandino Boliviano es una Unidad Educativa Privada donde se transmite una
                        filosofía específica del mundo, del hombre y de la historia. Los principios y
                        valores evangélicos se convierten en <strong>normas educativas de convivencia fraterna</strong>,
                        en el logro por la educación integral dirigida por profesores dedicados,
                        comprometidos y en constante actualización.
                    </p>
                    <p>
                        A través de sus tres niveles educativos — Inicial, Primaria Comunitaria Vocacional
                        y Secundaria Comunitaria Productiva — la institución ha crecido sostenidamente,
                        sumando familias que confían en su propuesta educativa diferenciada y
                        adaptada al contexto cultural de El Alto y Bolivia.
                    </p>
                    <p>
                        Hoy, con <strong>veinte años de funcionamiento</strong>, Interandino Boliviano
                        reafirma su compromiso de seguir contribuyendo, mejorando y desarrollando
                        la educación en El Alto, formando cada año una nueva generación de estudiantes
                        preparados para el futuro.
                    </p>
                </div>

                <div class="motto-box fade-up">
                    <blockquote>"Adquiere Sabiduría, adquiere inteligencia"</blockquote>
                    <cite>— Proverbios 4:5 &nbsp;|&nbsp; Lema: <em>Contribuir, mejorar y desarrollar</em></cite>
                </div>
            </div>

            <!-- Right: Timeline + Founders -->
            <div>
                <div class="timeline-wrapper">
                    <div class="timeline-title">
                        <i class="fas fa-history"></i> Línea del Tiempo
                    </div>
                    <div class="timeline">
                        <div class="tl-item gold fade-up">
                            <div class="tl-dot"></div>
                            <div class="tl-year">25 de Mayo · 2005</div>
                            <div class="tl-title">Fundación Institucional</div>
                            <div class="tl-desc">Los hermanos Quispe Copana fundan la UE en El Alto con la visión de una educación inclusiva y diferente.</div>
                        </div>
                        <div class="tl-item fade-up">
                            <div class="tl-dot"></div>
                            <div class="tl-year">2006</div>
                            <div class="tl-title">Resolución Administrativa</div>
                            <div class="tl-desc">Obtención de la R.A. No. 311/2006 que avala oficialmente el funcionamiento de la institución como unidad privada.</div>
                        </div>
                        <div class="tl-item fade-up">
                            <div class="tl-dot"></div>
                            <div class="tl-year">2010</div>
                            <div class="tl-title">Consolidación de los 3 Niveles</div>
                            <div class="tl-desc">La institución consolida Inicial, Primaria y Secundaria, ampliando su cobertura en la comunidad de El Alto.</div>
                        </div>
                        <div class="tl-item fade-up">
                            <div class="tl-dot"></div>
                            <div class="tl-year">2015 — 2020</div>
                            <div class="tl-title">Fortalecimiento Académico</div>
                            <div class="tl-desc">Adopción del modelo sociocomunitario productivo, capacitación docente continua y nuevas metodologías pedagógicas.</div>
                        </div>
                        <div class="tl-item green fade-up">
                            <div class="tl-dot"></div>
                            <div class="tl-year">2025 · 20 Años</div>
                            <div class="tl-title">¡Dos Décadas de Excelencia!</div>
                            <div class="tl-desc">Celebramos 20 años formando estudiantes íntegros, reafirmando el compromiso con El Alto y con Bolivia.</div>
                        </div>
                    </div>

                    <div class="founders-card fade-up">
                        <h4><i class="fas fa-star"></i> Fundadores</h4>
                        <div class="founder-item">
                            <i class="fas fa-user-circle"></i>
                            <div>
                                <span class="name">Sr. Néstor Quispe Copana</span>
                                <span class="role">Co-fundador</span>
                            </div>
                        </div>
                        <div class="founder-item">
                            <i class="fas fa-user-circle"></i>
                            <div>
                                <span class="name">Sr. René Víctor Quispe Copana</span>
                                <span class="role">Co-fundador</span>
                            </div>
                        </div>
                        <div class="founder-item">
                            <i class="fas fa-user-circle"></i>
                            <div>
                                <span class="name">Sr. Juan Quispe Copana</span>
                                <span class="role">Co-fundador</span>
                            </div>
                        </div>

                        <div class="inst-data">
                            <h5><i class="fas fa-building"></i> Datos Institucionales</h5>
                            <div class="inst-row"><i class="fas fa-map-marker-alt"></i> El Alto, La Paz — Bolivia</div>
                            <div class="inst-row"><i class="fas fa-shield-alt"></i> Dependencia Privada</div>
                            <div class="inst-row"><i class="fas fa-calendar-alt"></i> Fundado el 25 de Mayo de 2005</div>
                            <div class="inst-row"><i class="fas fa-file-alt"></i> R.A. No. 311/2006</div>
                            <div class="inst-row"><i class="fas fa-hourglass-half"></i> 20 años de funcionamiento</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
