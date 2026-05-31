-- Agenda: curso_codigo / prof_codigo / est_codigo pasan a NULLABLE.
-- El formulario de agenda trata estos campos como opcionales (notificación general
-- o sin curso explícito). Antes eran NOT NULL y rompían con:
--   SQLSTATE[23000] 1048 La columna 'curso_codigo' no puede ser nula.
-- El controlador ahora deriva curso_codigo del estudiante seleccionado cuando existe.

ALTER TABLE agenda MODIFY curso_codigo varchar(50) NULL DEFAULT NULL;
ALTER TABLE agenda MODIFY prof_codigo  varchar(14) NULL DEFAULT NULL;
ALTER TABLE agenda MODIFY est_codigo   varchar(14) NULL DEFAULT NULL;
