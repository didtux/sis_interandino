-- Script para vaciar tablas de inscripciones respetando claves foráneas
-- IMPORTANTE: Ejecutar TODO el script de una sola vez (seleccionar todo y ejecutar)

-- Opción 1: Usar DELETE (más lento pero seguro)
DELETE FROM inscripciones_descuentos;
DELETE FROM inscripciones_pagos;
DELETE FROM pagos_mensualidades;
DELETE FROM inscripciones;

-- Reiniciar auto_increment
ALTER TABLE inscripciones_descuentos AUTO_INCREMENT = 1;
ALTER TABLE inscripciones_pagos AUTO_INCREMENT = 1;
ALTER TABLE pagos_mensualidades AUTO_INCREMENT = 1;
ALTER TABLE inscripciones AUTO_INCREMENT = 1;

-- Verificar que las tablas estén vacías
SELECT 'inscripciones_descuentos' as tabla, COUNT(*) as registros FROM inscripciones_descuentos
UNION ALL
SELECT 'inscripciones_pagos', COUNT(*) FROM inscripciones_pagos
UNION ALL
SELECT 'pagos_mensualidades', COUNT(*) FROM pagos_mensualidades
UNION ALL
SELECT 'inscripciones', COUNT(*) FROM inscripciones;
