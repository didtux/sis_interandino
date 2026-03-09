-- Tabla para casos de psicopedagogía
CREATE TABLE IF NOT EXISTS psicopedagogia_casos (
    psico_id INT AUTO_INCREMENT PRIMARY KEY,
    psico_codigo VARCHAR(20) NOT NULL UNIQUE,
    est_codigo VARCHAR(14) NOT NULL,
    psico_fecha DATE NOT NULL,
    psico_caso TEXT NOT NULL,
    psico_solucion TEXT,
    psico_acuerdo TEXT,
    psico_tipo_acuerdo ENUM('VERBAL', 'ESCRITO', 'NINGUNO') DEFAULT 'NINGUNO',
    psico_observaciones TEXT,
    psico_estado TINYINT(1) DEFAULT 1,
    psico_registrado_por VARCHAR(20),
    psico_fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estudiante (est_codigo),
    INDEX idx_fecha (psico_fecha),
    INDEX idx_estado (psico_estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
