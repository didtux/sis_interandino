-- Agregar campo pagos_codigo a la tabla pagos_mensualidades si no existe
ALTER TABLE `pagos_mensualidades` 
ADD COLUMN `pagos_codigo` VARCHAR(20) NULL AFTER `pagos_id`;

-- Agregar campo pagos_sin_factura a la tabla pagos_mensualidades si no existe
ALTER TABLE `pagos_mensualidades` 
ADD COLUMN `pagos_sin_factura` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pagos_estado`;

-- Generar códigos para registros existentes (REC por defecto)
UPDATE `pagos_mensualidades` 
SET `pagos_codigo` = CONCAT('REC', LPAD(pagos_id, 5, '0'))
WHERE `pagos_codigo` IS NULL OR `pagos_codigo` = '';
