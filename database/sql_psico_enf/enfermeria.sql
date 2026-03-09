-- Tabla para enfermería
CREATE TABLE IF NOT EXISTS enfermeria_registros (
    enf_id INT AUTO_INCREMENT PRIMARY KEY,
    enf_codigo VARCHAR(20) NOT NULL UNIQUE,
    enf_tipo_persona ENUM('ESTUDIANTE', 'DOCENTE') DEFAULT 'ESTUDIANTE',
    est_codigo VARCHAR(14),
    doc_codigo VARCHAR(14),
    enf_fecha DATE NOT NULL,
    enf_hora TIME NOT NULL,
    enf_dx_detalle VARCHAR(100) NOT NULL,
    enf_medicamentos TEXT,
    enf_observaciones TEXT,
    enf_estado TINYINT(1) DEFAULT 1,
    enf_registrado_por VARCHAR(20),
    enf_fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estudiante (est_codigo),
    INDEX idx_docente (doc_codigo),
    INDEX idx_fecha (enf_fecha),
    INDEX idx_estado (enf_estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
