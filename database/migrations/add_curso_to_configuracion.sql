-- Agregar campo de curso a la configuración de asistencia
ALTER TABLE asistencia_configuracion 
ADD COLUMN cur_codigo VARCHAR(20) NULL AFTER config_turno,
ADD INDEX idx_cur_codigo (cur_codigo);
