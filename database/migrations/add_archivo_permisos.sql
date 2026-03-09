-- Agregar campo para archivo adjunto en permisos
ALTER TABLE asistencia_permisos 
ADD COLUMN permiso_archivo VARCHAR(255) NULL AFTER permiso_observacion;
