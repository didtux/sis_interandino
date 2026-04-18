-- Módulo de Asistencia de Transporte (Conductor)
-- Registra la asistencia de estudiantes en la ida y vuelta del transporte escolar

CREATE TABLE IF NOT EXISTS transporte_asistencia (
    tasis_id        INT AUTO_INCREMENT PRIMARY KEY,
    tasis_codigo    VARCHAR(30) NOT NULL,
    ruta_codigo     VARCHAR(30) NOT NULL,
    est_codigo      VARCHAR(30) NOT NULL,
    tasis_fecha     DATE NOT NULL,
    tasis_tipo      ENUM('IDA','VUELTA') NOT NULL COMMENT 'Ida al colegio o vuelta a casa',
    tasis_hora      TIME NOT NULL,
    tasis_observacion VARCHAR(500) DEFAULT NULL,
    tasis_registrado_por INT DEFAULT NULL,
    tasis_fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ruta_fecha (ruta_codigo, tasis_fecha),
    INDEX idx_est_fecha (est_codigo, tasis_fecha),
    UNIQUE KEY uk_asistencia (ruta_codigo, est_codigo, tasis_fecha, tasis_tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
