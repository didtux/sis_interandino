-- Agregar campo parentesco en la tabla de padres de familia
ALTER TABLE cole_padresfamilia ADD COLUMN pfam_parentesco ENUM('Padre','Madre','Hermano/a','Tutor/a') NULL AFTER pfam_nombres;
