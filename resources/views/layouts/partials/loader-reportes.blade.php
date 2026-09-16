{{--
    Loader para reportes pesados (boletines, centralizadores, kardex, Excel).

    Un PDF se descarga en vez de navegar, así que el navegador no avisa cuándo
    terminó. Se le agrega a la URL un token `_dl`; el middleware AvisarFinDescarga
    lo devuelve como cookie recién cuando la respuesta está lista, y acá se sondea
    esa cookie para cerrar el loader en el momento justo.

    No hay que marcar cada enlace: se enganchan solos los que apuntan a rutas de
    reporte (ver ES_REPORTE). Para excluir uno, se le pone data-sin-loader.
--}}
<div id="loaderReportes" class="loader-reportes" role="status" aria-live="polite" hidden>
    <div class="loader-reportes__caja">
        <div class="loader-reportes__spinner"></div>
        <h6 class="loader-reportes__titulo">Generando el reporte…</h6>
        <p class="loader-reportes__texto" id="loaderReportesTexto">
            Esto puede tardar unos segundos según la cantidad de estudiantes.
        </p>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cerrarLoaderReportes()">
            Seguir navegando
        </button>
    </div>
</div>

<style>
    .loader-reportes {
        position: fixed; inset: 0; z-index: 2000;
        display: flex; align-items: center; justify-content: center;
        background: rgba(30, 33, 48, .55); backdrop-filter: blur(2px);
    }
    .loader-reportes[hidden] { display: none; }
    .loader-reportes__caja {
        background: #fff; border-radius: .6rem; padding: 1.75rem 2.25rem;
        text-align: center; max-width: 330px; box-shadow: 0 .6rem 2rem rgba(0,0,0,.25);
    }
    .loader-reportes__spinner {
        width: 42px; height: 42px; margin: 0 auto 1rem;
        border: 4px solid #e4e6fc; border-top-color: #6777ef; border-radius: 50%;
        animation: loaderReportesGira .8s linear infinite;
    }
    @keyframes loaderReportesGira { to { transform: rotate(360deg); } }
    .loader-reportes__titulo { margin: 0 0 .35rem; font-weight: 600; }
    .loader-reportes__texto  { margin: 0 0 1rem; font-size: .85rem; color: #6c757d; }
    @media (prefers-reduced-motion: reduce) {
        .loader-reportes__spinner { animation-duration: 2.4s; }
    }
</style>

<script>
(function () {
    // Rutas que generan un archivo o una vista pesada.
    const ES_REPORTE = /(reporte|centralizador|boletin|kardex|pdf|excel|imprimir|exportar)/i;

    const overlay = document.getElementById('loaderReportes');
    const texto   = document.getElementById('loaderReportesTexto');
    let   sondeo  = null;
    let   corte   = null;

    function token() {
        return 'x' + Math.random().toString(36).slice(2, 10);
    }

    function leerCookie(nombre) {
        return document.cookie.split('; ').some(c => c.startsWith(nombre + '='));
    }

    function borrarCookie(nombre) {
        document.cookie = nombre + '=; Max-Age=0; path=/';
    }

    window.cerrarLoaderReportes = function () {
        overlay.hidden = true;
        clearInterval(sondeo); clearTimeout(corte);
        sondeo = corte = null;
    };

    /**
     * Muestra el loader y lo cierra cuando llega la cookie del servidor.
     * El corte por tiempo es la red de seguridad: si el reporte falla y nunca
     * llega la cookie, el usuario no queda con la pantalla bloqueada.
     */
    function abrirLoader(tk) {
        overlay.hidden = false;
        texto.textContent = 'Esto puede tardar unos segundos según la cantidad de estudiantes.';

        const cookie = 'dl_' + tk;
        const desde  = Date.now();

        sondeo = setInterval(function () {
            if (leerCookie(cookie)) { borrarCookie(cookie); cerrarLoaderReportes(); return; }
            if (Date.now() - desde > 15000) {
                texto.textContent = 'Sigue trabajando. Los cursos con muchos estudiantes tardan más.';
            }
        }, 400);

        // Tope duro: dos minutos y se libera la pantalla igual.
        corte = setTimeout(function () {
            texto.textContent = 'Está tardando más de lo normal. Podés seguir navegando.';
        }, 120000);
    }

    function conToken(url, tk) {
        try {
            const u = new URL(url, window.location.origin);
            u.searchParams.set('_dl', tk);
            return u.toString();
        } catch (e) {
            return url + (url.includes('?') ? '&' : '?') + '_dl=' + tk;
        }
    }

    // ── Enlaces ──
    document.addEventListener('click', function (ev) {
        const a = ev.target.closest('a[href]');
        if (!a || a.hasAttribute('data-sin-loader')) return;
        if (a.target === '_blank' || a.getAttribute('href').startsWith('#')) return;
        if (!ES_REPORTE.test(a.getAttribute('href'))) return;

        const tk = token();
        a.href = conToken(a.href, tk);
        abrirLoader(tk);
    }, true);

    // ── Formularios GET (los filtros de reporte) ──
    document.addEventListener('submit', function (ev) {
        const f = ev.target;
        if (!f || f.hasAttribute('data-sin-loader')) return;
        if ((f.method || 'get').toLowerCase() !== 'get') return;
        if (!ES_REPORTE.test(f.getAttribute('action') || window.location.pathname)) return;

        const tk = token();
        let campo = f.querySelector('input[name="_dl"]');
        if (!campo) {
            campo = document.createElement('input');
            campo.type = 'hidden'; campo.name = '_dl';
            f.appendChild(campo);
        }
        campo.value = tk;
        abrirLoader(tk);
    }, true);

    // Al volver con el botón "atrás" el loader no debe quedar visible.
    window.addEventListener('pageshow', function () { window.cerrarLoaderReportes(); });
})();
</script>
