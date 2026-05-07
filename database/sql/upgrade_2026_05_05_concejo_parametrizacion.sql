-- =====================================================================
-- UPGRADE 2026-05-05: Concejo Educativo, Parametrización y mejoras Notas
-- =====================================================================
-- Aplica al servidor los cambios equivalentes a las migraciones:
--   2026_05_05_000001 -> ALTER colegio_cursos
--   2026_05_05_000002 -> ALTER colegio_docentes
--   2026_05_05_000003 -> CREATE colegio_docente_horarios
--   2026_05_05_000004 -> CREATE sistema_configuracion
--   2026_05_05_000005 -> CREATE colegio_niveles
--   2026_05_05_000006 -> CREATE colegio_gestiones
--   2026_05_05_000007 -> INSERT módulos en rol_modulos
--
-- Compatible con MariaDB 10.0+ / MySQL 8.0+ (usa IF NOT EXISTS).
-- Si tu motor no soporta IF NOT EXISTS en ALTER COLUMN, comenta los que
-- ya existan o ejecuta sección por sección.
--
-- Recomendado: SELECT DATABASE(); y luego ejecutar manualmente.
-- =====================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────
-- 1) colegio_cursos: cur_orden, cur_abreviado, cur_nivel, cur_cupo
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE `colegio_cursos`
    ADD COLUMN IF NOT EXISTS `cur_orden` INT NOT NULL DEFAULT 0 AFTER `cur_nombre`,
    ADD COLUMN IF NOT EXISTS `cur_abreviado` VARCHAR(30) NULL AFTER `cur_orden`,
    ADD COLUMN IF NOT EXISTS `cur_nivel` VARCHAR(20) NULL AFTER `cur_abreviado`,
    ADD COLUMN IF NOT EXISTS `cur_cupo` INT NOT NULL DEFAULT 0 AFTER `cur_nivel`;

-- Asignar orden por curso (códigos conocidos)
UPDATE `colegio_cursos` SET `cur_orden` = 1  WHERE `cur_codigo` = 'PreKinder';
UPDATE `colegio_cursos` SET `cur_orden` = 2  WHERE `cur_codigo` = 'Kinder';
UPDATE `colegio_cursos` SET `cur_orden` = 3  WHERE `cur_codigo` = '1roPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 5  WHERE `cur_codigo` = '2doPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 6  WHERE `cur_codigo` = '5500504';
UPDATE `colegio_cursos` SET `cur_orden` = 7  WHERE `cur_codigo` = '3roPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 8  WHERE `cur_codigo` = 'eb67ecb';
UPDATE `colegio_cursos` SET `cur_orden` = 9  WHERE `cur_codigo` = '4toPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 10 WHERE `cur_codigo` = '5e7c089';
UPDATE `colegio_cursos` SET `cur_orden` = 11 WHERE `cur_codigo` = '5toPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 13 WHERE `cur_codigo` = '6toPRIM';
UPDATE `colegio_cursos` SET `cur_orden` = 14 WHERE `cur_codigo` = '4cb7d92';
UPDATE `colegio_cursos` SET `cur_orden` = 15 WHERE `cur_codigo` = '1roSEC';
UPDATE `colegio_cursos` SET `cur_orden` = 16 WHERE `cur_codigo` = 'af2534c';
UPDATE `colegio_cursos` SET `cur_orden` = 17 WHERE `cur_codigo` = '2doSEC';
UPDATE `colegio_cursos` SET `cur_orden` = 18 WHERE `cur_codigo` = '556e9b7';
UPDATE `colegio_cursos` SET `cur_orden` = 19 WHERE `cur_codigo` = '3roSEC';
UPDATE `colegio_cursos` SET `cur_orden` = 20 WHERE `cur_codigo` = '64b2b3e';
UPDATE `colegio_cursos` SET `cur_orden` = 21 WHERE `cur_codigo` = '4toSEC';
UPDATE `colegio_cursos` SET `cur_orden` = 22 WHERE `cur_codigo` = 'ffb2f67';
UPDATE `colegio_cursos` SET `cur_orden` = 23 WHERE `cur_codigo` = '5toSEC';
UPDATE `colegio_cursos` SET `cur_orden` = 25 WHERE `cur_codigo` = '6toSEC';

-- Inferir nivel cuando esté vacío
UPDATE `colegio_cursos`
SET `cur_nivel` = CASE
    WHEN `cur_codigo` IN ('PreKinder','Kinder') THEN 'INICIAL'
    WHEN `cur_nombre` LIKE '%PRIM%' OR `cur_nombre` LIKE '%Primaria%' THEN 'PRIMARIA'
    WHEN `cur_nombre` LIKE '%SEC%'  OR `cur_nombre` LIKE '%Secundaria%' THEN 'SECUNDARIA'
    ELSE `cur_nivel`
END
WHERE `cur_nivel` IS NULL OR `cur_nivel` = '';


-- ─────────────────────────────────────────────────────────────────────
-- 2) colegio_docentes: doc_telefono, doc_sexo, doc_descripcion
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE `colegio_docentes`
    ADD COLUMN IF NOT EXISTS `doc_telefono`    VARCHAR(20) NULL AFTER `doc_ci`,
    ADD COLUMN IF NOT EXISTS `doc_sexo`        VARCHAR(15) NULL AFTER `doc_telefono`,
    ADD COLUMN IF NOT EXISTS `doc_descripcion` TEXT        NULL AFTER `doc_sexo`;


-- ─────────────────────────────────────────────────────────────────────
-- 3) colegio_docente_horarios
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `colegio_docente_horarios` (
    `horario_id`             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `doc_codigo`             VARCHAR(14) NOT NULL,
    `horario_turno`          VARCHAR(20) NOT NULL,
    `horario_dia`            VARCHAR(15) NOT NULL,
    `horario_inicio`         TIME        NOT NULL,
    `horario_fin`            TIME        NOT NULL,
    `horario_estado`         TINYINT(4)  NOT NULL DEFAULT 1,
    `horario_fecha_registro` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`horario_id`),
    KEY `idx_dh_doc` (`doc_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────
-- 4) sistema_configuracion (datos institucionales)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sistema_configuracion` (
    `config_id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `config_logo`         VARCHAR(255) NULL,
    `config_denominacion` VARCHAR(200) NULL,
    `config_nombre_ue`    VARCHAR(200) NULL,
    `config_direccion`    VARCHAR(255) NULL,
    `config_telefono`     VARCHAR(50)  NULL,
    `config_ciudad`       VARCHAR(100) NULL,
    `config_email`        VARCHAR(100) NULL,
    `config_fecha`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sistema_configuracion`
    (`config_denominacion`, `config_nombre_ue`, `config_direccion`, `config_telefono`, `config_ciudad`, `config_email`)
SELECT 'UNIDAD EDUCATIVA', 'INTERANDINO BOLIVIANO', '', '', '', ''
WHERE NOT EXISTS (SELECT 1 FROM `sistema_configuracion`);


-- ─────────────────────────────────────────────────────────────────────
-- 5) colegio_niveles
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `colegio_niveles` (
    `niv_id`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `niv_nombre`    VARCHAR(50) NOT NULL,
    `niv_abreviado` VARCHAR(20) NULL,
    `niv_orden`     INT NOT NULL DEFAULT 0,
    `niv_estado`    TINYINT(4) NOT NULL DEFAULT 1,
    `niv_fecha`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`niv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `colegio_niveles` (`niv_nombre`,`niv_abreviado`,`niv_orden`,`niv_estado`)
SELECT * FROM (
    SELECT 'INICIAL'    AS n, 'INI' AS a, 1 AS o, 1 AS e UNION ALL
    SELECT 'PRIMARIA',         'PRI',     2,         1     UNION ALL
    SELECT 'SECUNDARIA',       'SEC',     3,         1
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `colegio_niveles` WHERE `niv_nombre` = src.n
);


-- ─────────────────────────────────────────────────────────────────────
-- 6) colegio_gestiones
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `colegio_gestiones` (
    `ges_id`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ges_anio`      VARCHAR(10) NOT NULL,
    `ges_nombre`    VARCHAR(80) NOT NULL,
    `ges_abreviado` VARCHAR(20) NULL,
    `ges_estado`    TINYINT(4) NOT NULL DEFAULT 0,
    `ges_fecha`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ges_id`),
    UNIQUE KEY `ges_anio` (`ges_anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `colegio_gestiones` (`ges_anio`,`ges_nombre`,`ges_abreviado`,`ges_estado`) VALUES
    ('2024', 'GESTIÓN 2024', '2024', 0),
    ('2025', 'GESTIÓN 2025', '2025', 0),
    ('2026', 'GESTIÓN 2026', '2026', 1);


-- ─────────────────────────────────────────────────────────────────────
-- 7) rol_modulos: nuevos módulos para Concejo, Parametrización, Notas
-- ─────────────────────────────────────────────────────────────────────
-- Hijos de Notas (mod_id de notas se obtiene dinámicamente)
INSERT INTO `rol_modulos` (`mod_nombre`,`mod_slug`,`mod_icono`,`mod_padre_id`,`mod_orden`,`mod_visible`)
SELECT * FROM (
    SELECT 'Rendimiento'         AS n, 'notas.rendimiento'         AS s, 'fas fa-chart-line' AS i, 8 AS p, 46 AS o, 1 AS v UNION ALL
    SELECT 'Centralizador Anual',     'notas.centralizador-anual',     'fas fa-table',           8,   47,    1     UNION ALL
    SELECT 'Cuadro de Honor',         'notas.cuadro-honor',            'fas fa-medal',           8,   48,    1     UNION ALL
    SELECT 'Top 3 por Curso',         'notas.top3-cursos',             'fas fa-trophy',          8,   49,    1
) AS src
WHERE NOT EXISTS (SELECT 1 FROM `rol_modulos` WHERE `mod_slug` = src.s);

-- Si el id real del módulo Notas no es 8, vuelve a apuntar:
UPDATE `rol_modulos` SET `mod_padre_id` = (SELECT mod_id FROM (SELECT mod_id FROM rol_modulos WHERE mod_slug = 'notas') t)
WHERE `mod_slug` IN ('notas.rendimiento','notas.centralizador-anual','notas.cuadro-honor','notas.top3-cursos');

-- Concejo Educativo (raíz)
INSERT INTO `rol_modulos` (`mod_nombre`,`mod_slug`,`mod_icono`,`mod_padre_id`,`mod_orden`,`mod_visible`)
SELECT 'Concejo Educativo','concejo','fas fa-gavel', NULL, 41, 1
WHERE NOT EXISTS (SELECT 1 FROM `rol_modulos` WHERE `mod_slug` = 'concejo');

-- Parametrización (raíz)
INSERT INTO `rol_modulos` (`mod_nombre`,`mod_slug`,`mod_icono`,`mod_padre_id`,`mod_orden`,`mod_visible`)
SELECT 'Parametrización','parametrizacion','fas fa-cogs', NULL, 50, 1
WHERE NOT EXISTS (SELECT 1 FROM `rol_modulos` WHERE `mod_slug` = 'parametrizacion');

-- Hijos de Parametrización
-- Se insertan con mod_padre_id = NULL y luego se actualiza al ID real del módulo padre.
INSERT INTO `rol_modulos` (`mod_nombre`,`mod_slug`,`mod_icono`,`mod_padre_id`,`mod_orden`,`mod_visible`)
SELECT * FROM (
    SELECT 'Unidad Educativa' AS n, 'unidad-educativa' AS s, 'fas fa-school'     AS i, NULL AS p, 1 AS o, 1 AS v UNION ALL
    SELECT 'Niveles',              'niveles',              'fas fa-layer-group',      NULL,      2,         1     UNION ALL
    SELECT 'Gestiones',            'gestiones',            'fas fa-calendar',         NULL,      3,         1
) AS src
WHERE NOT EXISTS (SELECT 1 FROM `rol_modulos` WHERE `mod_slug` = src.s);

UPDATE `rol_modulos`
SET `mod_padre_id` = (SELECT mod_id FROM (SELECT mod_id FROM rol_modulos WHERE mod_slug = 'parametrizacion') t)
WHERE `mod_slug` IN ('unidad-educativa','niveles','gestiones');


-- ─────────────────────────────────────────────────────────────────────
-- 8) [OPCIONAL] Recalcular nota_promedio_trimestral con decimales reales
--    Útil si tus notas existentes están guardadas como enteros (94.00,
--    91.00) pero los detalles en colegio_notas_detalle sí varían. El
--    nuevo flujo de calificación ya guarda con decimales; este UPDATE
--    sólo corrige las notas legacy.
-- ─────────────────────────────────────────────────────────────────────
-- DESCOMENTA si quieres aplicarlo:
-- UPDATE `colegio_notas` n
-- JOIN (
--     SELECT nota_id, ROUND(SUM(dim_avg), 2) AS prom
--     FROM (
--         SELECT nota_id, dimension_id, AVG(NULLIF(detalle_valor, 0)) AS dim_avg
--         FROM `colegio_notas_detalle`
--         GROUP BY nota_id, dimension_id
--     ) t
--     GROUP BY nota_id
-- ) calc ON calc.nota_id = n.nota_id
-- SET n.nota_promedio_trimestral = calc.prom;


-- ─────────────────────────────────────────────────────────────────────
-- Registrar las migraciones para que `php artisan migrate` no las repita
-- (asume tabla `migrations` con columnas `migration` y `batch`).
-- ─────────────────────────────────────────────────────────────────────

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- ── FIN ───────────────────────────────────────────────────────────────
SELECT 'Upgrade 2026-05-05 aplicado correctamente.' AS resultado;
