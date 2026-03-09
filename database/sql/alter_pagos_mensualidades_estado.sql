-- Agregar campo pagos_estado a la tabla pagos_mensualidades si no existe
ALTER TABLE `pagos_mensualidades` 
ADD COLUMN `pagos_estado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `pagos_fecha`;

-- Actualizar registros existentes para que tengan estado activo
UPDATE `pagos_mensualidades` SET `pagos_estado` = 1 WHERE `pagos_estado` IS NULL OR `pagos_estado` = 0;
