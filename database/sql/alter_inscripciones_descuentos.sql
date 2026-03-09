-- Agregar campos de descuento a la tabla inscripciones
ALTER TABLE `inscripciones` 
ADD COLUMN `insc_monto_descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `insc_monto_total`,
ADD COLUMN `insc_monto_final` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `insc_monto_descuento`,
ADD COLUMN `insc_sin_factura` TINYINT(1) NOT NULL DEFAULT 0 AFTER `insc_monto_final`;

-- Actualizar registros existentes para que monto_final = monto_total si no tienen descuento
UPDATE `inscripciones` SET `insc_monto_final` = `insc_monto_total` WHERE `insc_monto_final` = 0;
