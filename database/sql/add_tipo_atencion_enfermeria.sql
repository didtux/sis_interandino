-- Agregar campo tipo de atención en enfermería (solo aplica cuando DX Detalle = ATENCIÓN MÉDICA)
ALTER TABLE enfermeria_registros ADD COLUMN enf_tipo_atencion VARCHAR(255) NULL AFTER enf_dx_detalle;
