-- Módulo Asistencia Actividades

-- 1. Actividades (evento principal)
CREATE TABLE IF NOT EXISTS actividades (
    act_id INT(7) NOT NULL AUTO_INCREMENT,
    act_nombre VARCHAR(200) NOT NULL,
    act_descripcion TEXT NULL,
    act_fecha DATE NOT NULL,
    act_estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    act_creado_por INT(7) NOT NULL,
    act_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (act_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categorías de cada actividad
CREATE TABLE IF NOT EXISTS actividades_categorias (
    actcat_id INT(7) NOT NULL AUTO_INCREMENT,
    act_id INT(7) NOT NULL,
    actcat_nombre VARCHAR(200) NOT NULL,
    actcat_descripcion TEXT NULL,
    actcat_estado TINYINT(1) NOT NULL DEFAULT 1,
    actcat_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (actcat_id),
    KEY idx_actividad (act_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Registros de asistencia por categoría
CREATE TABLE IF NOT EXISTS actividades_registros (
    actreg_id INT(7) NOT NULL AUTO_INCREMENT,
    actcat_id INT(7) NOT NULL,
    est_codigo VARCHAR(14) NOT NULL,
    actreg_hora TIME NULL,
    actreg_observacion VARCHAR(255) NULL,
    actreg_registrado_por INT(7) NOT NULL,
    actreg_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (actreg_id),
    UNIQUE KEY uk_registro (actcat_id, est_codigo),
    KEY idx_estudiante (est_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Registrar módulo en roles (ignorar si ya existe)
INSERT IGNORE INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
VALUES ('Asist. Actividades', 'actividades-asistencia', 'fas fa-calendar-check', NULL, 40, 1);
