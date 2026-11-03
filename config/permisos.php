<?php

/*
|--------------------------------------------------------------------------
| Capacidades especiales
|--------------------------------------------------------------------------
| Decisiones que no son "ver/crear/editar/eliminar un módulo". Antes estaban
| escritas en el código como listas de roles (in_array($rol, [1, 9, 10, 11]))
| y ningún tilde de la pantalla Roles podía cambiarlas: un rol nuevo recibía
| "Acceso Denegado" aunque tuviera todo marcado.
|
| Ahora se asignan desde Roles → "Capacidades especiales" (tabla
| rol_capacidades). 'roles_iniciales' son los roles que tenían el acceso en
| el código: el script upgrade_2026_11_rol_capacidades.sql se los otorga para
| que nadie pierda nada. El Administrador (rol 1) tiene todas siempre.
*/

return [

    'capacidades' => [
        'observados.autorizar' => [
            'grupo' => 'Inscripciones',
            'nombre' => 'Autorizar inscripción de un estudiante observado',
            'descripcion' => 'Levanta el bloqueo de la lista de observados al inscribir o dar de alta.',
            'roles_iniciales' => [9, 10],
        ],
        'observados.gestionar' => [
            'grupo' => 'Inscripciones',
            'nombre' => 'Gestionar la lista de estudiantes observados',
            'descripcion' => 'Ver, agregar y liberar estudiantes de la lista de observados.',
            'roles_iniciales' => [9, 10, 11, 13],
        ],
        'alertas.direccion' => [
            'grupo' => 'Notas',
            'nombre' => 'Marcar advertencia parcial como dirección (rosa)',
            'descripcion' => 'Ver la matriz de advertencias de todos los cursos y marcar alertas de dirección.',
            'roles_iniciales' => [9, 10],
        ],
        'notas.aprobar' => [
            'grupo' => 'Notas',
            'nombre' => 'Aprobar o rechazar notas enviadas por los docentes',
            'descripcion' => '',
            'roles_iniciales' => [],
        ],
        'notas.editar_fuera_plazo' => [
            'grupo' => 'Notas',
            'nombre' => 'Editar notas fuera de plazo o ya aprobadas',
            'descripcion' => 'El cambio queda registrado en auditoría.',
            'roles_iniciales' => [],
        ],
        'notas.reportes' => [
            'grupo' => 'Notas',
            'nombre' => 'Generar reportes de notas de todos los cursos',
            'descripcion' => 'Centralizadores, reporte general y boletines de cualquier curso (pestaña Reportes).',
            'roles_iniciales' => [],
        ],
        'notas.configurar' => [
            'grupo' => 'Notas',
            'nombre' => 'Configurar periodos, dimensiones y recálculo de notas',
            'descripcion' => '',
            'roles_iniciales' => [],
        ],
        'boletin.reimpresiones' => [
            'grupo' => 'Notas',
            'nombre' => 'Ver y anular reimpresiones de boletines',
            'descripcion' => '',
            'roles_iniciales' => [9, 10, 11],
        ],
        'comunicados.gestionar' => [
            'grupo' => 'Docentes',
            'nombre' => 'Enviar comunicados a docentes y revisar entregas',
            'descripcion' => '',
            'roles_iniciales' => [9, 10, 11],
        ],
        'kardex_docente.gestionar' => [
            'grupo' => 'Docentes',
            'nombre' => 'Kardex docente: pedir y revisar documentación',
            'descripcion' => '',
            'roles_iniciales' => [9, 10, 11],
        ],
        'kardex_estudiante.direccion' => [
            'grupo' => 'Estudiantes',
            'nombre' => 'Kardex de estudiantes: ver y anotar sobre todos los estudiantes',
            'descripcion' => 'Sin esta capacidad, un docente sólo ve a los estudiantes de sus cursos.',
            'roles_iniciales' => [9, 10, 11, 12, 14],
        ],
        'gestion.activar' => [
            'grupo' => 'Gestión escolar',
            'nombre' => 'Activar la gestión siguiente (paso de año)',
            'descripcion' => 'Pasa a todo el sistema a la gestión nueva: cursos, listas, cobros y notas.',
            'roles_iniciales' => [],
        ],
        'gestion.preinscripcion' => [
            'grupo' => 'Gestión escolar',
            'nombre' => 'Abrir o cerrar la pre-inscripción de la gestión siguiente y fijar sus fechas',
            'descripcion' => 'Sin esta capacidad, en Gestiones se ven esos datos pero no se pueden cambiar.',
            'roles_iniciales' => [9, 10],
        ],
        'gestion.montos' => [
            'grupo' => 'Gestión escolar',
            'nombre' => 'Fijar el monto de la reserva de pre-inscripción',
            'descripcion' => 'Por curso (Tarifas), por gestión (Gestiones) y el monto por defecto (Parámetros).',
            'roles_iniciales' => [9, 13],
        ],
        'preinscripcion.cambiar_monto' => [
            'grupo' => 'Caja',
            'nombre' => 'Cobrar una reserva distinta del monto fijado',
            'descripcion' => 'Sin esta capacidad, caja cobra exactamente el monto de reserva que corresponde al curso.',
            'roles_iniciales' => [9, 13],
        ],
        'promocion.decidir' => [
            'grupo' => 'Gestión escolar',
            'nombre' => 'Fijar a mano el resultado de fin de año (promovido / reprobado)',
            'descripcion' => 'Corrige el resultado calculado; queda registrado quién y por qué.',
            'roles_iniciales' => [9, 10],
        ],
        'devoluciones.aprobar' => [
            'grupo' => 'Caja',
            'nombre' => 'Aprobar o rechazar devoluciones de dinero',
            'descripcion' => '',
            'roles_iniciales' => [9, 13],
        ],
        'auditoria.ver' => [
            'grupo' => 'Sistema',
            'nombre' => 'Ver la auditoría del sistema',
            'descripcion' => '',
            'roles_iniciales' => [],
        ],
    ],

    /*
    | Rutas del grupo con control de permisos que no tienen módulo asignado.
    | false: se dejan pasar y se registran en storage/logs (canal "permisos")
    |        para completar el mapa. true: se rechazan (negar por defecto).
    */
    'denegar_sin_mapeo' => env('PERMISOS_DENEGAR_SIN_MAPEO', false),

    /*
    | Qué significa cada casilla en los módulos donde no es obvio. Se muestra
    | debajo del nombre del módulo en la pantalla de Roles.
    */
    'ayuda_modulos' => [
        'gestiones'               => 'Crear = gestión siguiente · Editar = nombre y fechas de clases. Abrir la pre-inscripción, fijar montos y activar el paso de año son capacidades especiales (abajo).',
        'preinscripciones'        => 'Crear = pre-inscribir y cobrar la reserva · Editar = cambiar el curso destino y convertir en inscripción · Anular = anular. Cobrar otro monto: capacidad «Cobrar una reserva distinta».',
        'tarifas'                 => 'Editar = montos anuales y curso siguiente. La columna Reserva pide la capacidad «Fijar el monto de la reserva».',
        'promocion'               => 'Ver = resultados de fin de año · Crear o Editar = calcularlos. Corregirlos a mano: capacidad «Fijar a mano el resultado».',
        'atencion'                => 'Grupo del menú. Marque abajo qué áreas atiende el rol.',
        'atencion.secretaria'     => 'Ver = leer las consultas · Crear = responder · Editar = cerrar, reabrir o derivar a otra área.',
        'atencion.caja'           => 'Ver = leer las consultas · Crear = responder · Editar = cerrar, reabrir o derivar a otra área.',
        'atencion.direccion'      => 'Ver = leer las consultas · Crear = responder · Editar = cerrar, reabrir o derivar a otra área.',
        'atencion.psicopedagogia' => 'Ver = leer las consultas · Crear = responder · Editar = cerrar, reabrir o derivar a otra área.',
        'atencion.docente'        => 'Ver = leer · Crear = responder · Editar = cerrar o derivar. Un usuario docente sólo ve las consultas de los cursos donde dicta.',
    ],
];
