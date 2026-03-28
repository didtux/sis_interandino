-- Agregar campos RUDE y celular en estudiantes
ALTER TABLE colegio_estudiantes ADD COLUMN est_rude VARCHAR(30) NULL AFTER est_ueprocedencia;
ALTER TABLE colegio_estudiantes ADD COLUMN est_celular VARCHAR(20) NULL AFTER est_rude;
