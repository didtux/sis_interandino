-- Buscar inscripción de MURGUIA DELGADO DIEGO FABRICIO
SELECT 
    i.insc_codigo,
    i.insc_fecha,
    e.est_codigo,
    e.est_nombres,
    e.est_apellidos,
    e.est_ci,
    c.cur_nombre,
    i.insc_monto_total,
    i.insc_monto_pagado,
    i.insc_saldo,
    i.insc_estado
FROM inscripciones i
INNER JOIN colegio_estudiantes e ON i.est_codigo = e.est_codigo
INNER JOIN colegio_cursos c ON i.cur_codigo = c.cur_codigo
WHERE e.est_ci LIKE '%17591895%'
   OR e.est_nombres LIKE '%DIEGO%'
   OR e.est_apellidos LIKE '%MURGUIA%';
