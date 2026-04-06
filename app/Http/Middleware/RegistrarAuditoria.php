<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Auditoria;

class RegistrarAuditoria
{
    private $moduloMap = [
        'estudiantes' => 'Estudiantes',
        'cursos' => 'Cursos',
        'docentes' => 'Docentes',
        'materias' => 'Materias',
        'padres' => 'Padres de Familia',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'inscripciones' => 'Inscripciones',
        'pagos' => 'Mensualidades',
        'ventas' => 'Ventas',
        'categorias' => 'Categorías',
        'productos' => 'Productos',
        'proveedores' => 'Proveedores',
        'movimientos' => 'Movimientos',
        'servicios' => 'Servicios',
        'pagos-servicios' => 'Pagos Servicios',
        'agenda' => 'Agenda',
        'psicopedagogia' => 'Psicopedagogía',
        'enfermeria' => 'Enfermería',
        'vehiculos' => 'Vehículos',
        'choferes' => 'Choferes',
        'rutas' => 'Rutas',
        'asignaciones-transporte' => 'Asignaciones Transporte',
        'pagos-transporte' => 'Pagos Transporte',
        'estudiantes-rutas' => 'Estudiantes Rutas',
        'asistencias' => 'Asistencias',
        'asistencia-config' => 'Config. Asistencia',
        'notas' => 'Notas',
        'asistencia-clases' => 'Asistencia Clases',
        'descuentos' => 'Descuentos',
    ];

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = auth()->user();
            if (!$user) return $response;

            $method = $request->method();
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) return $response;

            // Solo registrar si la respuesta fue exitosa (redirect o 2xx)
            $status = $response->getStatusCode();
            if ($status >= 400) return $response;

            $segmento = $request->segment(1);
            $modulo = $this->moduloMap[$segmento] ?? $segmento;

            // Determinar acción
            $accion = match(true) {
                $method === 'DELETE' => 'eliminar',
                in_array($method, ['PUT', 'PATCH']) => 'editar',
                $method === 'POST' => 'crear',
                default => null
            };

            if (!$accion) return $response;

            // Determinar descripción
            $seg2 = $request->segment(2);
            $seg3 = $request->segment(3);
            $descripcion = ucfirst($accion) . ' en ' . $modulo;

            if ($seg3 === 'anular' || str_contains($request->url(), 'anular')) {
                $accion = 'eliminar';
                $descripcion = 'Anular registro en ' . $modulo;
            } elseif (str_contains($request->url(), 'crear-usuario')) {
                $descripcion = 'Crear usuario desde ' . $modulo;
            } elseif (str_contains($request->url(), 'vincular')) {
                $descripcion = 'Vincular estudiante en ' . $modulo;
            } elseif (str_contains($request->url(), 'desvincular')) {
                $accion = 'eliminar';
                $descripcion = 'Desvincular estudiante en ' . $modulo;
            } elseif (str_contains($request->url(), 'guardar')) {
                $descripcion = 'Guardar datos en ' . $modulo;
            } elseif (str_contains($request->url(), 'aprobar')) {
                $descripcion = 'Aprobar/Rechazar en ' . $modulo;
            }

            $registroId = $seg2 && is_numeric($seg2) ? $seg2 : ($seg2 ?? null);

            // Datos enviados (limpiar sensibles)
            $datos = $request->except(['_token', '_method', 'password', 'us_pass']);

            Auditoria::create([
                'audit_usuario_id' => $user->us_id,
                'audit_usuario_nombre' => $user->us_nombres . ' ' . ($user->us_apellidos ?? ''),
                'audit_accion' => $accion,
                'audit_modulo' => $modulo,
                'audit_descripcion' => $descripcion,
                'audit_registro_id' => $registroId,
                'audit_datos_anteriores' => null,
                'audit_datos_nuevos' => !empty($datos) ? $datos : null,
                'audit_ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // No interrumpir la operación si falla la auditoría
            \Log::error('Error auditoría: ' . $e->getMessage());
        }

        return $response;
    }
}
