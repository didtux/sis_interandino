-- =====================================================
-- Tablas de agrupación de materias por campo/área
-- Ejecutar en producción
-- =====================================================

CREATE TABLE IF NOT EXISTS colegio_materia_grupos (
    grupo_id INT(7) NOT NULL AUTO_INCREMENT,
    grupo_nombre VARCHAR(100) NOT NULL,
    grupo_estado TINYINT(1) DEFAULT 1,
    grupo_fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grupo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS colegio_materia_grupo_detalle (
    detalle_id INT(7) NOT NULL AUTO_INCREMENT,
    grupo_id INT(7) NOT NULL,
    mat_codigo VARCHAR(14) NOT NULL,
    detalle_orden INT DEFAULT 0,
    PRIMARY KEY (detalle_id),
    KEY idx_grupo (grupo_id),
    KEY idx_materia (mat_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos iniciales
INSERT IGNORE INTO colegio_materia_grupos (grupo_nombre) VALUES
('COMUNIDAD Y SOCIEDAD'),
('CIENCIA Y TECNOLOGÍA'),
('VIDA TIERRA Y TERRITORIO'),
('COSMOS Y PENSAMIENTO');
