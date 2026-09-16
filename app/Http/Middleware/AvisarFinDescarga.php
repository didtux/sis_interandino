<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Permite al navegador saber cuándo terminó de generarse un reporte.
 *
 * Un PDF o un Excel se descargan: la página no cambia, así que el navegador no
 * dispara ningún evento al terminar y un loader puesto al hacer clic se quedaría
 * girando para siempre. El truco clásico es que el cliente mande un token y el
 * servidor lo devuelva como cookie recién cuando la respuesta está lista; el JS
 * sondea esa cookie y ahí recién oculta el loader.
 *
 * @see resources/views/layouts/partials/loader-reportes.blade.php
 */
class AvisarFinDescarga
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('_dl');
        $respuesta = $next($request);

        // Sólo si el cliente pidió el aviso y la respuesta llegó a generarse.
        if ($token && preg_match('/^[A-Za-z0-9]{1,32}$/', $token)) {
            // headers->setCookie y no withCookie(): las descargas de Excel devuelven
            // una BinaryFileResponse de Symfony, que no tiene el helper de Laravel.
            // Sin httpOnly, porque justamente tiene que leerla el JS.
            $respuesta->headers->setCookie(cookie('dl_' . $token, '1', 5, '/', null, false, false));
        }

        return $respuesta;
    }
}
