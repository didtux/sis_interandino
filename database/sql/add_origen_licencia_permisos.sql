-- Agregar campos origen y numero_licencia a tabla de permisos
ALTER TABLE asistencia_permisos 
ADD COLUMN permiso_origen VARCHAR(20) DEFAULT 'PERSONAL' AFTER permiso_motivo,
ADD COLUMN permiso_numero_licencia VARCHAR(50) NULL AFTER permiso_numero;

-- Actualizar registros existentes
UPDATE asistencia_permisos SET permiso_origen = 'PERSONAL' WHERE permiso_origen IS NULL;
