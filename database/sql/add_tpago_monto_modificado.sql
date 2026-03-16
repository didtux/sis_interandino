-- Agregar columna para controlar si el monto ya fue modificado (solo 1 vez permitido)
ALTER TABLE transporte_pagos 
ADD COLUMN tpago_monto_modificado TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Flag: 0=no modificado, 1=ya fue modificado (solo se permite 1 vez)';
