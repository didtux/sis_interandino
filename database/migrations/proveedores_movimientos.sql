-- Tabla de Proveedores
DROP TABLE IF EXISTS `ventas_proveedores`;
CREATE TABLE `ventas_proveedores` (
  `prov_id` int(11) NOT NULL AUTO_INCREMENT,
  `prov_codigo` varchar(20) NOT NULL,
  `prov_nombre` varchar(100) NOT NULL,
  `prov_razon_social` varchar(150) DEFAULT NULL,
  `prov_nit` varchar(20) DEFAULT NULL,
  `prov_telefono` varchar(20) DEFAULT NULL,
  `prov_email` varchar(100) DEFAULT NULL,
  `prov_direccion` varchar(200) DEFAULT NULL,
  `prov_contacto` varchar(100) DEFAULT NULL,
  `prov_estado` tinyint(1) DEFAULT 1,
  `prov_fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  `prov_usuario_registro` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`prov_id`),
  UNIQUE KEY `prov_codigo` (`prov_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Movimientos de Almacén
DROP TABLE IF EXISTS `ventas_movimientos`;
CREATE TABLE `ventas_movimientos` (
  `mov_id` int(11) NOT NULL AUTO_INCREMENT,
  `mov_codigo` varchar(20) NOT NULL,
  `prod_codigo` varchar(20) NOT NULL,
  `prov_codigo` varchar(20) DEFAULT NULL,
  `mov_tipo` enum('entrada','salida','ajuste','devolucion') NOT NULL,
  `mov_cantidad` int(11) NOT NULL,
  `mov_precio_unitario` decimal(10,2) DEFAULT NULL,
  `mov_precio_total` decimal(10,2) DEFAULT NULL,
  `mov_motivo` varchar(200) DEFAULT NULL,
  `mov_observacion` text DEFAULT NULL,
  `mov_fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  `mov_usuario` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`mov_id`),
  UNIQUE KEY `mov_codigo` (`mov_codigo`),
  KEY `prod_codigo` (`prod_codigo`),
  KEY `prov_codigo` (`prov_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columna de proveedor a productos si no existe
ALTER TABLE `ventas_productos` 
ADD COLUMN IF NOT EXISTS `prov_codigo` varchar(20) DEFAULT NULL AFTER `categ_codigo`;
