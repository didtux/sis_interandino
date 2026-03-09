-- Ejecutar este SQL para corregir la tabla existente

ALTER TABLE `inscripciones_pagos` 
MODIFY COLUMN `inscpago_codigo` varchar(20) NOT NULL;

-- Verificar que se aplicó correctamente
DESCRIBE inscripciones_pagos;
