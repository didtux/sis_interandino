-- Tabla de Servicios
CREATE TABLE IF NOT EXISTS `servicios` (
  `serv_id` INT(11) NOT NULL AUTO_INCREMENT,
  `serv_codigo` VARCHAR(20) NOT NULL,
  `serv_nombre` VARCHAR(100) NOT NULL,
  `serv_descripcion` TEXT NULL,
  `serv_costo` DECIMAL(10,2) NOT NULL,
  `serv_estado` TINYINT(1) DEFAULT 1,
  `serv_fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `serv_usuario_registro` VARCHAR(20) NULL,
  PRIMARY KEY (`serv_id`),
  UNIQUE KEY `serv_codigo` (`serv_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Pagos de Servicios
CREATE TABLE IF NOT EXISTS `pagos_servicios` (
  `pserv_id` INT(11) NOT NULL AUTO_INCREMENT,
  `pserv_codigo` VARCHAR(20) NOT NULL,
  `serv_codigo` VARCHAR(20) NOT NULL,
  `est_codigo` VARCHAR(20) NOT NULL,
  `pfam_codigo` VARCHAR(20) NULL,
  `pserv_monto` DECIMAL(10,2) NOT NULL,
  `pserv_descuento` DECIMAL(10,2) DEFAULT 0,
  `pserv_total` DECIMAL(10,2) NOT NULL,
  `pserv_observacion` TEXT NULL,
  `pserv_fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `pserv_usuario` VARCHAR(20) NULL,
  PRIMARY KEY (`pserv_id`),
  UNIQUE KEY `pserv_codigo` (`pserv_codigo`),
  KEY `serv_codigo` (`serv_codigo`),
  KEY `est_codigo` (`est_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
