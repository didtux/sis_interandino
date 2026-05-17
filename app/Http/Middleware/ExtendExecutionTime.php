<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Extiende el tiempo de ejecución y memoria para rutas de reportes pesados
 * (PDFs grandes, centralizadores, kardex completos, etc.).
 *
 * Uso: ->middleware('reportes') en routes/web.php
 */
class ExtendExecutionTime
{
    public function handle(Request $request, Closure $next, $segundos = 300, $memoria = '512M')
    {
        @set_time_limit((int) $segundos);
        @ini_set('max_execution_time', (int) $segundos);
        @ini_set('memory_limit', $memoria);

        return $next($request);
    }
}
