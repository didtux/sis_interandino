-- Modificar campo foto en la tabla de estudiantes (si existe, lo modifica; si no, lo crea)
ALTER TABLE `colegio_estudiantes` 
MODIFY COLUMN `est_foto` VARCHAR(255) NULL DEFAULT NULL;

-- Modificar campo foto en la tabla de choferes
ALTER TABLE `transporte_choferes` 
MODIFY COLUMN `chof_foto` VARCHAR(255) NULL DEFAULT NULL;

-- Modificar campo foto en la tabla de padres de familia
ALTER TABLE `cole_padresfamilia` 
MODIFY COLUMN `pfam_foto` VARCHAR(255) NULL DEFAULT NULL;
