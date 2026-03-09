-- Primero eliminar la tabla si existe para recrearla con el collation correcto
DROP TABLE IF EXISTS `asistencia_configuracion_cursos`;

-- Crear tabla intermedia con el mismo collation que colegio_cursos
CREATE TABLE `asistencia_configuracion_cursos` (
  `config_curso_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `cur_codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`config_curso_id`),
  KEY `config_id` (`config_id`),
  KEY `cur_codigo` (`cur_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar datos existentes de cur_codigo a la tabla intermedia
INSERT INTO `asistencia_configuracion_cursos` (`config_id`, `cur_codigo`, `created_at`, `updated_at`)
SELECT `config_id`, `cur_codigo`, NOW(), NOW()
FROM `asistencia_configuracion`
WHERE `cur_codigo` IS NOT NULL AND `config_estado` = 1;
