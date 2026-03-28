-- =============================================
-- Sistema de Roles y Permisos
-- =============================================

-- Tabla de Roles
CREATE TABLE IF NOT EXISTS `rol_roles` (
    `rol_id` INT(3) NOT NULL AUTO_INCREMENT,
    `rol_nombre` VARCHAR(50) NOT NULL,
    `rol_descripcion` VARCHAR(200) NULL,
    `rol_visible` INT(1) NOT NULL DEFAULT 1,
    `rol_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Módulos del sistema
CREATE TABLE IF NOT EXISTS `rol_modulos` (
    `mod_id` INT(5) NOT NULL AUTO_INCREMENT,
    `mod_nombre` VARCHAR(50) NOT NULL,
    `mod_slug` VARCHAR(50) NOT NULL,
    `mod_icono` VARCHAR(50) NULL,
    `mod_padre_id` INT(5) NULL DEFAULT NULL,
    `mod_orden` INT(3) NOT NULL DEFAULT 0,
    `mod_visible` INT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`mod_id`),
    UNIQUE KEY `uk_mod_slug` (`mod_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Permisos (rol + módulo + acciones)
CREATE TABLE IF NOT EXISTS `rol_permisos` (
    `perm_id` INT(7) NOT NULL AUTO_INCREMENT,
    `rol_id` INT(3) NOT NULL,
    `mod_id` INT(5) NOT NULL,
    `perm_ver` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_crear` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_editar` TINYINT(1) NOT NULL DEFAULT 0,
    `perm_eliminar` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`perm_id`),
    UNIQUE KEY `uk_rol_modulo` (`rol_id`, `mod_id`),
    CONSTRAINT `fk_perm_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol_roles`(`rol_id`),
    CONSTRAINT `fk_perm_mod` FOREIGN KEY (`mod_id`) REFERENCES `rol_modulos`(`mod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columnas de entidad a rol_usuarios
ALTER TABLE `rol_usuarios`
    ADD COLUMN `us_entidad_tipo` ENUM('admin','docente','padre','chofer') NULL DEFAULT NULL AFTER `us_visible`,
    ADD COLUMN `us_entidad_id` VARCHAR(20) NULL DEFAULT NULL AFTER `us_entidad_tipo`;

-- =============================================
-- Datos iniciales: Roles
-- =============================================
INSERT INTO `rol_roles` (`rol_id`, `rol_nombre`, `rol_descripcion`) VALUES
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Docente', 'Acceso a módulos de docencia'),
(3, 'Padre de Familia', 'Acceso a información de sus hijos'),
(4, 'Chofer', 'Acceso a módulo de transporte'),
(5, 'Caja', 'Acceso a pagos y ventas'),
(6, 'Ventas', 'Acceso a módulo de ventas y almacén');

-- =============================================
-- Datos iniciales: Módulos (extraídos del menú)
-- =============================================
INSERT INTO `rol_modulos` (`mod_id`, `mod_nombre`, `mod_slug`, `mod_icono`, `mod_padre_id`, `mod_orden`) VALUES
-- Módulos principales
(1,  'Dashboard',           'home',                  'fas fa-home',              NULL, 1),
(2,  'Usuarios',            'usuarios',              'fas fa-users-cog',         NULL, 2),
(3,  'Estudiantes',         'estudiantes',           'fas fa-user-graduate',     NULL, 3),
(4,  'Cursos',              'cursos',                'fas fa-chalkboard',        NULL, 4),
(5,  'Docentes',            'docentes',              'fas fa-chalkboard-teacher',NULL, 5),
(6,  'Materias',            'materias',              'fas fa-book',              NULL, 6),
(7,  'Asistencias',         'asistencias',           'fas fa-clipboard-check',   NULL, 7),
(8,  'Notas',               'notas',                 'fas fa-star',              NULL, 8),
(9,  'Padres de Familia',   'padres',                'fas fa-users',             NULL, 9),
(10, 'Pagos',               'pagos',                 'fas fa-money-bill-wave',   NULL, 10),
(11, 'Ventas y Almacén',    'ventas',                'fas fa-shopping-cart',      NULL, 11),
(12, 'Agenda',              'agenda',                'fas fa-calendar-alt',       NULL, 12),
(13, 'Psicopedagogía',      'psicopedagogia',        'fas fa-brain',             NULL, 13),
(14, 'Enfermería',          'enfermeria',            'fas fa-heartbeat',          NULL, 14),
(15, 'Transporte',          'transporte',            'fas fa-bus',               NULL, 15),
-- Submódulos de Asistencias
(16, 'Registro Asistencia', 'asistencias.registro',  'fas fa-list',              7,  1),
(17, 'Config. Asistencia',  'asistencia-config',     'fas fa-cog',               7,  2),
(18, 'Atrasos',             'asistencia-config.atrasos','fas fa-clock',           7,  3),
(19, 'Permisos',            'asistencia-config.permisos','fas fa-file-alt',       7,  4),
(20, 'Festivos',            'asistencia-config.festivos','fas fa-calendar-day',   7,  5),
(21, 'Reportes Asistencia', 'asistencia-config.reportes','fas fa-file-pdf',       7,  6),
-- Submódulos de Pagos
(22, 'Inscripciones',       'inscripciones',         'fas fa-user-plus',         10, 1),
(23, 'Reportes Inscripción','inscripciones.reportes','fas fa-file-pdf',          10, 2),
(24, 'Mensualidades',       'pagos.mensualidades',   'fas fa-money-check',       10, 3),
(25, 'Descuentos',          'descuentos',            'fas fa-percent',           10, 4),
(26, 'Servicios',           'servicios',             'fas fa-concierge-bell',    10, 5),
(27, 'Pagos Servicios',     'pagos-servicios',       'fas fa-file-invoice-dollar',10, 6),
-- Submódulos de Ventas
(28, 'Proveedores',         'proveedores',           'fas fa-truck',             11, 1),
(29, 'Categorías',          'categorias',            'fas fa-tags',              11, 2),
(30, 'Productos',           'productos',             'fas fa-box',              11, 3),
(31, 'Ventas',              'ventas.registro',       'fas fa-cash-register',     11, 4),
(32, 'Movimientos',         'movimientos',           'fas fa-exchange-alt',      11, 5),
(33, 'Reporte Stock',       'movimientos.stock',     'fas fa-warehouse',         11, 6),
-- Submódulos de Transporte
(34, 'Vehículos',           'vehiculos',             'fas fa-car',              15, 1),
(35, 'Choferes',            'choferes',              'fas fa-id-card',          15, 2),
(36, 'Rutas',               'rutas',                 'fas fa-route',            15, 3),
(37, 'Asignaciones Transp.','asignaciones-transporte','fas fa-tasks',           15, 4),
(38, 'Pagos Transporte',    'pagos-transporte',      'fas fa-money-bill',       15, 5),
(39, 'Estudiantes Rutas',   'estudiantes-rutas',     'fas fa-users',            15, 6);

-- =============================================
-- Permisos del Administrador (acceso total)
-- =============================================
INSERT INTO `rol_permisos` (`rol_id`, `mod_id`, `perm_ver`, `perm_crear`, `perm_editar`, `perm_eliminar`)
SELECT 1, mod_id, 1, 1, 1, 1 FROM `rol_modulos`;

-- Actualizar rol_id de usuarios existentes al nuevo esquema
UPDATE `rol_usuarios` SET `us_entidad_tipo` = 'admin' WHERE `rol_id` = 1;
