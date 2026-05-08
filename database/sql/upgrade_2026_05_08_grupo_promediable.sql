-- =====================================================================
-- Upgrade: agrega columna `detalle_promediable` a colegio_materia_grupo_detalle.
-- Permite indicar qué materias dentro de un grupo (área) suman al promedio
-- del grupo. Ej: "Comunidad y Sociedad" agrupa 4 materias pero solo 2
-- contribuyen al promedio.
-- Por defecto = 1 (todas suman, como hasta ahora).
-- =====================================================================

ALTER TABLE `colegio_materia_grupo_detalle`
    ADD COLUMN IF NOT EXISTS `detalle_promediable` TINYINT(1) NOT NULL DEFAULT 1
    AFTER `detalle_orden`;

-- Sembrado retroactivo: todas las filas existentes quedan promediables (=1)
UPDATE `colegio_materia_grupo_detalle`
   SET `detalle_promediable` = 1
 WHERE `detalle_promediable` IS NULL;
