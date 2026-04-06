<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RolPermiso;
use App\Models\Modulo;

class CheckPermiso
{
    // Mapeo URL prefix → mod_slug
    private $rutaModulo = [
        'usuarios'               => 'usuarios',
        'roles'                  => 'usuarios',
        'estudiantes'            => 'estudiantes',
        'cursos'                 => 'cursos',
        'docentes'               => 'docentes',
        'materias'               => 'materias',
        'asistencias'            => 'asistencias',
        'asistencia-config'      => 'asistencia-config',
        'notas'                  => 'notas',
        'padres'                 => 'padres',
        'pagos'                  => 'pagos.mensualidades',
        'inscripciones'          => 'inscripciones',
        'descuentos'             => 'descuentos',
        'servicios'              => 'servicios',
        'pagos-servicios'        => 'pagos-servicios',
        'categorias'             => 'categorias',
        'productos'              => 'productos',
        'ventas'                 => 'ventas.registro',
        'proveedores'            => 'proveedores',
        'movimientos'            => 'movimientos',
        'reporte-stock'          => 'movimientos.stock',
        'agenda'                 => 'agenda',
        'psicopedagogia'         => 'psicopedagogia',
        'enfermeria'             => 'enfermeria',
        'vehiculos'              => 'vehiculos',
        'choferes'               => 'choferes',
        'rutas'                  => 'rutas',
        'asignaciones-transporte'=> 'asignaciones-transporte',
        'pagos-transporte'       => 'pagos-transporte',
        'estudiantes-rutas'      => 'estudiantes-rutas',
        'asistencia-clases'      => 'notas',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) return redirect('/login');
        if ($user->rol_id == 1) return $next($request);

        // Excluir rutas API internas
        $segmento = $request->segment(1);
        if ($segmento === 'api') return $next($request);
        $modSlug = $this->rutaModulo[$segmento] ?? null;

        if (!$modSlug) return $next($request);

        // Determinar acción por método HTTP + URL pattern
        $method = $request->method();
        $accion = 'perm_ver'; // default

        if (in_array($method, ['POST'])) {
            // POST a /recurso = crear, POST a /recurso/{id}/algo = acción especial
            $accion = 'perm_crear';
        } elseif (in_array($method, ['PUT', 'PATCH'])) {
            $accion = 'perm_editar';
        } elseif ($method === 'DELETE') {
            $accion = 'perm_eliminar';
        } elseif ($method === 'GET') {
            $seg2 = $request->segment(2);
            if ($seg2 === 'create') {
                $accion = 'perm_crear';
            } elseif ($request->segment(3) === 'edit') {
                $accion = 'perm_editar';
            } else {
                $accion = 'perm_ver';
            }
        }

        $permiso = RolPermiso::where('rol_id', $user->rol_id)
            ->whereHas('modulo', fn($q) => $q->where('mod_slug', $modSlug))
            ->first();

        if (!$permiso || !$permiso->$accion) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Sin permisos'], 403);
            }
            abort(403, 'No tiene permisos para realizar esta acción.');
        }

        return $next($request);
    }
}
