-- Módulo de Notas v2 - Columnas dinámicas

-- 1. Agregar cantidad de columnas a dimensiones
ALTER TABLE notas_config_dimensiones ADD COLUMN dimension_columnas TINYINT(2) NOT NULL DEFAULT 1 AFTER dimension_valor_max;

-- 2. Configurar columnas por defecto
UPDATE notas_config_dimensiones SET dimension_columnas=3 WHERE dimension_nombre='SER';
UPDATE notas_config_dimensiones SET dimension_columnas=2 WHERE dimension_nombre='SABER';
UPDATE notas_config_dimensiones SET dimension_columnas=1 WHERE dimension_nombre='HACER';
UPDATE notas_config_dimensiones SET dimension_columnas=1 WHERE dimension_nombre='AUTOEVALUACION';

-- 3. Tabla de detalle de notas (valores dinámicos por dimensión y columna)
CREATE TABLE IF NOT EXISTS colegio_notas_detalle (
    detalle_id INT(7) NOT NULL AUTO_INCREMENT,
    nota_id INT(7) NOT NULL,
    dimension_id INT(7) NOT NULL,
    columna_num TINYINT(2) NOT NULL DEFAULT 1,
    detalle_valor DECIMAL(5,2) NULL DEFAULT 0,
    PRIMARY KEY (detalle_id),
    KEY idx_nota (nota_id),
    KEY idx_dimension (dimension_id),
    UNIQUE KEY uk_nota_dim_col (nota_id, dimension_id, columna_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
