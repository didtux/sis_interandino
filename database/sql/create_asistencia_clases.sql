-- Asistencia de clases (registro por docente en su materia)
CREATE TABLE IF NOT EXISTS notas_asistencia_clases (
    asiscl_id INT(7) NOT NULL AUTO_INCREMENT,
    curmatdoc_id INT(7) NOT NULL COMMENT 'Curso-Materia-Docente',
    periodo_id INT(7) NOT NULL,
    est_codigo VARCHAR(14) NOT NULL,
    asiscl_fecha DATE NOT NULL,
    asiscl_estado ENUM('P','A','F','L') NOT NULL DEFAULT 'P' COMMENT 'P=Presente, A=Atraso, F=Falta, L=Licencia',
    asiscl_observacion VARCHAR(255) NULL,
    asiscl_registrado_por INT(7) NOT NULL,
    asiscl_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (asiscl_id),
    UNIQUE KEY uk_asistencia (curmatdoc_id, est_codigo, asiscl_fecha),
    KEY idx_periodo (periodo_id),
    KEY idx_fecha (asiscl_fecha),
    KEY idx_estudiante (est_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
