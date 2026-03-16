-- Modificar la columna tpago_tipo para aceptar todos los tipos de pago
ALTER TABLE transporte_pagos 
MODIFY COLUMN tpago_tipo ENUM('mensual','bimestral','trimestral','cuatrimestral','quinquemestral','semestral','7 meses','8 meses','9 meses','anual') 
NOT NULL;
