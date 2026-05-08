-- =====================================================================
-- Upgrade: el campo (mat_campo) pasa a ser el grupo natural de cada materia.
-- Cada materia indica si suma al promedio de su campo via `mat_promediable`.
-- Se elimina la dependencia de las tablas colegio_materia_grupos /
-- colegio_materia_grupo_detalle (no se borran los datos: quedan disponibles
-- por compatibilidad pero el sistema ya no las consulta).
-- =====================================================================

ALTER TABLE `colegio_materias`
    ADD COLUMN IF NOT EXISTS `mat_promediable` TINYINT(1) NOT NULL DEFAULT 1
    AFTER `mat_campo`;

-- Por defecto todas las materias suman al promedio del campo.
UPDATE `colegio_materias`
   SET `mat_promediable` = 1
 WHERE `mat_promediable` IS NULL;

-- Migración retroactiva: si alguna materia ya estaba en un grupo manual con
-- detalle_promediable = 0, se respeta ese estado.
UPDATE `colegio_materias` m
  JOIN `colegio_materia_grupo_detalle` d
    ON CONVERT(m.mat_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
       = d.mat_codigo COLLATE utf8mb4_unicode_ci
   SET m.mat_promediable = d.detalle_promediable
 WHERE d.detalle_promediable IS NOT NULL;
