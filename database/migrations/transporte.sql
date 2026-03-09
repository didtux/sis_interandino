-- Tabla de Vehículos
CREATE TABLE IF NOT EXISTS `transporte_vehiculos` (
  `veh_id` int(11) NOT NULL AUTO_INCREMENT,
  `veh_codigo` varchar(20) NOT NULL,
  `veh_placa` varchar(20) NOT NULL,
  `veh_marca` varchar(50) DEFAULT NULL,
  `veh_modelo` varchar(50) DEFAULT NULL,
  `veh_anio` int(4) DEFAULT NULL,
  `veh_capacidad` int(11) NOT NULL,
  `veh_color` varchar(30) DEFAULT NULL,
  `veh_estado` tinyint(1) DEFAULT 1,
  `veh_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `veh_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`veh_id`),
  UNIQUE KEY `veh_codigo` (`veh_codigo`),
  UNIQUE KEY `veh_placa` (`veh_placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

-- Tabla de Choferes
CREATE TABLE IF NOT EXISTS `transporte_choferes` (
  `chof_id` int(11) NOT NULL AUTO_INCREMENT,
  `chof_codigo` varchar(20) NOT NULL,
  `chof_nombres` varchar(100) NOT NULL,
  `chof_apellidos` varchar(100) NOT NULL,
  `chof_ci` varchar(20) NOT NULL,
  `chof_licencia` varchar(20) NOT NULL,
  `chof_telefono` varchar(20) DEFAULT NULL,
  `chof_direccion` varchar(200) DEFAULT NULL,
  `chof_fecha_nacimiento` date DEFAULT NULL,
  `chof_estado` tinyint(1) DEFAULT 1,
  `chof_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `chof_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`chof_id`),
  UNIQUE KEY `chof_codigo` (`chof_codigo`),
  UNIQUE KEY `chof_ci` (`chof_ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Rutas
CREATE TABLE IF NOT EXISTS `transporte_rutas` (
  `ruta_id` int(11) NOT NULL AUTO_INCREMENT,
  `ruta_codigo` varchar(20) NOT NULL,
  `ruta_nombre` varchar(100) NOT NULL,
  `ruta_descripcion` text DEFAULT NULL,
  `ruta_coordenadas` text DEFAULT NULL,
  `ruta_estado` tinyint(1) DEFAULT 1,
  `ruta_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `ruta_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ruta_id`),
  UNIQUE KEY `ruta_codigo` (`ruta_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Asignación Chofer-Vehículo-Ruta
CREATE TABLE IF NOT EXISTS `transporte_asignaciones` (
  `asig_id` int(11) NOT NULL AUTO_INCREMENT,
  `asig_codigo` varchar(20) NOT NULL,
  `chof_codigo` varchar(20) NOT NULL,
  `veh_codigo` varchar(20) NOT NULL,
  `ruta_codigo` varchar(20) NOT NULL,
  `asig_fecha_inicio` date NOT NULL,
  `asig_fecha_fin` date DEFAULT NULL,
  `asig_estado` tinyint(1) DEFAULT 1,
  `asig_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `asig_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`asig_id`),
  UNIQUE KEY `asig_codigo` (`asig_codigo`),
  KEY `chof_codigo` (`chof_codigo`),
  KEY `veh_codigo` (`veh_codigo`),
  KEY `ruta_codigo` (`ruta_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Pagos de Transporte
CREATE TABLE IF NOT EXISTS `transporte_pagos` (
  `tpago_id` int(11) NOT NULL AUTO_INCREMENT,
  `tpago_codigo` varchar(20) NOT NULL,
  `est_codigo` varchar(20) NOT NULL,
  `tpago_tipo` enum('mensual','trimestral','anual') NOT NULL,
  `tpago_monto` decimal(10,2) NOT NULL,
  `tpago_fecha_pago` date NOT NULL,
  `tpago_fecha_inicio` date NOT NULL,
  `tpago_fecha_fin` date NOT NULL,
  `tpago_estado` enum('vigente','vencido','cancelado') DEFAULT 'vigente',
  `tpago_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `tpago_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`tpago_id`),
  UNIQUE KEY `tpago_codigo` (`tpago_codigo`),
  KEY `est_codigo` (`est_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Asignación Estudiante-Ruta
CREATE TABLE IF NOT EXISTS `transporte_estudiantes_rutas` (
  `ter_id` int(11) NOT NULL AUTO_INCREMENT,
  `ter_codigo` varchar(20) NOT NULL,
  `est_codigo` varchar(20) NOT NULL,
  `ruta_codigo` varchar(20) NOT NULL,
  `tpago_codigo` varchar(20) NOT NULL,
  `ter_direccion_recogida` varchar(200) DEFAULT NULL,
  `ter_coordenadas` varchar(100) DEFAULT NULL,
  `ter_estado` tinyint(1) DEFAULT 1,
  `ter_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `ter_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ter_id`),
  UNIQUE KEY `ter_codigo` (`ter_codigo`),
  KEY `est_codigo` (`est_codigo`),
  KEY `ruta_codigo` (`ruta_codigo`),
  KEY `tpago_codigo` (`tpago_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modificar enum para agregar opción semestral
ALTER TABLE `transporte_pagos` 
MODIFY COLUMN `tpago_tipo` enum('mensual','trimestral','semestral','anual') NOT NULL;
