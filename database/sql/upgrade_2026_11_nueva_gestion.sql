-- ============================================================================
-- Pre-inscripción, promoción, devoluciones y paso a la nueva gestión (Fase D)
-- ----------------------------------------------------------------------------
-- Requiere: upgrade_2026_11_gestion_vigente.sql y upgrade_2026_11_recibo_secuencias.sql
-- Idempotente.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

-- ── Cursos: a qué curso pasa el estudiante si aprueba ────────────────────────
ALTER TABLE colegio_cursos
    ADD COLUMN IF NOT EXISTS cur_siguiente VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS cur_grado     TINYINT NULL,
    ADD COLUMN IF NOT EXISTS cur_paralelo  CHAR(1) NULL;

-- ── Tarifario por gestión (antes el monto anual se escribía a mano) ──────────
CREATE TABLE IF NOT EXISTS colegio_tarifas (
    tar_id          INT AUTO_INCREMENT PRIMARY KEY,
    ges_anio        SMALLINT NOT NULL,
    cur_codigo      VARCHAR(20) NULL COMMENT 'NULL = todo el nivel',
    cur_nivel       VARCHAR(20) NULL COMMENT 'NULL = todo el colegio',
    tar_monto_anual DECIMAL(10,2) NOT NULL,
    tar_cuotas      TINYINT NOT NULL DEFAULT 10,
    tar_mes_inicio  TINYINT NOT NULL DEFAULT 2,
    KEY idx_tar_gestion (ges_anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Historial del estudiante por gestión ────────────────────────────────────
-- La ficha (colegio_estudiantes) tiene un solo curso, el actual. Lo que cambia
-- año a año (curso, inscripción, resultado) queda acá, y los reportes de una
-- gestión pasada leen el curso de esta tabla.
CREATE TABLE IF NOT EXISTS estudiante_gestion (
    eg_id                  INT AUTO_INCREMENT PRIMARY KEY,
    est_codigo             VARCHAR(14) NOT NULL,
    ges_anio               SMALLINT NOT NULL,
    cur_codigo             VARCHAR(20) NOT NULL,
    insc_codigo            VARCHAR(14) NULL,
    eg_estado              ENUM('INSCRITO','RETIRADO') NOT NULL DEFAULT 'INSCRITO',
    eg_resultado           ENUM('PENDIENTE','PROMOVIDO','REPROBADO','RETIRADO','EGRESADO') NOT NULL DEFAULT 'PENDIENTE',
    eg_promedio_final      DECIMAL(5,2) NULL,
    eg_materias_reprobadas TINYINT NULL,
    eg_resultado_manual    TINYINT NOT NULL DEFAULT 0,
    eg_resultado_motivo    VARCHAR(255) NULL,
    eg_resultado_por       INT NULL,
    eg_resultado_fecha     DATETIME NULL,
    UNIQUE KEY uq_eg (est_codigo, ges_anio),
    KEY idx_eg_curso (ges_anio, cur_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pre-inscripción y reserva ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS preinscripciones (
    preinsc_id          INT AUTO_INCREMENT PRIMARY KEY,
    preinsc_codigo      VARCHAR(14) NOT NULL UNIQUE,
    ges_anio            SMALLINT NOT NULL,
    preinsc_tipo        ENUM('ANTIGUO','NUEVO') NOT NULL,
    est_codigo          VARCHAR(14) NULL COMMENT 'NULL mientras el alumno nuevo no se crea',
    pre_nombres         VARCHAR(80) NULL,
    pre_apellidos       VARCHAR(80) NULL,
    pre_ci              VARCHAR(20) NULL,
    pre_fecha_nac       DATE NULL,
    pre_ue_procedencia  VARCHAR(120) NULL,
    pfam_codigo         VARCHAR(14) NULL,
    cur_origen          VARCHAR(20) NULL,
    cur_destino         VARCHAR(20) NOT NULL,
    preinsc_condicional TINYINT NOT NULL DEFAULT 1 COMMENT 'depende del resultado de la gestión actual',
    preinsc_estado      ENUM('RESERVADA','CONFIRMADA','AJUSTAR','CONVERTIDA','ANULADA','DEVUELTA') NOT NULL DEFAULT 'RESERVADA',
    monto_anual_ref     DECIMAL(10,2) NULL,
    desc_id             INT NULL,
    monto_desc_ref      DECIMAL(10,2) NOT NULL DEFAULT 0,
    insc_codigo         VARCHAR(14) NULL,
    preinsc_obs         TEXT NULL,
    preinsc_usuario     INT NULL,
    preinsc_fecha       DATETIME NOT NULL,
    KEY idx_preinsc_gestion (ges_anio, preinsc_estado),
    KEY idx_preinsc_est (est_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS preinscripciones_pagos (
    prepago_id        INT AUTO_INCREMENT PRIMARY KEY,
    preinsc_id        INT NOT NULL,
    prepago_recibo    VARCHAR(14) NOT NULL,
    prepago_monto     DECIMAL(10,2) NOT NULL,
    prepago_metodo    ENUM('EFECTIVO','QR','MIXTO') NOT NULL DEFAULT 'EFECTIVO',
    monto_efectivo    DECIMAL(10,2) NOT NULL DEFAULT 0,
    monto_qr          DECIMAL(10,2) NOT NULL DEFAULT 0,
    transferencia_nro VARCHAR(40) NULL,
    sin_factura       TINYINT NOT NULL DEFAULT 0,
    prepago_estado    TINYINT NOT NULL DEFAULT 1,
    anulado_por       INT NULL,
    anulado_fecha     DATETIME NULL,
    anulado_motivo    VARCHAR(255) NULL,
    prepago_usuario   INT NULL,
    prepago_fecha     DATETIME NOT NULL,
    KEY idx_prepago_preinsc (preinsc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS preinsc_id INT NULL;
ALTER TABLE inscripciones_pagos
    ADD COLUMN IF NOT EXISTS inscpago_estado TINYINT NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS inscpago_origen ENUM('CAJA','RESERVA') NOT NULL DEFAULT 'CAJA';

-- ── Descuentos: por porcentaje o monto fijo, con vigencia ───────────────────
ALTER TABLE descuentos
    ADD COLUMN IF NOT EXISTS desc_tipo ENUM('PORCENTAJE','MONTO') NOT NULL DEFAULT 'PORCENTAJE',
    ADD COLUMN IF NOT EXISTS desc_monto DECIMAL(10,2) NULL,
    ADD COLUMN IF NOT EXISTS desc_gestion SMALLINT NULL COMMENT 'NULL = cualquier gestión',
    ADD COLUMN IF NOT EXISTS desc_vigente_hasta DATE NULL;

-- Los descuentos "por familia" se cargaron con 0,05 % pero se aplicaron como un
-- monto fijo (250 Bs). Recalcularlos por porcentaje daba 2,38 Bs. Pasan a MONTO
-- con el monto que efectivamente se les aplicó (el más frecuente).
UPDATE descuentos d
JOIN (SELECT desc_id, MAX(inscdesc_monto_descuento) monto FROM inscripciones_descuentos
      WHERE inscdesc_monto_descuento >= 50 GROUP BY desc_id) a ON a.desc_id = d.desc_id
SET d.desc_tipo = 'MONTO', d.desc_monto = a.monto
WHERE d.desc_porcentaje < 1 AND d.desc_tipo = 'PORCENTAJE';

-- ── Devoluciones: documento propio, el recibo original no se toca ────────────
CREATE TABLE IF NOT EXISTS devoluciones (
    dev_id          INT AUTO_INCREMENT PRIMARY KEY,
    dev_codigo      VARCHAR(14) NULL UNIQUE COMMENT 'recibo DEV, se asigna al pagar',
    origen_tipo     ENUM('RESERVA','INSCRIPCION','MENSUALIDAD') NOT NULL,
    recibo_origen   VARCHAR(20) NOT NULL,
    est_codigo      VARCHAR(14) NULL,
    preinsc_id      INT NULL,
    pfam_codigo     VARCHAR(14) NULL,
    ges_anio        SMALLINT NULL,
    monto_original  DECIMAL(10,2) NOT NULL,
    monto_retenido  DECIMAL(10,2) NOT NULL DEFAULT 0,
    monto_devuelto  DECIMAL(10,2) NOT NULL,
    dev_motivo      ENUM('REPROBADO','DESISTE','SIN_CUPO','DUPLICADO','OTRO') NOT NULL,
    dev_detalle     TEXT NULL,
    dev_metodo      ENUM('EFECTIVO','QR') NOT NULL DEFAULT 'EFECTIVO',
    dev_estado      ENUM('SOLICITADA','APROBADA','PAGADA','RECHAZADA') NOT NULL DEFAULT 'SOLICITADA',
    solicitado_por  INT NULL, solicitado_fecha DATETIME NULL,
    aprobado_por    INT NULL, aprobado_fecha   DATETIME NULL,
    pagado_por      INT NULL, pagado_fecha     DATETIME NULL,
    rechazo_motivo  VARCHAR(255) NULL,
    KEY idx_dev_estado (dev_estado),
    KEY idx_dev_pagado (pagado_fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Series de recibo nuevas ──────────────────────────────────────────────────
INSERT INTO recibo_secuencias (serie, prefijo, digitos, ultimo, descripcion) VALUES
 ('RES-REC', 'RSV', 5, 0, 'Reserva de cupo (pre-inscripción) con factura'),
 ('RES-TAL', 'TSV', 5, 0, 'Reserva de cupo (pre-inscripción) sin factura'),
 ('DEV',     'DEV', 5, 0, 'Devoluciones'),
 ('PRE',     'PRE', 6, 0, 'Código de pre-inscripción'),
 ('INSC',    'INSC', 6, 0, 'Código de inscripción')
ON DUPLICATE KEY UPDATE serie = serie;
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(insc_codigo, 5) AS UNSIGNED)) FROM inscripciones
    WHERE insc_codigo REGEXP '^INSC[0-9]{6}$'), 0)) WHERE s.serie = 'INSC';

-- ── Parámetros del colegio (decisiones que el sistema no debe fijar) ─────────
CREATE TABLE IF NOT EXISTS sistema_parametros (
    param_clave       VARCHAR(60) PRIMARY KEY,
    param_valor       VARCHAR(255) NULL,
    param_descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO sistema_parametros (param_clave, param_valor, param_descripcion) VALUES
 ('nota_minima_aprobacion', '51', 'Nota mínima para aprobar una materia'),
 ('reprobacion_materias',   '3',  'Cantidad de materias reprobadas a partir de la cual el estudiante repite el año (A DEFINIR POR EL COLEGIO)'),
 ('preinsc_mora',           'AVISO', 'Si el estudiante debe mensualidades al pre-inscribir: AVISO (se avisa y se permite) o BLOQUEO'),
 ('devolucion_retencion_pct', '0', 'Porcentaje de la reserva que se retiene al devolver (A DEFINIR POR EL COLEGIO)');

-- ── Módulos nuevos en la pantalla Roles ─────────────────────────────────────
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Pre-inscripciones', 'preinscripciones', 'fas fa-user-clock', m.mod_id, 22, 1
FROM rol_modulos m WHERE m.mod_slug = 'pagos'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'preinscripciones');
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Devoluciones', 'devoluciones', 'fas fa-undo-alt', m.mod_id, 28, 1
FROM rol_modulos m WHERE m.mod_slug = 'pagos'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'devoluciones');
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Promoción de estudiantes', 'promocion', 'fas fa-level-up-alt', m.mod_id, 45, 1
FROM rol_modulos m WHERE m.mod_slug = 'notas'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'promocion');
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Tarifas y cursos siguientes', 'tarifas', 'fas fa-tags', m.mod_id, 53, 1
FROM rol_modulos m WHERE m.mod_slug = 'parametrizacion'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'tarifas');
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Parámetros del colegio', 'parametros', 'fas fa-sliders-h', m.mod_id, 54, 1
FROM rol_modulos m WHERE m.mod_slug = 'parametrizacion'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'parametros');

-- Quien hoy tiene Inscripciones recibe Pre-inscripciones y Devoluciones con los
-- mismos tildes (caja ya opera inscripciones); quien tiene Notas recibe Promoción
-- en sólo lectura. Aprobar devoluciones y fijar resultados son capacidades aparte.
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT p.rol_id, n.mod_id, p.perm_ver, p.perm_crear, p.perm_editar, p.perm_eliminar, p.perm_anular, p.perm_exportar
FROM rol_permisos p
JOIN rol_modulos o ON o.mod_id = p.mod_id AND o.mod_slug = 'inscripciones'
JOIN rol_modulos n ON n.mod_slug IN ('preinscripciones', 'devoluciones')
WHERE NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = n.mod_id);
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT p.rol_id, n.mod_id, p.perm_ver, 0, 0, 0, 0, p.perm_exportar
FROM rol_permisos p
JOIN rol_modulos o ON o.mod_id = p.mod_id AND o.mod_slug = 'notas'
JOIN rol_modulos n ON n.mod_slug = 'promocion'
WHERE p.rol_id <> 2 AND NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = n.mod_id);

-- ── Historial 2026 inicial: desde las inscripciones activas ─────────────────
INSERT IGNORE INTO estudiante_gestion (est_codigo, ges_anio, cur_codigo, insc_codigo, eg_estado, eg_resultado)
SELECT i.est_codigo, CAST(i.insc_gestion AS UNSIGNED), i.cur_codigo, i.insc_codigo,
       IF(e.est_visible = 0, 'RETIRADO', 'INSCRITO'), IF(e.est_visible = 0, 'RETIRADO', 'PENDIENTE')
FROM inscripciones i
JOIN colegio_estudiantes e ON e.est_codigo = CONVERT(i.est_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE i.insc_estado IN (1, 2);

-- ── Capacidades nuevas (config/permisos.php) ────────────────────────────────
INSERT IGNORE INTO rol_capacidades (rol_id, cap_slug)
SELECT r.rol_id, c.slug FROM rol_roles r JOIN (
          SELECT 'promocion.decidir' slug, 9 rol UNION ALL SELECT 'promocion.decidir', 10
UNION ALL SELECT 'devoluciones.aprobar', 9 UNION ALL SELECT 'devoluciones.aprobar', 13
) c ON c.rol = r.rol_id;

SELECT 'estudiante_gestion' t, ges_anio, COUNT(*) FROM estudiante_gestion GROUP BY ges_anio;
