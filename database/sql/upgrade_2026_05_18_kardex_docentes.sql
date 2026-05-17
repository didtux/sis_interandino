-- ════════════════════════════════════════════════════════════════════
-- Fase 9 — Kardex Docentes:
--   1) docente_asistencia: registros de asistencia (vía QR o manual)
--   2) docente_kardex: documentos solicitados/entregados (PDC, exámenes, etc.)
--   3) docente_disciplinario: incidencias disciplinarias
-- ════════════════════════════════════════════════════════════════════

-- 1) Asistencia de docentes
CREATE TABLE IF NOT EXISTS `docente_asistencia` (
  `dasist_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_codigo`    VARCHAR(20) NOT NULL,
  `dasist_fecha`  DATE        NOT NULL,
  `dasist_hora`   TIME        NOT NULL,
  `dasist_tipo`   ENUM('ENTRADA','SALIDA','UNICO') NOT NULL DEFAULT 'UNICO',
  `dasist_origen` ENUM('QR','MANUAL') NOT NULL DEFAULT 'MANUAL',
  `dasist_observacion` VARCHAR(255) NULL,
  `dasist_registrado_por` INT NULL,
  PRIMARY KEY (`dasist_id`),
  KEY `idx_doc_fecha` (`doc_codigo`, `dasist_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Kardex de documentos
CREATE TABLE IF NOT EXISTS `docente_kardex` (
  `kdx_id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_codigo`           VARCHAR(20)  NOT NULL,
  `kdx_tipo_documento`   VARCHAR(50)  NOT NULL COMMENT 'EXAMENES, PDC, CUADERNO_PEDAGOGICO, PLAN_ANUAL, OTRO',
  `kdx_titulo`           VARCHAR(150) NOT NULL,
  `kdx_descripcion`      VARCHAR(500) NULL,
  `kdx_fecha_solicitud`  DATE         NOT NULL,
  `kdx_fecha_entrega`    DATE         NULL  COMMENT 'fecha pactada',
  `kdx_fecha_recibido`   DATE         NULL  COMMENT 'fecha real de recepción',
  `kdx_estado`           ENUM('PENDIENTE','ENTREGADO','OBSERVADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
  `kdx_archivo`          VARCHAR(255) NULL,
  `kdx_creado_por`       INT          NULL,
  `kdx_creado_fecha`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `kdx_observacion`      VARCHAR(500) NULL,
  PRIMARY KEY (`kdx_id`),
  KEY `idx_doc` (`doc_codigo`),
  KEY `idx_estado_fecha` (`kdx_estado`, `kdx_fecha_entrega`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Disciplinario
CREATE TABLE IF NOT EXISTS `docente_disciplinario` (
  `disc_id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_codigo`      VARCHAR(20)  NOT NULL,
  `disc_fecha`      DATE         NOT NULL,
  `disc_tipo`       VARCHAR(30)  NOT NULL COMMENT 'FALTA, UNIFORME, ATRASO, ACADEMICO, OTRO',
  `disc_gravedad`   ENUM('LEVE','MEDIA','GRAVE') NOT NULL DEFAULT 'LEVE',
  `disc_descripcion` VARCHAR(500) NOT NULL,
  `disc_evidencia`  VARCHAR(255) NULL,
  `disc_registrado_por` INT NULL,
  `disc_registrado_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`disc_id`),
  KEY `idx_doc_fecha` (`doc_codigo`, `disc_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
