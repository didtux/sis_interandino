-- Verificar cuántos registros hay en cada tabla
SELECT 'inscripciones' as tabla, COUNT(*) as registros FROM inscripciones
UNION ALL
SELECT 'inscripciones_pagos', COUNT(*) FROM inscripciones_pagos
UNION ALL
SELECT 'inscripciones_descuentos', COUNT(*) FROM inscripciones_descuentos
UNION ALL
SELECT 'pagos', COUNT(*) FROM pagos;
