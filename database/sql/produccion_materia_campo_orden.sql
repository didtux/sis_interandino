-- =====================================================
-- Agrupación de materias por campo/área curricular
-- Ejecutar en producción
-- =====================================================

-- Agregar columnas a colegio_materias
ALTER TABLE colegio_materias
    ADD COLUMN IF NOT EXISTS mat_campo VARCHAR(100) DEFAULT NULL AFTER mat_nombre,
    ADD COLUMN IF NOT EXISTS mat_orden INT DEFAULT 0 AFTER mat_campo;
