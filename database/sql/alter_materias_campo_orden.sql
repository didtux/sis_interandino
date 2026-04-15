-- Agregar campo y orden a materias para reportes de notas
ALTER TABLE colegio_materias ADD COLUMN IF NOT EXISTS mat_campo VARCHAR(100) DEFAULT NULL AFTER mat_nombre;
ALTER TABLE colegio_materias ADD COLUMN IF NOT EXISTS mat_orden INT DEFAULT 0 AFTER mat_campo;
