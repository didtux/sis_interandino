-- =============================================
-- MÓDULO DE NOTAS - ESTRUCTURA COMPLETA
-- =============================================

-- 1. Configuración de periodos (trimestres/bimestres)
CREATE TABLE IF NOT EXISTS notas_config_periodos (
    periodo_id INT(7) NOT NULL AUTO_INCREMENT,
    periodo_nombre VARCHAR(50) NOT NULL COMMENT 'Ej: 1er Trimestre, 2do Trimestre',
    periodo_numero TINYINT(2) NOT NULL COMMENT 'Orden: 1, 2, 3...',
    periodo_fecha_inicio DATE NOT NULL,
    periodo_fecha_fin DATE NOT NULL,
    periodo_gestion YEAR NOT NULL,
    periodo_estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    periodo_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Configuración de dimensiones (SER, SABER, HACER, DECIDIR, AUTOEVALUACIÓN)
CREATE TABLE IF NOT EXISTS notas_config_dimensiones (
    dimension_id INT(7) NOT NULL AUTO_INCREMENT,
    dimension_nombre VARCHAR(50) NOT NULL COMMENT 'SER, SABER, HACER, DECIDIR, AUTOEVALUACIÓN',
    dimension_valor_max INT(3) NOT NULL COMMENT 'Valor máximo: 10, 45, 40, 5...',
    dimension_orden TINYINT(2) NOT NULL DEFAULT 0,
    dimension_gestion YEAR NOT NULL,
    dimension_estado TINYINT(1) NOT NULL DEFAULT 1,
    dimension_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (dimension_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Rediseñar tabla de notas
DROP TABLE IF EXISTS colegio_notas;

CREATE TABLE colegio_notas (
    nota_id INT(7) NOT NULL AUTO_INCREMENT,
    periodo_id INT(7) NOT NULL,
    curmatdoc_id INT(7) NOT NULL COMMENT 'Referencia a curso-materia-docente',
    est_codigo VARCHAR(14) NOT NULL,
    -- SER (subdimensiones)
    nota_ser_respeto DECIMAL(5,2) NULL DEFAULT 0,
    nota_ser_responsabilidad DECIMAL(5,2) NULL DEFAULT 0,
    nota_ser_puntualidad DECIMAL(5,2) NULL DEFAULT 0,
    nota_ser_promedio DECIMAL(5,2) NULL DEFAULT 0,
    -- SABER (subdimensiones)
    nota_saber_parcial DECIMAL(5,2) NULL DEFAULT 0,
    nota_saber_examen DECIMAL(5,2) NULL DEFAULT 0,
    nota_saber_promedio DECIMAL(5,2) NULL DEFAULT 0,
    -- HACER
    nota_hacer_promedio DECIMAL(5,2) NULL DEFAULT 0,
    -- AUTOEVALUACIÓN
    nota_autoevaluacion DECIMAL(5,2) NULL DEFAULT 0,
    -- PROMEDIO TRIMESTRAL
    nota_promedio_trimestral DECIMAL(5,2) NULL DEFAULT 0,
    -- Estado de aprobación: 0=Borrador, 1=Enviado, 2=Aprobado, 3=Rechazado
    nota_estado TINYINT(1) NOT NULL DEFAULT 0,
    nota_observacion TEXT NULL,
    nota_fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nota_fecha_aprobacion DATETIME NULL,
    nota_aprobado_por INT(7) NULL,
    PRIMARY KEY (nota_id),
    KEY idx_periodo (periodo_id),
    KEY idx_curmatdoc (curmatdoc_id),
    KEY idx_estudiante (est_codigo),
    UNIQUE KEY uk_nota_unica (periodo_id, curmatdoc_id, est_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Datos iniciales de dimensiones (gestión 2026)
INSERT INTO notas_config_dimensiones (dimension_nombre, dimension_valor_max, dimension_orden, dimension_gestion) VALUES
('SER', 10, 1, 2026),
('SABER', 45, 2, 2026),
('HACER', 40, 3, 2026),
('AUTOEVALUACIÓN', 5, 4, 2026);

-- 5. Datos iniciales de periodos (gestión 2026)
INSERT INTO notas_config_periodos (periodo_nombre, periodo_numero, periodo_fecha_inicio, periodo_fecha_fin, periodo_gestion) VALUES
('1er Trimestre', 1, '2026-02-02', '2026-05-08', 2026),
('2do Trimestre', 2, '2026-05-11', '2026-08-14', 2026),
('3er Trimestre', 3, '2026-08-17', '2026-11-20', 2026);
