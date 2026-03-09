-- Agregar campo de estado a pagos_servicios
ALTER TABLE pagos_servicios 
ADD COLUMN pserv_estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Anulado';

-- Actualizar registros existentes a estado activo
UPDATE pagos_servicios SET pserv_estado = 1 WHERE pserv_estado IS NULL OR pserv_estado = 0;
