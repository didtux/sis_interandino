-- Tabla de descuentos
CREATE TABLE IF NOT EXISTS `descuentos` (
  `desc_id` int(11) NOT NULL AUTO_INCREMENT,
  `desc_codigo` varchar(50) NOT NULL,
  `desc_nombre` varchar(100) NOT NULL,
  `desc_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `desc_estado` tinyint(1) NOT NULL DEFAULT 1,
  `desc_fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`desc_id`),
  UNIQUE KEY `desc_codigo` (`desc_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla intermedia inscripciones_descuentos
CREATE TABLE IF NOT EXISTS `inscripciones_descuentos` (
  `inscdesc_id` int(11) NOT NULL AUTO_INCREMENT,
  `insc_id` int(11) NOT NULL,
  `desc_id` int(11) NOT NULL,
  `inscdesc_monto_descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `inscdesc_fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`inscdesc_id`),
  KEY `insc_id` (`insc_id`),
  KEY `desc_id` (`desc_id`),
  CONSTRAINT `fk_inscdesc_inscripcion` FOREIGN KEY (`insc_id`) REFERENCES `inscripciones` (`insc_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inscdesc_descuento` FOREIGN KEY (`desc_id`) REFERENCES `descuentos` (`desc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar descuentos por defecto
INSERT INTO `descuentos` (`desc_codigo`, `desc_nombre`, `desc_porcentaje`, `desc_estado`) VALUES
('DESC001', 'Descuento Hermanos', 10.00, 1),
('DESC002', 'Descuento Pronto Pago', 5.00, 1),
('DESC003', 'Descuento Excelencia Académica', 15.00, 1),
('DESC004', 'Descuento Personal', 20.00, 1);
