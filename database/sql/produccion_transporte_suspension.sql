-- Suspensión de servicio de transporte
-- Ejecutar en producción
ALTER TABLE transporte_estudiantes_rutas
    ADD COLUMN IF NOT EXISTS ter_suspendido TINYINT(1) DEFAULT 0 AFTER ter_estado,
    ADD COLUMN IF NOT EXISTS ter_suspendido_desde INT DEFAULT NULL COMMENT 'Mes desde el cual se suspendio (2-11)' AFTER ter_suspendido;
