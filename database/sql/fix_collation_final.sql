-- Verificar el collation actual de la columna cur_codigo en colegio_cursos
-- SHOW FULL COLUMNS FROM colegio_cursos WHERE Field = 'cur_codigo';

-- Opción 1: Cambiar el collation de colegio_cursos.cur_codigo a utf8mb4_unicode_ci
ALTER TABLE `colegio_cursos` 
MODIFY `cur_codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Opción 2: Recrear la tabla intermedia con utf8mb4_general_ci
DROP TABLE IF EXISTS `asistencia_configuracion_cursos`;

CREATE TABLE `asistencia_configuracion_cursos` (
  `config_curso_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `cur_codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`config_curso_id`),
  KEY `config_id` (`config_id`),
  KEY `cur_codigo` (`cur_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrar datos existentes
INSERT INTO `asistencia_configuracion_cursos` (`config_id`, `cur_codigo`, `created_at`, `updated_at`)
SELECT `config_id`, `cur_codigo`, NOW(), NOW()
FROM `asistencia_configuracion`
WHERE `cur_codigo` IS NOT NULL AND `config_estado` = 1;
