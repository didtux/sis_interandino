-- Cambiar tpago_codigo de UNIQUE a INDEX para permitir pagos agrupados
ALTER TABLE transporte_pagos DROP INDEX tpago_codigo;
ALTER TABLE transporte_pagos ADD INDEX idx_tpago_codigo (tpago_codigo);
