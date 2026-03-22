-- Agregar relación entre permisos y horarios
ALTER TABLE asistencia_permisos ADD COLUMN config_id INT(7) NULL AFTER estud_codigo;
ALTER TABLE asistencia_permisos ADD INDEX idx_permisos_config (config_id);
