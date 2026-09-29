-- Actividades: distinguir INGRESO de SALIDA en el escaneo.
--
-- Hasta ahora sólo se guardaba una marca por estudiante y categoría, así que el
-- segundo escaneo se rechazaba con "ya fue registrado" y no había forma de
-- registrar la salida. Los registros existentes se dan por INGRESO, que es lo
-- único que se podía cargar antes.
ALTER TABLE actividades_registros
    ADD COLUMN actreg_tipo ENUM('INGRESO','SALIDA') NOT NULL DEFAULT 'INGRESO' AFTER actreg_hora;

-- La deduplicación pasa a ser por (categoría, estudiante, tipo): un mismo alumno
-- puede tener su ingreso y su salida, pero no dos ingresos.
--
-- uk_registro era el índice único (actcat_id, est_codigo) que imponía la marca
-- única; si no se elimina, el INSERT de la salida muere con "Duplicate entry".
ALTER TABLE actividades_registros
    DROP INDEX uk_registro,
    ADD UNIQUE KEY uq_actreg_cat_est_tipo (actcat_id, est_codigo, actreg_tipo);
