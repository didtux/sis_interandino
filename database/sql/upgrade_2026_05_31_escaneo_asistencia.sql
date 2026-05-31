-- =====================================================================
-- Upgrade: App de escaneo de asistencia (rol Encargado Escaneo + auditoría)
-- Fecha: 2026-05-31
-- =====================================================================

-- 1) Rol dedicado a la app de escaneo (solo para esa app).
INSERT INTO rol_roles (rol_nombre, rol_descripcion, rol_visible, rol_fecha)
SELECT 'Encargado Escaneo', 'Acceso exclusivo a la app de escaneo de asistencia (lector QR).', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM rol_roles WHERE rol_nombre = 'Encargado Escaneo');

-- 2) Columnas de auditoría en colegio_asistencia: quién registró y por qué medio.
--    (nullable para no romper inserciones existentes que no las envían)
ALTER TABLE colegio_asistencia
    ADD COLUMN IF NOT EXISTS asis_usuario INT NULL DEFAULT NULL COMMENT 'us_id del usuario que registró (auditoría)',
    ADD COLUMN IF NOT EXISTS asis_origen VARCHAR(20) NULL DEFAULT NULL COMMENT 'QR | MANUAL | etc.';

-- 3) Permitir el tipo de entidad 'escaneo' en usuarios (para el redirect de login).
ALTER TABLE rol_usuarios
    MODIFY COLUMN us_entidad_tipo ENUM('admin','docente','padre','chofer','escaneo') NULL;

-- 4) Usuario de escaneo.
--    El password se setea desde la aplicación (Hash::make) para mantener el hash de Laravel.
--    Ejemplo (ajustar hash con bcrypt de Laravel):
--    INSERT INTO rol_usuarios (us_codigo, rol_id, us_ci, us_nombres, us_apellidos, us_user, us_pass, us_visible, us_entidad_tipo)
--    SELECT 'ESCANEO01', (SELECT rol_id FROM rol_roles WHERE rol_nombre='Encargado Escaneo'),
--           'ESCANEO01', 'Encargado', 'Escaneo', 'escaneo', '<bcrypt-hash>', 1, 'escaneo'
--    WHERE NOT EXISTS (SELECT 1 FROM rol_usuarios WHERE us_user='escaneo');
