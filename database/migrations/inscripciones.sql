-- Migración para Módulo de Inscripciones
-- Ejecutar en la base de datos uepinter_ueinterandino

CREATE TABLE `inscripciones` (
  `insc_id` int(7) NOT NULL AUTO_INCREMENT,
  `insc_codigo` varchar(14) NOT NULL,
  `est_codigo` varchar(14) NOT NULL,
  `pfam_codigo` varchar(14) NOT NULL,
  `cur_codigo` varchar(14) NOT NULL,
  `insc_gestion` varchar(10) NOT NULL COMMENT 'Año escolar',
  `insc_monto_total` double NOT NULL,
  `insc_monto_pagado` double NOT NULL DEFAULT 0,
  `insc_saldo` double NOT NULL,
  `insc_concepto` text DEFAULT NULL,
  `insc_estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activa, 2=Pagada, 0=Cancelada',
  `insc_fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `insc_usuario` varchar(14) NOT NULL,
  PRIMARY KEY (`insc_id`),
  UNIQUE KEY `insc_codigo` (`insc_codigo`),
  KEY `est_codigo` (`est_codigo`),
  KEY `pfam_codigo` (`pfam_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `inscripciones_pagos` (
  `inscpago_id` int(7) NOT NULL AUTO_INCREMENT,
  `inscpago_codigo` varchar(20) NOT NULL,
  `insc_codigo` varchar(14) NOT NULL,
  `inscpago_monto` double NOT NULL,
  `inscpago_concepto` varchar(200) DEFAULT NULL,
  `inscpago_fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `inscpago_usuario` varchar(14) NOT NULL,
  `inscpago_recibo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`inscpago_id`),
  UNIQUE KEY `inscpago_codigo` (`inscpago_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
