-- =====================================================
-- Fix collation actividades tables
-- Unificar a utf8mb4_general_ci (igual que colegio_estudiantes)
-- Ejecutar en producción
-- =====================================================

ALTER TABLE actividades CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE actividades_categorias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE actividades_registros CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
