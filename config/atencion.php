<?php

/*
| Áreas del canal de atención a padres. "modulo" es el submódulo de Roles que
| permite ver y responder las consultas de esa área (ver = leer, crear =
| responder, editar = cerrar o derivar a otra área).
*/
return [
    'areas' => [
        'SECRETARIA'     => ['nombre' => 'Secretaría', 'icono' => 'fas fa-user-tie', 'modulo' => 'atencion.secretaria',
                             'ayuda' => 'Inscripciones, certificados, documentos, datos del estudiante'],
        'CAJA'           => ['nombre' => 'Caja y pagos', 'icono' => 'fas fa-cash-register', 'modulo' => 'atencion.caja',
                             'ayuda' => 'Mensualidades, recibos, comprobantes, descuentos'],
        'DIRECCION'      => ['nombre' => 'Dirección', 'icono' => 'fas fa-school', 'modulo' => 'atencion.direccion',
                             'ayuda' => 'Consultas académicas, convivencia, reclamos'],
        'PSICOPEDAGOGIA' => ['nombre' => 'Psicopedagogía', 'icono' => 'fas fa-brain', 'modulo' => 'atencion.psicopedagogia',
                             'ayuda' => 'Orientación, conducta, acompañamiento'],
        'DOCENTE'        => ['nombre' => 'Docentes del curso', 'icono' => 'fas fa-chalkboard-teacher', 'modulo' => 'atencion.docente',
                             'ayuda' => 'La ven los profesores que dictan clases en el curso de su hijo/a'],
    ],
    // Cada cuántos segundos se refrescan la bandeja y el hilo abiertos
    'refresco_segundos' => 30,
];
