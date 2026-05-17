-- ════════════════════════════════════════════════════════════════════
-- Fase 8 — Alerta parcial trimestre (advertencia de reprobación).
-- El docente marca naranja, la directora eleva a rosa para enviar
-- al padre.
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `nota_alerta_parcial` (
  `alerta_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `est_codigo`       VARCHAR(20)     NOT NULL,
  `mat_codigo`       VARCHAR(20)     NOT NULL,
  `cur_codigo`       VARCHAR(20)     NOT NULL,
  `periodo_id`       INT             NOT NULL,
  `alerta_gestion`   INT             NOT NULL,
  -- Marca del docente (naranja)
  `marcado_docente`           TINYINT(1)   NOT NULL DEFAULT 0,
  `marcado_docente_por`       INT          NULL,
  `marcado_docente_nombre`    VARCHAR(150) NULL,
  `marcado_docente_fecha`     DATETIME     NULL,
  -- Marca elevada por la directora (rosa)
  `marcado_director`          TINYINT(1)   NOT NULL DEFAULT 0,
  `marcado_director_por`      INT          NULL,
  `marcado_director_nombre`   VARCHAR(150) NULL,
  `marcado_director_fecha`    DATETIME     NULL,
  `alerta_observacion`        VARCHAR(255) NULL,
  PRIMARY KEY (`alerta_id`),
  UNIQUE KEY `uk_est_mat_periodo` (`est_codigo`, `mat_codigo`, `periodo_id`),
  KEY `idx_curso_periodo` (`cur_codigo`, `periodo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
