-- Agregar campo foto de perfil en docentes
ALTER TABLE colegio_docentes ADD COLUMN doc_foto VARCHAR(255) NULL AFTER doc_materia;
