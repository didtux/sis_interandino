-- Lista de observados: permitir registrar estudiantes que NO están en el sistema
-- (aún no inscritos). Se guardan por nombre + CI + curso de referencia y se bloquea
-- su inscripción comparando el CI al momento de inscribir.

ALTER TABLE estudiantes_observados
  MODIFY est_codigo varchar(20) NULL DEFAULT NULL,
  ADD COLUMN obs_estudiante_nombre varchar(150) NULL DEFAULT NULL AFTER est_codigo,
  ADD COLUMN obs_estudiante_ci     varchar(30)  NULL DEFAULT NULL AFTER obs_estudiante_nombre,
  ADD COLUMN obs_curso_texto       varchar(80)  NULL DEFAULT NULL AFTER obs_estudiante_ci;
