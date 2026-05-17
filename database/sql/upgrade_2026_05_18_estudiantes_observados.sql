-- ════════════════════════════════════════════════════════════════════
-- Fase 7 — Lista negra de estudiantes para inscripción.
-- Solo la dirección puede crear/quitar. Histórico por gestión.
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `estudiantes_observados` (
  `obs_id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `est_codigo`       VARCHAR(20)     NOT NULL,
  `obs_gestion`      INT             NOT NULL,
  `obs_motivo_tipo`  VARCHAR(30)     NOT NULL COMMENT 'PENSIONES, FALTAS, DISCIPLINARIO, OTRO',
  `obs_motivo`       VARCHAR(255)    NOT NULL,
  `obs_registrado_por`        INT    NULL,
  `obs_registrado_por_nombre` VARCHAR(150) NULL,
  `obs_fecha_registro`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `obs_liberado_por`          INT    NULL,
  `obs_liberado_por_nombre`   VARCHAR(150) NULL,
  `obs_fecha_liberacion`      DATETIME NULL,
  `obs_motivo_liberacion`     VARCHAR(255) NULL,
  `obs_activo`                TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`obs_id`),
  KEY `idx_est_gestion` (`est_codigo`, `obs_gestion`, `obs_activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
