-- Script para verificar y corregir campos en pagos_mensualidades

-- 1. Verificar si existe el campo pagos_codigo
SELECT COUNT(*) as tiene_pagos_codigo 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'pagos_mensualidades' 
AND COLUMN_NAME = 'pagos_codigo';

-- 2. Verificar si existe el campo pagos_sin_factura
SELECT COUNT(*) as tiene_pagos_sin_factura 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'pagos_mensualidades' 
AND COLUMN_NAME = 'pagos_sin_factura';

-- 3. Si los campos no existen, crearlos
ALTER TABLE `pagos_mensualidades` 
ADD COLUMN IF NOT EXISTS `pagos_codigo` VARCHAR(20) NULL AFTER `pagos_id`;

ALTER TABLE `pagos_mensualidades` 
ADD COLUMN IF NOT EXISTS `pagos_sin_factura` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pagos_estado`;

-- 4. Actualizar registros sin código (generar REC por defecto)
UPDATE `pagos_mensualidades` 
SET `pagos_codigo` = CONCAT('REC', LPAD(pagos_id, 5, '0'))
WHERE `pagos_codigo` IS NULL OR `pagos_codigo` = '';

-- 5. Actualizar registros sin tipo de factura (con factura por defecto)
UPDATE `pagos_mensualidades` 
SET `pagos_sin_factura` = 0
WHERE `pagos_sin_factura` IS NULL;

-- 6. Verificar resultados
SELECT 
    pagos_id,
    pagos_codigo,
    pagos_sin_factura,
    est_codigo,
    concepto,
    pagos_precio
FROM pagos_mensualidades 
ORDER BY pagos_id DESC 
LIMIT 10;
