-- Transporte: manejar pagos por CUOTA (no por mes calendario).
-- El recibo debe decir "Transporte Nra cuota". Se guarda una fila por cuota,
-- con su número de cuota secuencial y el mes que cubre.
-- tpago_tipo pasa de ENUM a VARCHAR para permitir etiquetas de cuota.

ALTER TABLE transporte_pagos
  MODIFY tpago_tipo VARCHAR(30) NOT NULL,
  ADD COLUMN tpago_cuota_nro INT NULL AFTER tpago_tipo,
  ADD COLUMN tpago_mes VARCHAR(20) NULL AFTER tpago_cuota_nro;
