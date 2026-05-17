-- ════════════════════════════════════════════════════════════════════
-- Kardex de Estudiantes — anotaciones del docente sobre cada alumno.
-- Visible para: docente (los que enseña), padre (sus hijos), admin (todo).
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `estudiante_kardex` (
  `ek_id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `est_codigo`          VARCHAR(20)  NOT NULL,
  `cur_codigo`          VARCHAR(20)  NULL  COMMENT 'Curso al momento del registro',
  `ek_fecha`            DATE         NOT NULL,
  `ek_tipo`             VARCHAR(30)  NOT NULL  COMMENT 'ACADEMICO/CONDUCTUAL/FELICITACION/OBSERVACION/COMPROMISO',
  `ek_categoria`        VARCHAR(20)  NULL      COMMENT 'POSITIVO/NEUTRO/NEGATIVO',
  `ek_titulo`           VARCHAR(150) NOT NULL,
  `ek_descripcion`      TEXT         NULL,
  `ek_acuerdo`          TEXT         NULL      COMMENT 'Compromiso/acuerdo con el padre',
  `ek_archivo`          VARCHAR(255) NULL,
  `ek_visible_padre`    TINYINT(1)   NOT NULL DEFAULT 1,
  `ek_visto_padre`      TINYINT(1)   NOT NULL DEFAULT 0,
  `ek_visto_padre_at`   DATETIME     NULL,
  `doc_codigo`          VARCHAR(20)  NULL      COMMENT 'Docente que la registró',
  `mat_codigo`          VARCHAR(20)  NULL      COMMENT 'Materia (opcional)',
  `ek_registrado_por`   BIGINT UNSIGNED NOT NULL,
  `ek_estado`           TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`ek_id`),
  KEY `idx_est`    (`est_codigo`),
  KEY `idx_curso`  (`cur_codigo`),
  KEY `idx_doc`    (`doc_codigo`),
  KEY `idx_fecha`  (`ek_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
