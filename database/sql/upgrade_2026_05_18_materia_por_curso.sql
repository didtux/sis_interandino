-- ════════════════════════════════════════════════════════════════════
-- Fase 3 — Configuración de campo / orden / promediable POR CURSO
-- Aplicar UNA sola vez.
-- Tablas existentes están en latin1 → mantenemos misma collation
-- para evitar conflictos de charset en los JOIN.
-- ════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `colegio_materia_curso`;

CREATE TABLE `colegio_materia_curso` (
  `matc_id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cur_codigo`       VARCHAR(20)     NOT NULL,
  `mat_codigo`       VARCHAR(20)     NOT NULL,
  `matc_campo`       VARCHAR(100)    NULL  COMMENT 'Área / Campo del Ministerio para ese curso',
  `matc_orden`       INT             NOT NULL DEFAULT 999  COMMENT 'Orden de aparición en boletín/centralizador',
  `matc_promediable` TINYINT(1)      NOT NULL DEFAULT 0    COMMENT '1 = suma al promedio del campo',
  `matc_estado`      TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`matc_id`),
  UNIQUE KEY `uk_curso_materia` (`cur_codigo`, `mat_codigo`),
  KEY `idx_campo` (`matc_campo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ─── Backfill: hereda el campo/orden/promediable global por cada (curso, materia) ───
INSERT IGNORE INTO `colegio_materia_curso` (`cur_codigo`, `mat_codigo`, `matc_campo`, `matc_orden`, `matc_promediable`, `matc_estado`)
SELECT
  cm.cur_codigo,
  cm.mat_codigo,
  m.mat_campo,
  COALESCE(m.mat_orden, 999),
  COALESCE(m.mat_promediable, 0),
  1
FROM `colegio_curso_materia` cm
INNER JOIN `colegio_materias` m ON m.mat_codigo = cm.mat_codigo
WHERE cm.curmat_estado = 1;

-- ─── Reglas de promediable según gestión (documento p.3) ───
-- Primero reset: nada promedia.
UPDATE `colegio_materia_curso` SET `matc_promediable` = 0;

-- ─── INICIAL (10 materias): LENGUAJE - INGLÉS - AYMARA promedian ───
UPDATE `colegio_materia_curso` mc
INNER JOIN `colegio_cursos` c     ON c.cur_codigo = mc.cur_codigo
INNER JOIN `colegio_materias` m   ON m.mat_codigo = mc.mat_codigo
SET mc.matc_promediable = 1
WHERE (c.cur_nivel LIKE '%INICIAL%' OR c.cur_nombre LIKE '%Sección%' OR c.cur_nombre LIKE '%PreKinder%' OR c.cur_nombre LIKE '%Kinder%')
  AND (m.mat_nombre LIKE 'LENGUAJE%' OR m.mat_nombre LIKE 'INGL%' OR m.mat_nombre LIKE 'AYMARA%');

-- ─── 1°-5° PRIMARIA (12 materias): LENGUAJE - INGLÉS - AYMARA promedian ───
UPDATE `colegio_materia_curso` mc
INNER JOIN `colegio_cursos` c   ON c.cur_codigo = mc.cur_codigo
INNER JOIN `colegio_materias` m ON m.mat_codigo = mc.mat_codigo
SET mc.matc_promediable = 1
WHERE c.cur_nivel LIKE '%PRIMARIA%'
  AND c.cur_nombre NOT LIKE '6%'
  AND (m.mat_nombre LIKE 'LENGUAJE%' OR m.mat_nombre LIKE 'INGL%' OR m.mat_nombre LIKE 'AYMARA%');

-- ─── 6° PRIMARIA (14 materias): LENGUAJE-INGLÉS-AYMARA + CS.NATURALES-QUÍMICA-FÍSICA ───
UPDATE `colegio_materia_curso` mc
INNER JOIN `colegio_cursos` c   ON c.cur_codigo = mc.cur_codigo
INNER JOIN `colegio_materias` m ON m.mat_codigo = mc.mat_codigo
SET mc.matc_promediable = 1
WHERE c.cur_nivel LIKE '%PRIMARIA%'
  AND c.cur_nombre LIKE '6%'
  AND (
    m.mat_nombre LIKE 'LENGUAJE%'
    OR m.mat_nombre LIKE 'INGL%'
    OR m.mat_nombre LIKE 'AYMARA%'
    OR m.mat_nombre LIKE 'CIENCIAS NATURALES%'
    OR m.mat_nombre LIKE 'CS%NATURALES%'
    OR m.mat_nombre LIKE 'QU%MICA%'
    OR m.mat_nombre LIKE 'F%SICA%'
  );

-- ─── 1°-2° SECUNDARIA (15 materias): LENGUAJE-AYMARA + BIOLOGÍA-FÍSICA-QUÍMICA ───
UPDATE `colegio_materia_curso` mc
INNER JOIN `colegio_cursos` c   ON c.cur_codigo = mc.cur_codigo
INNER JOIN `colegio_materias` m ON m.mat_codigo = mc.mat_codigo
SET mc.matc_promediable = 1
WHERE c.cur_nivel LIKE '%SECUNDARIA%'
  AND (c.cur_nombre LIKE '1%' OR c.cur_nombre LIKE '2%')
  AND (
    m.mat_nombre LIKE 'LENGUAJE%'
    OR m.mat_nombre LIKE 'AYMARA%'
    OR m.mat_nombre LIKE 'BIOLOG%'
    OR m.mat_nombre LIKE 'F%SICA%'
    OR m.mat_nombre LIKE 'QU%MICA%'
  );

-- ─── 3°-6° SECUNDARIA (15 materias): LENGUAJE-AYMARA ───
UPDATE `colegio_materia_curso` mc
INNER JOIN `colegio_cursos` c   ON c.cur_codigo = mc.cur_codigo
INNER JOIN `colegio_materias` m ON m.mat_codigo = mc.mat_codigo
SET mc.matc_promediable = 1
WHERE c.cur_nivel LIKE '%SECUNDARIA%'
  AND (c.cur_nombre LIKE '3%' OR c.cur_nombre LIKE '4%' OR c.cur_nombre LIKE '5%' OR c.cur_nombre LIKE '6%')
  AND (m.mat_nombre LIKE 'LENGUAJE%' OR m.mat_nombre LIKE 'AYMARA%');

-- ─── Verificación (no destructiva): listado de promediables por curso ───
-- SELECT c.cur_nombre, m.mat_nombre, mc.matc_campo, mc.matc_promediable
-- FROM colegio_materia_curso mc
-- JOIN colegio_cursos c   ON c.cur_codigo = mc.cur_codigo
-- JOIN colegio_materias m ON m.mat_codigo = mc.mat_codigo
-- WHERE mc.matc_promediable = 1
-- ORDER BY c.cur_orden, mc.matc_orden;
