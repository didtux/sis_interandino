-- ════════════════════════════════════════════════════════════════════
-- Fase 2 — Contador de descargas/impresiones de boletines + QR
-- Aplicar UNA sola vez sobre la BD.
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `boletines_descargas` (
  `descarga_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `descarga_token`     VARCHAR(64)     NOT NULL,
  `est_codigo`         VARCHAR(20)     NOT NULL,
  `descarga_gestion`   INT             NOT NULL,
  `descarga_trimestre` TINYINT         NULL COMMENT 'NULL = anual; 1/2/3 = trimestre',
  `descargado_por`     INT             NULL COMMENT 'us_id del usuario que descargó',
  `descargado_por_nombre` VARCHAR(150) NULL,
  `descarga_fecha`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `descarga_ip`        VARCHAR(45)     NULL,
  `descarga_user_agent` VARCHAR(255)   NULL,
  `descarga_numero_copia` INT          NOT NULL DEFAULT 1 COMMENT 'Nro. correlativo por (est, gestion, trimestre)',
  `descarga_cobrable`  TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '0=primera (gratis), 1=reimpresión cobrable',
  `descarga_servicio_id` BIGINT        NULL COMMENT 'FK opcional a servicio facturable',
  `descarga_observacion` VARCHAR(255)  NULL,
  PRIMARY KEY (`descarga_id`),
  UNIQUE KEY `uk_descarga_token` (`descarga_token`),
  KEY `idx_est_gestion_trim` (`est_codigo`, `descarga_gestion`, `descarga_trimestre`),
  KEY `idx_descarga_fecha` (`descarga_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (Opcional) Catálogo de servicios extra para vincular cobros futuros.
-- Si ya tienes una tabla equivalente en Pagos, ignora esta sección.
CREATE TABLE IF NOT EXISTS `servicios_extra` (
  `servicio_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicio_codigo` VARCHAR(30)     NOT NULL,
  `servicio_nombre` VARCHAR(100)    NOT NULL,
  `servicio_precio` DECIMAL(10,2)   NOT NULL DEFAULT 0,
  `servicio_estado` TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`servicio_id`),
  UNIQUE KEY `uk_servicio_codigo` (`servicio_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `servicios_extra` (`servicio_codigo`, `servicio_nombre`, `servicio_precio`)
VALUES
  ('REIMPR_BOLETIN', 'Reimpresión de boletín de calificaciones', 10.00);
