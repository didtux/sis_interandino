<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\Permisos;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Controla el acceso según los permisos de la pantalla Roles (rol_permisos).
 *
 * Cómo resuelve el módulo de una URL:
 *  1. El primer tramo se traduce con $rutaModulo (o $prefijos, para las rutas
 *     de reportes tipo "pagos-reporte-excel").
 *  2. Si el segundo tramo corresponde a un submódulo del catálogo, manda el
 *     submódulo: /asistencia-config/permisos → asistencia-config.permisos.
 *     Antes sólo se miraba el primer tramo, así que un rol con el permiso
 *     marcado en "Licencias" recibía Acceso Denegado porque se le revisaba
 *     "Configuración de asistencia".
 *  3. Alcanza con tener el permiso en el submódulo o en el módulo general
 *     (lo que se revisaba antes), así que nadie pierde un acceso que ya tenía.
 *
 * La acción sale del método del controlador, no del verbo HTTP: antes todo
 * POST pedía "crear", y anular, activar o generar un reporte quedaban
 * bloqueados para roles con permiso de editar o de ver.
 */
class CheckPermiso
{
    // Primer tramo de la URL → módulo(s). Con varios, alcanza cualquiera.
    private $rutaModulo = [
        'usuarios'               => 'usuarios',
        'roles'                  => 'usuarios',
        'estudiantes'            => 'estudiantes',
        'cursos'                 => 'cursos',
        'docentes'               => 'docentes',
        'materias'               => 'materias',
        'asistencias'            => ['asistencias', 'asistencias.registro'],
        'asistencia-config'      => 'asistencia-config',
        'notas'                  => 'notas',
        'padres'                 => 'padres',
        'pagos'                  => 'pagos.mensualidades',
        'caja'                   => 'pagos.mensualidades',
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
        'actividades-asistencia' => 'actividades-asistencia',
        // Estaban en el catálogo pero sin control: cualquier usuario logueado
        // podía, por ejemplo, activar una gestión.
        'parametrizacion'        => 'parametrizacion',
        'unidad-educativa'       => 'unidad-educativa',
        'concejo'                => 'concejo',
        'contacto-mensajes'      => 'contacto-mensajes',
        'tarifas'                => 'tarifas',
        'parametros'             => 'parametros',
        'preinscripciones'       => 'preinscripciones',
        'promocion'              => 'promocion',
        'devoluciones'           => 'devoluciones',
        'recibos'                => ['pagos.mensualidades', 'inscripciones', 'preinscripciones', 'devoluciones'],
        'bloqueos'               => 'portal-bloqueos',
        'publicaciones'          => 'publicaciones',
        'web-publica'            => 'web-publica',
    ];

    // Rutas de reportes con URL propia ("pagos-reporte-excel"…). Quedaban sin
    // ningún control: un Docente podía bajar el Excel de pagos. Se busca el
    // prefijo más largo que coincida.
    private $prefijos = [
        'pagos-servicios-reporte'            => 'pagos-servicios',
        'pagos-mora'                         => 'pagos.mensualidades',
        'pagos-reporte'                      => 'pagos.mensualidades',
        'pagos-resumen-anual'                => 'pagos.mensualidades',
        'ventas-reporte'                     => 'ventas.registro',
        'inscripciones-reporte'              => ['inscripciones.reportes', 'inscripciones'],
        'reporte-stock-pdf'                  => 'movimientos.stock',
        'descuentos-estudiantes'             => 'descuentos',
        'cursos-reporte-pdf'                 => 'cursos',
        'agenda-reporte-pdf'                 => 'agenda',
        'actividades-asistencia-reporte-pdf' => 'actividades-asistencia',
        'asistencias-'                       => ['asistencias', 'asistencias.registro', 'asistencia-config.reportes'],
        'notas-reporte'                      => 'notas',
        'estudiantes-'                       => 'estudiantes',
    ];

    // Segundos tramos que no son un módulo propio sino parte de otro: los
    // reportes de licencias (/asistencia-config/licencias/*) se piden desde la
    // pantalla de Permisos, así que los habilita ese submódulo (o Reportes).
    private $alias = [
        'asistencia-config.licencias' => ['asistencia-config.permisos', 'asistencia-config.reportes'],
    ];

    // Prefijos cuyo acceso lo decide el controlador (capacidades especiales o
    // "es docente / es el padre del alumno"). No son un olvido del mapa: se
    // listan para que no aparezcan como rutas sin control.
    private $controlEnControlador = [
        'alertas-parcial', 'atencion', 'auditoria', 'comunicados', 'kardex-docente', 'kardex-estudiante',
        'mis-comunicados', 'mis-documentos', 'mi-portal', 'mi-transporte', 'observados',
    ];

    // Acciones de sólo lectura aunque viajen por POST (generar un reporte…).
    // Las que producen un archivo piden "exportar"; el resto, "ver".
    private const ACCION_EXPORTAR = '/pdf|excel|export|imprimir|descargar/i';
    private const ACCION_LECTURA  = '/reporte|generar|buscar|preview|verificar/i';
    private const ACCION_ANULAR   = '/anular/i';
    private const ACCION_IMPORTAR = '/importar|cargar/i';
    private const ACCION_BORRADO  = '/eliminar|borrar|limpiar/i';

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) return redirect('/login');
        if ($user->rol_id == 1) return $next($request);

        $segmento = $request->segment(1);
        if ($segmento === 'api') return $next($request);

        $modulos = $this->modulosDeUrl($segmento, $request->segment(2));
        if (!$modulos) {
            if (in_array($segmento, $this->controlEnControlador, true)) return $next($request);
            return $this->sinMapeo($request, $next);
        }

        $acciones = $this->accionesRequeridas($request);
        $permisos = Permisos::delRol((int) $user->rol_id);

        foreach ($modulos as $slug) {
            foreach ($acciones as $accion) {
                if (!empty($permisos[$slug][substr($accion, 5)])) return $next($request);
            }
        }

        $nombre = $this->catalogo()[$modulos[0]]->mod_nombre ?? $modulos[0];
        $verbo  = implode(' o ', array_map(fn($a) => substr($a, 5), $acciones));
        $msg    = "Su rol no tiene el permiso «{$verbo}» en el módulo «{$nombre}».";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => 'Sin permisos', 'message' => $msg], 403);
        }
        abort(403, $msg);
    }

    /** Slugs que habilitan la URL, del más específico al más general. */
    private function modulosDeUrl(?string $seg1, ?string $seg2): array
    {
        $base = $this->rutaModulo[$seg1] ?? null;
        if ($base === null) {
            $mejor = '';
            foreach ($this->prefijos as $prefijo => $mod) {
                if (str_starts_with((string) $seg1, $prefijo) && strlen($prefijo) > strlen($mejor)) {
                    $mejor = $prefijo;
                    $base  = $mod;
                }
            }
        }
        if ($base === null) return [];
        $base = (array) $base;

        // Submódulo por el segundo tramo: "asistencia-config.permisos" (hijo con
        // prefijo) o "gestiones" (hijo de parametrizacion con slug propio).
        $catalogo = $this->catalogo();
        $especifico = [];
        if ($seg2) {
            foreach ($base as $b) {
                if (isset($catalogo["{$b}.{$seg2}"])) $especifico[] = "{$b}.{$seg2}";
                if (isset($catalogo[$seg2]) && $catalogo[$seg2]->padre === $b) $especifico[] = $seg2;
                foreach ($this->alias["{$b}.{$seg2}"] ?? [] as $a) $especifico[] = $a;
            }
        }
        return array_values(array_unique(array_merge($especifico, $base)));
    }

    /** Columnas de rol_permisos que habilitan la petición (alcanza una). */
    private function accionesRequeridas(Request $request): array
    {
        $metodo = optional($request->route())->getActionMethod();

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($metodo === 'create' || $request->segment(2) === 'create') return ['perm_crear'];
            if ($metodo === 'edit' || $request->segment(3) === 'edit') return ['perm_editar'];
            if ($metodo && preg_match(self::ACCION_EXPORTAR, $metodo)) return ['perm_exportar'];
            return ['perm_ver'];
        }

        if ($metodo === 'store')   return ['perm_crear'];
        if ($metodo === 'update')  return ['perm_editar'];
        if ($metodo === 'destroy') return ['perm_eliminar'];
        // El orden importa: "importarExcel" y "eliminarCargaMasiva" escriben,
        // aunque el nombre tenga "excel"/"carga".
        if ($metodo && preg_match(self::ACCION_BORRADO, $metodo)) return ['perm_eliminar'];
        if ($metodo && preg_match(self::ACCION_IMPORTAR, $metodo)) return ['perm_crear'];
        if ($metodo && preg_match(self::ACCION_ANULAR, $metodo)) return ['perm_anular'];
        if ($metodo && preg_match(self::ACCION_EXPORTAR, $metodo)) return ['perm_exportar'];
        if ($metodo && preg_match(self::ACCION_LECTURA, $metodo)) return ['perm_ver'];
        if ($request->isMethod('DELETE')) return ['perm_eliminar'];

        // Acciones propias (anular, pagar, activar, subir/bajar…): modifican un
        // registro existente o registran algo nuevo; alcanza crear o editar.
        return ['perm_crear', 'perm_editar'];
    }

    /**
     * Ruta del grupo con control de permisos que no tiene módulo asignado.
     * Con permisos.denegar_sin_mapeo = false se deja pasar (comportamiento
     * anterior) pero se registra una vez por día y ruta, para completar el mapa
     * antes de pasar a "negar por defecto".
     */
    private function sinMapeo(Request $request, Closure $next)
    {
        $ruta = optional($request->route())->getName() ?: $request->path();
        if (Cache::add('permisos_sin_mapeo:' . $ruta, 1, now()->addDay())) {
            Log::warning('[permisos] Ruta sin módulo asignado: ' . $request->method() . ' ' . $request->path() . " ({$ruta})");
        }
        if (config('permisos.denegar_sin_mapeo')) {
            abort(403, 'Esta pantalla no tiene un módulo asignado en Roles. Consulte al administrador.');
        }
        return $next($request);
    }

    private function catalogo(): array
    {
        return Permisos::catalogo();
    }
}
