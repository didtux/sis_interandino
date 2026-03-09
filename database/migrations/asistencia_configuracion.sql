-- Migración para Configuración de Asistencia
-- Ejecutar en la base de datos uepinter_ueinterandino

-- Tabla de configuración de horarios y atrasos
CREATE TABLE `asistencia_configuracion` (
  `config_id` int(7) NOT NULL AUTO_INCREMENT,
  `config_codigo` varchar(14) NOT NULL,
  `config_categoria` varchar(50) NOT NULL COMMENT 'Primaria, Secundaria, etc.',
  `hora_entrada` time NOT NULL,
  `hora_salida` time NOT NULL,
  `tolerancia_atraso` time NOT NULL COMMENT 'Minutos de tolerancia',
  `hora_atraso_desde` time NOT NULL,
  `hora_atraso_hasta` time NOT NULL,
  `config_estado` tinyint(1) NOT NULL DEFAULT 1,
  `config_fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `config_codigo` (`config_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Tabla de registro de atrasos
CREATE TABLE `asistencia_atrasos` (
  `atraso_id` int(7) NOT NULL AUTO_INCREMENT,
  `atraso_codigo` varchar(14) NOT NULL,
  `estud_codigo` varchar(14) NOT NULL,
  `atraso_fecha` date NOT NULL,
  `atraso_hora` time NOT NULL,
  `minutos_atraso` int(11) NOT NULL,
  `atraso_observacion` varchar(200) DEFAULT NULL,
  `atraso_fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`atraso_id`),
  UNIQUE KEY `atraso_codigo` (`atraso_codigo`),
  KEY `estud_codigo` (`estud_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Tabla de permisos estudiantiles
CREATE TABLE `asistencia_permisos` (
  `permiso_id` int(7) NOT NULL AUTO_INCREMENT,
  `permiso_codigo` varchar(14) NOT NULL,
  `estud_codigo` varchar(14) NOT NULL,
  `permiso_fecha_inicio` date NOT NULL,
  `permiso_fecha_fin` date NOT NULL,
  `permiso_motivo` varchar(200) NOT NULL,
  `permiso_observacion` text DEFAULT NULL,
  `permiso_documento` varchar(100) DEFAULT NULL,
  `permiso_estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Aprobado, 0=Rechazado, 2=Pendiente',
  `permiso_aprobado_por` varchar(14) DEFAULT NULL,
  `permiso_fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`permiso_id`),
  UNIQUE KEY `permiso_codigo` (`permiso_codigo`),
  KEY `estud_codigo` (`estud_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Tabla de fechas festivas y especiales
CREATE TABLE `asistencia_fechas_festivas` (
  `festivo_id` int(7) NOT NULL AUTO_INCREMENT,
  `festivo_codigo` varchar(14) NOT NULL,
  `festivo_fecha` date NOT NULL,
  `festivo_nombre` varchar(100) NOT NULL,
  `festivo_descripcion` varchar(200) DEFAULT NULL,
  `festivo_hora_entrada` time DEFAULT NULL,
  `festivo_hora_salida` time DEFAULT NULL,
  `festivo_tipo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Feriado (sin clases), 2=Horario especial',
  `festivo_estado` tinyint(1) NOT NULL DEFAULT 1,
  `festivo_fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`festivo_id`),
  UNIQUE KEY `festivo_codigo` (`festivo_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de ejemplo para configuración
INSERT INTO `asistencia_configuracion` (`config_codigo`, `config_categoria`, `hora_entrada`, `hora_salida`, `tolerancia_atraso`, `hora_atraso_desde`, `hora_atraso_hasta`, `config_estado`) VALUES
('CONF001', 'Primaria', '08:00:00', '12:30:00', '00:15:00', '08:15:00', '09:00:00', 1),
('CONF002', 'Secundaria', '07:30:00', '13:00:00', '00:10:00', '07:40:00', '08:30:00', 1);

-- Datos de ejemplo para fechas festivas
INSERT INTO `asistencia_fechas_festivas` (`festivo_codigo`, `festivo_fecha`, `festivo_nombre`, `festivo_descripcion`, `festivo_tipo`, `festivo_estado`) VALUES
('FEST001', '2026-01-01', 'Año Nuevo', 'Feriado nacional', 1, 1),
('FEST002', '2026-08-06', 'Día de la Patria', 'Independencia de Bolivia', 1, 1),
('FEST003', '2026-12-25', 'Navidad', 'Feriado nacional', 1, 1);
