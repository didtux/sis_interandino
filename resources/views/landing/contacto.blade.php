@extends('landing.layout')
@section('title', 'Contacto')

@push('styles')
<style>
    .contact-layout {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 3.5rem;
    }

    /* Info side */
    .contact-info-title {
        font-size: 1.5rem; font-weight: 800;
        color: var(--dark); margin-bottom: 0.5rem;
    }
    .contact-info-sub {
        font-size: 0.93rem; color: var(--text);
        line-height: 1.75; margin-bottom: 2rem;
    }
    .contact-card {
        display: flex; align-items: flex-start;
        gap: 1.2rem; margin-bottom: 1.2rem;
        background: white; border-radius: 14px;
        padding: 1.3rem 1.5rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.06);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .contact-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .contact-card-icon {
        width: 50px; height: 50px; border-radius: 12px;
        background: var(--primary);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: background 0.25s ease;
    }
    .contact-card:hover .contact-card-icon { background: var(--accent2); }
    .contact-card-icon i { color: white; font-size: 1.1rem; }
    .contact-card strong {
        display: block; font-size: 0.78rem; font-weight: 700;
        color: var(--text); text-transform: uppercase;
        letter-spacing: 0.5px; margin-bottom: 0.25rem;
    }
    .contact-card p {
        font-size: 0.92rem; color: var(--dark);
        font-weight: 500; line-height: 1.5; margin: 0;
    }
    .contact-social {
        margin-top: 1.8rem;
    }
    .contact-social p {
        font-size: 0.83rem; color: var(--text);
        font-weight: 600; margin-bottom: 0.9rem;
    }
    .social-row {
        display: flex; gap: 0.8rem;
    }
    .social-btn {
        display: flex; align-items: center; gap: 0.6rem;
        padding: 0.6rem 1.2rem; border-radius: 10px;
        background: var(--primary); color: white;
        text-decoration: none; font-size: 0.83rem; font-weight: 600;
        transition: all 0.25s ease;
    }
    .social-btn:hover {
        background: var(--accent); color: var(--dark);
        transform: translateY(-2px); text-decoration: none;
    }
    .social-btn i { font-size: 0.9rem; }

    /* Form side */
    .form-card {
        background: white; border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 8px 40px rgba(0,0,0,0.09);
    }
    .form-card-title {
        font-size: 1.5rem; font-weight: 800;
        color: var(--dark); margin-bottom: 0.4rem;
        display: flex; align-items: center; gap: 0.7rem;
    }
    .form-card-title i { color: var(--primary); font-size: 1.3rem; }
    .form-card-sub {
        font-size: 0.9rem; color: var(--text);
        margin-bottom: 2rem; line-height: 1.6;
    }
    .form-row {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .form-group { margin-bottom: 1.1rem; }
    .form-group label {
        display: block; font-size: 0.82rem; font-weight: 600;
        color: var(--dark); margin-bottom: 0.4rem;
    }
    .form-group label .req { color: #e53e3e; margin-left: 2px; }
    .form-control {
        width: 100%; padding: 0.8rem 1.1rem;
        border: 1.5px solid var(--border); border-radius: 10px;
        font-family: 'Poppins', sans-serif; font-size: 0.9rem;
        color: var(--dark); background: var(--light);
        transition: all 0.25s ease; outline: none;
    }
    .form-control:focus {
        border-color: var(--primary); background: white;
        box-shadow: 0 0 0 3px rgba(26,58,108,0.08);
    }
    textarea.form-control { resize: vertical; min-height: 130px; }
    .btn-submit {
        width: 100%; padding: 1rem;
        background: linear-gradient(135deg, var(--primary), #2a5298);
        color: white; border: none; border-radius: 10px;
        font-family: 'Poppins', sans-serif; font-size: 1rem;
        font-weight: 700; cursor: pointer;
        transition: all 0.3s ease; letter-spacing: 0.5px;
        display: flex; align-items: center; justify-content: center; gap: 0.6rem;
        margin-top: 0.5rem;
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(26,58,108,0.35);
    }
    .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

    @media (max-width: 992px) {
        .contact-layout { grid-template-columns: 1fr; }
        .form-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 576px) {
        .form-card { padding: 2rem 1.5rem; }
        .social-row { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

<!-- PAGE HERO BANNER -->
<div class="page-hero-banner">
    <div class="pattern"></div>
    <div class="container page-hero-banner-inner">
        <div class="page-tag">Comunícate con Nosotros</div>
        <h1>Información de <span>Contacto</span></h1>
        <p>Estamos para atenderte. Visítanos o escríbenos para información sobre inscripciones y servicios educativos.</p>
        <div class="breadcrumb-nav">
            <a href="{{ route('landing') }}"><i class="fas fa-home" style="font-size:0.75rem"></i> Inicio</a>
            <span>/</span>
            <strong>Contacto</strong>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="section-body light">
    <div class="container">

        <div class="section-header">
            <div class="section-label">Estamos Aquí para Ti</div>
            <h2 class="section-title">¿Cómo podemos <span>ayudarte?</span></h2>
            <div class="section-divider"></div>
            <p class="section-desc">
                Nuestro equipo directivo está disponible para orientarte en el proceso de
                inscripción y resolver todas tus dudas sobre nuestra oferta educativa.
            </p>
        </div>

        <div class="contact-layout">

            <!-- Left: Contact info -->
            <div>
                <h3 class="contact-info-title">Encuéntranos</h3>
                <p class="contact-info-sub">
                    Nos encontramos en la ciudad de El Alto, La Paz.
                    Puedes visitarnos o enviarnos un mensaje.
                </p>

                <div class="contact-card fade-up">
                    <div class="contact-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <strong>Dirección</strong>
                        <p>Ciudad de El Alto, Departamento de La Paz, Bolivia</p>
                    </div>
                </div>

                <div class="contact-card fade-up">
                    <div class="contact-card-icon"><i class="fas fa-user-tie"></i></div>
                    <div>
                        <strong>Director General</strong>
                        <p>Arq. Ovidio Luis Quispe Moya</p>
                    </div>
                </div>

                <div class="contact-card fade-up">
                    <div class="contact-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <strong>Coordinación Académica</strong>
                        <p>Prof. Adela Guerra Castaños</p>
                    </div>
                </div>

                <div class="contact-card fade-up">
                    <div class="contact-card-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <strong>Niveles Educativos</strong>
                        <p>Inicial · Primaria Comunitaria Vocacional · Secundaria Comunitaria Productiva</p>
                    </div>
                </div>

                <div class="contact-card fade-up">
                    <div class="contact-card-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <strong>Resolución Administrativa</strong>
                        <p>No. 311/2006 — Dependencia Privada</p>
                    </div>
                </div>

                <div class="contact-social fade-up">
                    <p>Síguenos en redes sociales:</p>
                    <div class="social-row">
                        <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i> Facebook</a>
                        <a href="#" class="social-btn"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        <a href="#" class="social-btn"><i class="fab fa-instagram"></i> Instagram</a>
                    </div>
                </div>
            </div>

            <!-- Right: Contact form -->
            <div class="form-card fade-up">
                <h3 class="form-card-title">
                    <i class="fas fa-paper-plane"></i> Envíanos un Mensaje
                </h3>
                <p class="form-card-sub">
                    Completa el formulario y nos pondremos en contacto contigo a la brevedad.
                    Los campos marcados con <span style="color:#e53e3e">*</span> son obligatorios.
                </p>

                <form id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre completo <span class="req">*</span></label>
                            <input type="text" id="nombre" class="form-control" placeholder="Tu nombre completo" required>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono / WhatsApp</label>
                            <input type="text" id="telefono" class="form-control" placeholder="Ej: 78901234">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input type="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                    </div>
                    <div class="form-group">
                        <label for="asunto">Motivo de consulta</label>
                        <select id="asunto" class="form-control">
                            <option value="">Selecciona un motivo...</option>
                            <option>Información sobre inscripciones</option>
                            <option>Consulta sobre nivel Inicial</option>
                            <option>Consulta sobre nivel Primaria</option>
                            <option>Consulta sobre nivel Secundaria</option>
                            <option>Información sobre pensiones y pagos</option>
                            <option>Solicitud de documentos</option>
                            <option>Información general</option>
                            <option>Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="req">*</span></label>
                        <textarea id="mensaje" class="form-control" placeholder="Escribe tu consulta con detalle..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Enviar Mensaje
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('contactForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        btn.disabled = true;
        setTimeout(function () {
            document.getElementById('contactForm').reset();
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Mensaje';
            btn.disabled = false;
            showToast('¡Mensaje recibido! Nos pondremos en contacto pronto.');
        }, 1300);
    });
</script>
@endpush
