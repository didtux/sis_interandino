-- Agregar campo config_turno a la tabla asistencia_configuracion
ALTER TABLE `asistencia_configuracion` 
ADD COLUMN `config_turno` VARCHAR(20) NULL AFTER `config_categoria`;

-- Actualizar registros existentes con valor por defecto
UPDATE `asistencia_configuracion` 
SET `config_turno` = 'Mañana' 
WHERE `config_turno` IS NULL;
