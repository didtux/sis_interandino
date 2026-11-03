-- ============================================================================
-- SQL CAMBIOS — todos los cambios de base de la 2ª corrección UEPIAB 2026
-- (Fases A a H + ajuste de gestión nueva, reserva y atención por roles)
-- ----------------------------------------------------------------------------
-- Junta, en el orden en que deben correr, los 12 scripts
-- database/sql/upgrade_2026_11_*.sql. Se carga de una sola vez:
--
--     mysql -u USUARIO -p NOMBRE_BASE < sql_cambios.sql
--
-- o en phpMyAdmin: base de datos → Importar → este archivo.
--
-- ANTES DE CORRER:
--   1. Sacar un respaldo completo de la base (mysqldump).
--   2. Deben estar aplicados los scripts anteriores (upgrade_2026_05_* y
--      upgrade_2026_09_*). Éste no los incluye.
--   3. Requiere MariaDB (usa ADD COLUMN IF NOT EXISTS, igual que los scripts
--      anteriores de producción).
--
-- Es idempotente: si se corta a la mitad o se corre dos veces, volver a
-- correrlo completo no duplica nada.
--
-- NO hace la unificación de padres: eso es `php artisan padres:unificar`,
-- que espera la aprobación del colegio.
--
-- Los SELECT sueltos son verificaciones: muestran cómo quedó cada paso.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los
-- acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;


-- ############################################################################
-- 01 / 12 · Fase A · upgrade_2026_11_pagos_montos_metodo.sql
-- ############################################################################
-- ============================================================================
-- Montos por método en mensualidades (Caja diaria)
-- ----------------------------------------------------------------------------
-- Las columnas pagos_monto_efectivo / pagos_monto_qr se agregaron cuando ya
-- había pagos cargados: los anteriores quedaron con 0 y 0 aunque se cobraron.
-- En el reporte de caja de esos días la tarjeta EFECTIVO salía en 0 con un
-- total cobrado distinto de cero (en local: 2.477 pagos, 15/02 al 21/07/2026).
--
-- Sólo toca filas con los DOS montos en 0: el pago mixto ya viene partido y
-- no se modifica. Idempotente: correrlo dos veces no cambia nada más.
-- ============================================================================

-- Antes: cuántas filas se van a corregir
SELECT pagos_metodo, COUNT(*) AS filas, SUM(pagos_precio) AS monto
FROM pagos_mensualidades
WHERE pagos_monto_efectivo = 0 AND pagos_monto_qr = 0 AND pagos_precio > 0
GROUP BY pagos_metodo;

UPDATE pagos_mensualidades
   SET pagos_monto_efectivo = pagos_precio
 WHERE pagos_metodo = 'EFECTIVO'
   AND pagos_monto_efectivo = 0 AND pagos_monto_qr = 0 AND pagos_precio > 0;

UPDATE pagos_mensualidades
   SET pagos_monto_qr = pagos_precio
 WHERE pagos_metodo = 'QR'
   AND pagos_monto_efectivo = 0 AND pagos_monto_qr = 0 AND pagos_precio > 0;

-- Después: debe dar 0 filas descuadradas fuera de MIXTO
SELECT pagos_metodo, COUNT(*) AS descuadradas
FROM pagos_mensualidades
WHERE ROUND(pagos_monto_efectivo + pagos_monto_qr, 2) <> ROUND(pagos_precio, 2)
GROUP BY pagos_metodo;


-- ############################################################################
-- 02 / 12 · Fase A · upgrade_2026_11_contacto_mensajes.sql
-- ############################################################################
-- ============================================================================
-- Mensajes del formulario de contacto de la página pública
-- ----------------------------------------------------------------------------
-- El formulario de /contacto no enviaba nada: no tenía action ni nombres en los
-- campos, y el JavaScript mostraba "¡Mensaje recibido!" sin guardar el mensaje.
-- "Solicitar inscripción" lleva a este formulario con el motivo INSCRIPCION.
-- ============================================================================

CREATE TABLE IF NOT EXISTS contacto_mensajes (
    cm_id            INT AUTO_INCREMENT PRIMARY KEY,
    cm_nombre        VARCHAR(120) NOT NULL,
    cm_telefono      VARCHAR(30)  NULL,
    cm_correo        VARCHAR(120) NULL,
    cm_motivo        ENUM('INSCRIPCION','NIVEL_INICIAL','NIVEL_PRIMARIA','NIVEL_SECUNDARIA',
                          'PENSIONES','DOCUMENTOS','INFORMACION','OTRO') NOT NULL DEFAULT 'INFORMACION',
    cm_curso_interes VARCHAR(80)  NULL,
    cm_mensaje       TEXT NOT NULL,
    cm_estado        ENUM('NUEVO','EN_PROCESO','ATENDIDO','DESCARTADO') NOT NULL DEFAULT 'NUEVO',
    cm_nota_interna  TEXT NULL,
    cm_atendido_por  INT NULL,
    cm_atendido_at   DATETIME NULL,
    cm_ip            VARCHAR(45) NULL,
    cm_user_agent    VARCHAR(255) NULL,
    created_at       DATETIME NULL,
    updated_at       DATETIME NULL,
    KEY idx_cm_estado (cm_estado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Módulo en la pantalla Roles para dar acceso a la bandeja.
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Mensajes de contacto', 'contacto-mensajes', 'fas fa-envelope-open-text', NULL, 42, 1
WHERE NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'contacto-mensajes');

-- Secretaría (11) y Dirección (9, 10) reciben la bandeja con permiso de ver y
-- editar (cambiar el estado). El administrador no necesita fila.
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar)
SELECT r.rol_id, m.mod_id, 1, 0, 1, 0
FROM rol_roles r
JOIN rol_modulos m ON m.mod_slug = 'contacto-mensajes'
WHERE r.rol_id IN (9, 10, 11)
  AND NOT EXISTS (SELECT 1 FROM rol_permisos p WHERE p.rol_id = r.rol_id AND p.mod_id = m.mod_id);


-- ############################################################################
-- 03 / 12 · Fase B · upgrade_2026_11_gestion_vigente.sql
-- ############################################################################
-- ============================================================================
-- Gestión vigente leída de la base (Fase B)
-- ----------------------------------------------------------------------------
-- Hasta ahora el año en curso salía del reloj (date('Y')) en más de cien
-- lugares y colegio_gestiones.ges_estado no lo leía nadie: el 1 de enero el
-- sistema pasaba a 2027 solo, con caja sin poder cobrar deudas de 2026 y las
-- listas de curso vacías. Desde este script la gestión la decide el colegio.
--
-- Tres estados independientes:
--   ges_estado = 1            gestión académica VIGENTE (una sola)
--   ges_inscripcion_abierta   se puede pre-inscribir/inscribir para esa gestión
--                             aunque todavía no sea la vigente (2027 en noviembre)
--   ges_cerrada               no admite notas ni cobros nuevos
-- ============================================================================

ALTER TABLE colegio_gestiones
    ADD COLUMN IF NOT EXISTS ges_inscripcion_abierta TINYINT NOT NULL DEFAULT 0 AFTER ges_estado,
    ADD COLUMN IF NOT EXISTS ges_preinsc_desde DATE NULL AFTER ges_inscripcion_abierta,
    ADD COLUMN IF NOT EXISTS ges_preinsc_hasta DATE NULL AFTER ges_preinsc_desde,
    ADD COLUMN IF NOT EXISTS ges_fecha_inicio DATE NULL AFTER ges_preinsc_hasta,
    ADD COLUMN IF NOT EXISTS ges_fecha_fin DATE NULL AFTER ges_fecha_inicio,
    ADD COLUMN IF NOT EXISTS ges_cerrada TINYINT NOT NULL DEFAULT 0 AFTER ges_fecha_fin,
    ADD COLUMN IF NOT EXISTS ges_monto_reserva DECIMAL(10,2) NOT NULL DEFAULT 300 AFTER ges_cerrada;

-- Si no hay ninguna vigente, se marca la del año actual (la que usaba el reloj).
UPDATE colegio_gestiones SET ges_estado = 1
WHERE ges_anio = YEAR(CURDATE())
  AND NOT EXISTS (SELECT 1 FROM (SELECT ges_id FROM colegio_gestiones WHERE ges_estado = 1) x);

SELECT ges_anio, ges_estado, ges_inscripcion_abierta, ges_cerrada FROM colegio_gestiones ORDER BY ges_anio;


-- ############################################################################
-- 04 / 12 · Fase B · upgrade_2026_11_pagos_gestion_mes.sql
-- ############################################################################
-- ============================================================================
-- Gestión y mes guardados en cada mensualidad (Fase B)
-- ----------------------------------------------------------------------------
-- Hasta ahora la gestión de un pago salía de YEAR(pagos_fecha) y el mes del
-- texto del concepto ("Mensualidad Marzo"). Con la pre-inscripción, una cuota
-- de 2027 cobrada en diciembre de 2026 contaría para 2026. Desde ahora se
-- guardan al cobrar; las filas viejas se completan acá con la misma regla que
-- usaba el sistema (App\Models\Pago::mes_correspondiente).
--
-- También se registra quién anuló, cuándo y por qué (antes sólo pagos_estado=0).
-- Idempotente.
-- ============================================================================

ALTER TABLE pagos_mensualidades
    ADD COLUMN IF NOT EXISTS pagos_gestion  SMALLINT NULL AFTER pagos_fecha,
    ADD COLUMN IF NOT EXISTS pagos_mes      TINYINT  NULL AFTER pagos_gestion,
    ADD COLUMN IF NOT EXISTS insc_codigo    VARCHAR(14) NULL AFTER pagos_mes,
    ADD COLUMN IF NOT EXISTS anulado_por    INT NULL,
    ADD COLUMN IF NOT EXISTS anulado_fecha  DATETIME NULL,
    ADD COLUMN IF NOT EXISTS anulado_motivo VARCHAR(255) NULL;

ALTER TABLE pagos_mensualidades
    ADD INDEX IF NOT EXISTS idx_pagos_est_gestion (est_codigo, pagos_gestion, pagos_estado);

UPDATE pagos_mensualidades SET pagos_gestion = YEAR(pagos_fecha)
WHERE pagos_gestion IS NULL AND pagos_fecha IS NOT NULL;

-- Mes: primero el nombre del mes en el concepto (en el mismo orden que el
-- modelo), si no, enero cuenta como febrero y febrero–noviembre como su mes.
-- Los cobros genéricos de varios meses ("Mensualidades" de 1000 Bs o más)
-- quedan en NULL: cubren más de un mes y el modelo los reparte por monto.
UPDATE pagos_mensualidades SET pagos_mes = CASE
        WHEN LOWER(TRIM(concepto)) IN ('mensualidades','mensualidad','pago mensualidades') AND pagos_precio >= 1000 THEN NULL
        WHEN concepto LIKE '%Febrero%'    THEN 2
        WHEN concepto LIKE '%Marzo%'      THEN 3
        WHEN concepto LIKE '%Abril%'      THEN 4
        WHEN concepto LIKE '%Mayo%'       THEN 5
        WHEN concepto LIKE '%Junio%'      THEN 6
        WHEN concepto LIKE '%Julio%'      THEN 7
        WHEN concepto LIKE '%Agosto%'     THEN 8
        WHEN concepto LIKE '%Septiembre%' THEN 9
        WHEN concepto LIKE '%Octubre%'    THEN 10
        WHEN concepto LIKE '%Noviembre%'  THEN 11
        WHEN MONTH(pagos_fecha) = 1 THEN 2
        WHEN MONTH(pagos_fecha) BETWEEN 2 AND 11 THEN MONTH(pagos_fecha)
        ELSE NULL END
WHERE pagos_mes IS NULL;

SELECT pagos_gestion, COUNT(*) filas, SUM(pagos_mes IS NULL) sin_mes
FROM pagos_mensualidades GROUP BY pagos_gestion;


-- ############################################################################
-- 05 / 12 · Fase B · upgrade_2026_11_recibo_secuencias.sql
-- ############################################################################
-- ============================================================================
-- Numeración de recibos sin duplicados (Fase B)
-- ----------------------------------------------------------------------------
-- El número de recibo se calculaba como MAX(código)+1 sin bloqueo: dos cajeras
-- cobrando al mismo tiempo podían sacar el mismo número. Desde ahora cada serie
-- tiene un contador y App\Services\ReciboNumerador lo incrementa con
-- SELECT ... FOR UPDATE dentro de una transacción.
--
-- Se mantienen las series y formatos existentes:
--   MEN-REC / MEN-TAL  mensualidades           REC00001 / TAL00001 (5 dígitos)
--   INS-REC / INS-TAL  pago de inscripción     REC000001 / TAL000001 (6 dígitos)
-- El contador arranca en el número más alto ya emitido. Idempotente: si la
-- serie ya existe, sólo la sube si alguien emitió números más altos por fuera.
-- ============================================================================

CREATE TABLE IF NOT EXISTS recibo_secuencias (
    serie    VARCHAR(10) PRIMARY KEY,
    prefijo  VARCHAR(5)  NOT NULL,
    digitos  TINYINT     NOT NULL,
    ultimo   INT         NOT NULL DEFAULT 0,
    descripcion VARCHAR(120) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO recibo_secuencias (serie, prefijo, digitos, ultimo, descripcion) VALUES
 ('MEN-REC', 'REC', 5, 0, 'Mensualidades con factura'),
 ('MEN-TAL', 'TAL', 5, 0, 'Mensualidades sin factura (talonario)'),
 ('INS-REC', 'REC', 6, 0, 'Pago de inscripción con factura'),
 ('INS-TAL', 'TAL', 6, 0, 'Pago de inscripción sin factura (talonario)')
ON DUPLICATE KEY UPDATE serie = serie;

UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(pagos_codigo, 4) AS UNSIGNED)) FROM pagos_mensualidades
    WHERE pagos_codigo REGEXP '^REC[0-9]{5}$'), 0)) WHERE s.serie = 'MEN-REC';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(pagos_codigo, 4) AS UNSIGNED)) FROM pagos_mensualidades
    WHERE pagos_codigo REGEXP '^TAL[0-9]{5}$'), 0)) WHERE s.serie = 'MEN-TAL';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(inscpago_recibo, 4) AS UNSIGNED)) FROM inscripciones_pagos
    WHERE inscpago_recibo REGEXP '^REC[0-9]{6}$'), 0)) WHERE s.serie = 'INS-REC';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(inscpago_recibo, 4) AS UNSIGNED)) FROM inscripciones_pagos
    WHERE inscpago_recibo REGEXP '^TAL[0-9]{6}$'), 0)) WHERE s.serie = 'INS-TAL';

SELECT * FROM recibo_secuencias;


-- ############################################################################
-- 06 / 12 · Fase B · upgrade_2026_11_padres_unificados.sql
-- ############################################################################
-- ============================================================================
-- Registro de padres unificados (Fase B)
-- ----------------------------------------------------------------------------
-- Cada inscripción creaba un padre nuevo aunque ya existiera con el mismo CI:
-- 138 CI tienen más de un pfam_codigo, cada uno con un hijo distinto. Por eso
-- en Caja aparece un solo hijo por padre y el portal muestra datos incompletos.
--
-- La unificación la hace `php artisan padres:unificar` (primero sin --aplicar,
-- que sólo genera un CSV para revisar). Esta tabla guarda cada movimiento
-- origen → destino para poder rastrearlo o revertirlo.
-- ============================================================================
CREATE TABLE IF NOT EXISTS padres_unificados (
    pu_id      INT AUTO_INCREMENT PRIMARY KEY,
    pu_origen  VARCHAR(14) NOT NULL,
    pu_destino VARCHAR(14) NOT NULL,
    pu_fecha   DATETIME NOT NULL,
    KEY idx_pu_origen (pu_origen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################################
-- 07 / 12 · Fase C · upgrade_2026_11_rol_capacidades.sql
-- ############################################################################
-- ============================================================================
-- Roles y permisos: capacidades especiales y acciones anular/exportar (Fase C)
-- ----------------------------------------------------------------------------
-- 1) rol_capacidades: decisiones que estaban escritas en el código como listas
--    de roles (in_array($rol, [1,9,10,11])) y ningún tilde podía cambiar. El
--    catálogo está en config/permisos.php. Acá se otorgan a los roles que hoy
--    tienen el acceso, para que nadie pierda nada.
-- 2) rol_permisos.perm_anular / perm_exportar: acciones que antes se resolvían
--    como "editar" y "ver". Arrancan copiando esos valores (mismo acceso que hoy)
--    y desde la pantalla Roles se pueden separar.
-- Idempotente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS rol_capacidades (
    rc_id     INT AUTO_INCREMENT PRIMARY KEY,
    rol_id    INT NOT NULL,
    cap_slug  VARCHAR(60) NOT NULL,
    UNIQUE KEY uq_rol_cap (rol_id, cap_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rol_capacidades (rol_id, cap_slug)
SELECT r.rol_id, c.slug FROM rol_roles r JOIN (
          SELECT 'observados.autorizar' slug, 9 rol UNION ALL SELECT 'observados.autorizar', 10
UNION ALL SELECT 'observados.gestionar', 9 UNION ALL SELECT 'observados.gestionar', 10
UNION ALL SELECT 'observados.gestionar', 11 UNION ALL SELECT 'observados.gestionar', 13
UNION ALL SELECT 'alertas.direccion', 9 UNION ALL SELECT 'alertas.direccion', 10
UNION ALL SELECT 'boletin.reimpresiones', 9 UNION ALL SELECT 'boletin.reimpresiones', 10 UNION ALL SELECT 'boletin.reimpresiones', 11
UNION ALL SELECT 'comunicados.gestionar', 9 UNION ALL SELECT 'comunicados.gestionar', 10 UNION ALL SELECT 'comunicados.gestionar', 11
UNION ALL SELECT 'kardex_docente.gestionar', 9 UNION ALL SELECT 'kardex_docente.gestionar', 10 UNION ALL SELECT 'kardex_docente.gestionar', 11
UNION ALL SELECT 'kardex_estudiante.direccion', 9 UNION ALL SELECT 'kardex_estudiante.direccion', 10
UNION ALL SELECT 'kardex_estudiante.direccion', 11 UNION ALL SELECT 'kardex_estudiante.direccion', 12
UNION ALL SELECT 'kardex_estudiante.direccion', 14
) c ON c.rol = r.rol_id;

ALTER TABLE rol_permisos
    ADD COLUMN IF NOT EXISTS perm_anular   TINYINT NOT NULL DEFAULT 0 AFTER perm_eliminar,
    ADD COLUMN IF NOT EXISTS perm_exportar TINYINT NOT NULL DEFAULT 0 AFTER perm_anular;

-- Sólo la primera vez (columnas recién creadas, todo en 0): mismo acceso que hoy.
UPDATE rol_permisos SET perm_anular = perm_editar, perm_exportar = perm_ver
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM rol_permisos WHERE perm_anular = 1 OR perm_exportar = 1 LIMIT 1) x);

SELECT cap_slug, GROUP_CONCAT(rol_id ORDER BY rol_id) roles FROM rol_capacidades GROUP BY cap_slug;


-- ############################################################################
-- 08 / 12 · Fase D · upgrade_2026_11_nueva_gestion.sql
-- ############################################################################
-- ============================================================================
-- Pre-inscripción, promoción, devoluciones y paso a la nueva gestión (Fase D)
-- ----------------------------------------------------------------------------
-- Requiere: upgrade_2026_11_gestion_vigente.sql y upgrade_2026_11_recibo_secuencias.sql
-- Idempotente.
-- ============================================================================

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


-- ############################################################################
-- 09 / 12 · Fase E · upgrade_2026_11_portal_padres.sql
-- ############################################################################
-- ============================================================================
-- Portal de padres (Fase E): bloqueo de notas por mora y publicaciones del
-- colegio.
-- Requiere upgrade_2026_11_nueva_gestion.sql (sistema_parametros). Idempotente.
-- ============================================================================

-- ── Bloqueo de notas por mora ───────────────────────────────────────────────
INSERT IGNORE INTO sistema_parametros (param_clave, param_valor, param_descripcion) VALUES
 ('bloqueo_mora_activo',  '0',    'Bloquear las notas en el portal de padres por mora (1 = sí)'),
 ('bloqueo_mora_modo',    'AMBOS','Bloqueo: AUTO (por meses adeudados), MANUAL (sólo los que marque caja) o AMBOS'),
 ('bloqueo_meses_minimo', '2',    'Meses de mensualidad adeudados a partir de los cuales se bloquean las notas'),
 ('bloqueo_desde_mes',    '3',    'Mes del año escolar desde el que se aplica el bloqueo automático (3 = marzo)'),
 ('bloqueo_mensaje',      'Las calificaciones no están disponibles porque hay mensualidades pendientes. Regularice el pago en caja.', 'Mensaje que ve el padre cuando las notas están bloqueadas');

CREATE TABLE IF NOT EXISTS estudiante_bloqueo_notas (
    bloq_id        INT AUTO_INCREMENT PRIMARY KEY,
    est_codigo     VARCHAR(14) NOT NULL,
    ges_anio       SMALLINT NOT NULL,
    bloq_tipo      ENUM('BLOQUEAR','EXONERAR') NOT NULL,
    bloq_motivo    VARCHAR(255) NOT NULL,
    vigente_hasta  DATE NULL,
    bloq_activo    TINYINT NOT NULL DEFAULT 1,
    bloq_usuario   INT NULL,
    bloq_fecha     DATETIME NOT NULL,
    KEY idx_bloq_est (est_codigo, ges_anio, bloq_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Publicaciones del colegio para el portal ────────────────────────────────
-- Revista, lista de útiles, reglamento, horarios, misión y visión, agenda.
CREATE TABLE IF NOT EXISTS publicaciones_institucionales (
    pub_id             INT AUTO_INCREMENT PRIMARY KEY,
    pub_tipo           ENUM('REVISTA','LISTA_UTILES','REGLAMENTO','HORARIO','MISION_VISION','AGENDA','OTRO') NOT NULL,
    pub_titulo         VARCHAR(150) NOT NULL,
    pub_descripcion    TEXT NULL,
    ges_anio           SMALLINT NULL COMMENT 'NULL = vale para todas las gestiones',
    niv_nombre         VARCHAR(20) NULL COMMENT 'INICIAL / PRIMARIA / SECUNDARIA; NULL = todos',
    cur_codigo         VARCHAR(20) NULL COMMENT 'NULL = todos los cursos (del nivel)',
    pub_formato        ENUM('PDF','IMAGEN','HTML','LINK') NOT NULL DEFAULT 'PDF',
    pub_archivo        VARCHAR(255) NULL,
    pub_contenido_html MEDIUMTEXT NULL,
    pub_url            VARCHAR(255) NULL,
    visible_padres     TINYINT NOT NULL DEFAULT 1,
    publicado_desde    DATE NULL,
    publicado_hasta    DATE NULL,
    pub_orden          INT NOT NULL DEFAULT 0,
    pub_estado         TINYINT NOT NULL DEFAULT 1,
    pub_usuario        INT NULL,
    pub_fecha          DATETIME NOT NULL,
    KEY idx_pub_tipo (pub_tipo, ges_anio, pub_estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Módulos en la pantalla Roles ────────────────────────────────────────────
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Bloqueo de notas por mora', 'portal-bloqueos', 'fas fa-user-lock', m.mod_id, 30, 1
FROM rol_modulos m WHERE m.mod_slug = 'pagos'
  AND NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'portal-bloqueos');
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Publicaciones del colegio', 'publicaciones', 'fas fa-book-open', NULL, 43, 1
WHERE NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'publicaciones');

-- Bloqueos: quien cobra mensualidades. Publicaciones: quien
-- maneja la Agenda (dirección y secretaría).
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT p.rol_id, n.mod_id, p.perm_ver, p.perm_crear, p.perm_editar, p.perm_eliminar, p.perm_anular, p.perm_exportar
FROM rol_permisos p
JOIN rol_modulos o ON o.mod_id = p.mod_id AND o.mod_slug = 'pagos.mensualidades'
JOIN rol_modulos n ON n.mod_slug = 'portal-bloqueos'
WHERE NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = n.mod_id);
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT p.rol_id, n.mod_id, p.perm_ver, p.perm_crear, p.perm_editar, p.perm_eliminar, p.perm_anular, p.perm_exportar
FROM rol_permisos p
JOIN rol_modulos o ON o.mod_id = p.mod_id AND o.mod_slug = 'agenda'
JOIN rol_modulos n ON n.mod_slug = 'publicaciones'
WHERE NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = n.mod_id);

SELECT mod_slug, (SELECT COUNT(*) FROM rol_permisos p WHERE p.mod_id = m.mod_id) roles
FROM rol_modulos m WHERE mod_slug IN ('portal-bloqueos', 'publicaciones');


-- ############################################################################
-- 10 / 12 · Fase F · upgrade_2026_11_web_publica.sql
-- ############################################################################
-- ============================================================================
-- Web pública editable (Fase F): textos de las páginas, bloques (avisos,
-- requisitos de inscripción por nivel, autoridades, línea del tiempo,
-- documentos) y redes sociales del colegio. Idempotente.
-- Si una clave no tiene fila, la página muestra el texto original
-- (config/landing.php), así que la web no cambia hasta que alguien edite.
-- ============================================================================

-- Textos fijos de cada página (título del carrusel, misión, visión…)
CREATE TABLE IF NOT EXISTS landing_secciones (
    ls_clave       VARCHAR(100) NOT NULL PRIMARY KEY COMMENT 'p.ej. inicio.slide1.titulo (ver config/landing.php)',
    ls_valor       MEDIUMTEXT NULL,
    ls_usuario     INT NULL,
    ls_fecha       DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bloques repetibles: tantos como el colegio quiera, con vigencia
CREATE TABLE IF NOT EXISTS landing_bloques (
    lb_id          INT AUTO_INCREMENT PRIMARY KEY,
    lb_seccion     ENUM('AVISO','REQUISITO','DOCUMENTO','AUTORIDAD','HITO') NOT NULL,
    lb_titulo      VARCHAR(150) NOT NULL,
    lb_subtitulo   VARCHAR(150) NULL COMMENT 'cargo de la autoridad, año del hito, tipo de alumno del requisito',
    lb_texto       MEDIUMTEXT NULL,
    lb_imagen      VARCHAR(255) NULL,
    lb_archivo     VARCHAR(255) NULL,
    lb_link        VARCHAR(255) NULL,
    lb_link_texto  VARCHAR(60) NULL,
    lb_fecha       DATE NULL,
    niv_nombre     VARCHAR(20) NULL COMMENT 'INICIAL / PRIMARIA / SECUNDARIA; NULL = todos',
    ges_anio       SMALLINT NULL COMMENT 'NULL = toda gestión',
    vigente_desde  DATE NULL,
    vigente_hasta  DATE NULL,
    lb_orden       INT NOT NULL DEFAULT 0,
    lb_activo      TINYINT NOT NULL DEFAULT 1,
    lb_usuario     INT NULL,
    lb_fecha_registro DATETIME NOT NULL,
    KEY idx_lb_seccion (lb_seccion, lb_activo, lb_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Redes sociales y horario de atención (el footer hoy tiene enlaces "#")
SET @db = DATABASE();
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sistema_configuracion' AND COLUMN_NAME='config_facebook');
SET @s = IF(@c = 0, 'ALTER TABLE sistema_configuracion
    ADD COLUMN config_facebook  VARCHAR(255) NULL,
    ADD COLUMN config_instagram VARCHAR(255) NULL,
    ADD COLUMN config_tiktok    VARCHAR(255) NULL,
    ADD COLUMN config_youtube   VARCHAR(255) NULL,
    ADD COLUMN config_whatsapp  VARCHAR(30)  NULL COMMENT ''número con código de país, p.ej. 59171234567'',
    ADD COLUMN config_horario   VARCHAR(150) NULL COMMENT ''horario de atención'',
    ADD COLUMN config_mapa_url  VARCHAR(500) NULL COMMENT ''enlace de Google Maps''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Módulo en la pantalla Roles. Lo administran los roles que atienden los
-- mensajes de la web (dirección y secretaría), con todas las acciones: esa
-- bandeja no tiene "crear", así que copiar sus permisos los dejaría sin poder
-- cargar avisos.
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Web pública', 'web-publica', 'fas fa-globe', NULL, 44, 1
WHERE NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'web-publica');
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT p.rol_id, n.mod_id, p.perm_ver, p.perm_ver, p.perm_ver, p.perm_ver, 0, 0
FROM rol_permisos p
JOIN rol_modulos o ON o.mod_id = p.mod_id AND o.mod_slug = 'contacto-mensajes'
JOIN rol_modulos n ON n.mod_slug = 'web-publica'
WHERE p.perm_ver = 1 AND NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = n.mod_id);

SELECT mod_slug, (SELECT COUNT(*) FROM rol_permisos p WHERE p.mod_id = m.mod_id) roles
FROM rol_modulos m WHERE mod_slug = 'web-publica';


-- ############################################################################
-- 11 / 12 · Fase H · upgrade_2026_11_atencion.sql
-- ############################################################################
-- ============================================================================
-- Canal de atención a padres (Fase H): el padre abre una consulta a un área
-- (secretaría, caja, dirección, psicopedagogía o docentes del curso) y el
-- personal responde en un hilo. No es chat en tiempo real: las pantallas se
-- refrescan solas cada 30 segundos. Idempotente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS atencion_tickets (
    at_id             INT AUTO_INCREMENT PRIMARY KEY,
    at_area           ENUM('SECRETARIA','CAJA','DIRECCION','PSICOPEDAGOGIA','DOCENTE') NOT NULL,
    at_asunto         VARCHAR(150) NOT NULL,
    us_id             INT NOT NULL COMMENT 'usuario del padre que abrió la consulta',
    pfam_codigo       VARCHAR(14) NULL,
    est_codigo        VARCHAR(14) NULL COMMENT 'hijo al que se refiere (obligatorio para docentes)',
    cur_codigo        VARCHAR(20) NULL COMMENT 'curso del hijo al abrir (para los docentes)',
    at_estado         ENUM('ABIERTO','RESPONDIDO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
    at_ultimo_mensaje DATETIME NOT NULL,
    at_cerrado_por    INT NULL,
    at_cerrado_fecha  DATETIME NULL,
    at_fecha          DATETIME NOT NULL,
    KEY idx_at_area (at_area, at_estado, at_ultimo_mensaje),
    KEY idx_at_padre (us_id, at_ultimo_mensaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS atencion_mensajes (
    am_id        INT AUTO_INCREMENT PRIMARY KEY,
    at_id        INT NOT NULL,
    am_autor     ENUM('PADRE','PERSONAL','SISTEMA') NOT NULL,
    us_id        INT NULL,
    am_texto     TEXT NOT NULL,
    am_adjunto   VARCHAR(255) NULL,
    am_leido     TINYINT NOT NULL DEFAULT 0 COMMENT 'leído por la otra parte',
    am_fecha     DATETIME NOT NULL,
    KEY idx_am_ticket (at_id, am_id),
    KEY idx_am_noleido (am_autor, am_leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Módulos: grupo "Atención a padres" y un submódulo por área ─────────────
INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT 'Atención a padres', 'atencion', 'fas fa-comments', NULL, 45, 1
WHERE NOT EXISTS (SELECT 1 FROM rol_modulos WHERE mod_slug = 'atencion');

INSERT INTO rol_modulos (mod_nombre, mod_slug, mod_icono, mod_padre_id, mod_orden, mod_visible)
SELECT x.nombre, x.slug, x.icono, p.mod_id, x.orden, 1
FROM (SELECT 'Atención: Secretaría' nombre, 'atencion.secretaria' slug, 'fas fa-user-tie' icono, 1 orden
      UNION ALL SELECT 'Atención: Caja', 'atencion.caja', 'fas fa-cash-register', 2
      UNION ALL SELECT 'Atención: Dirección', 'atencion.direccion', 'fas fa-school', 3
      UNION ALL SELECT 'Atención: Psicopedagogía', 'atencion.psicopedagogia', 'fas fa-brain', 4
      UNION ALL SELECT 'Atención: Docentes del curso', 'atencion.docente', 'fas fa-chalkboard-teacher', 5) x
JOIN rol_modulos p ON p.mod_slug = 'atencion'
WHERE NOT EXISTS (SELECT 1 FROM rol_modulos m WHERE m.mod_slug = x.slug);

-- Quién atiende cada área (ver = leer, crear = responder, editar = cerrar/derivar).
-- Se ajusta después en Roles. 2 Docente · 5 Caja · 9 Director General ·
-- 10 Directora Académica · 11 Secretaría · 12 Psicopedagogía · 13 Dir. financiero · 14 Regencia
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT x.rol_id, m.mod_id, 1, 1, 1, 0, 0, 0
FROM (SELECT 11 rol_id, 'atencion.secretaria' slug
      UNION ALL SELECT 5,  'atencion.caja'
      UNION ALL SELECT 13, 'atencion.caja'
      UNION ALL SELECT 9,  'atencion.direccion'
      UNION ALL SELECT 10, 'atencion.direccion'
      UNION ALL SELECT 14, 'atencion.direccion'
      UNION ALL SELECT 12, 'atencion.psicopedagogia'
      UNION ALL SELECT 2,  'atencion.docente'
      UNION ALL SELECT 9,  'atencion.docente'
      UNION ALL SELECT 10, 'atencion.docente') x
JOIN rol_modulos m ON m.mod_slug = x.slug
JOIN rol_roles r ON r.rol_id = x.rol_id
WHERE NOT EXISTS (SELECT 1 FROM rol_permisos p WHERE p.rol_id = x.rol_id AND p.mod_id = m.mod_id);
-- El grupo, para los mismos roles (lo usa el menú)
INSERT INTO rol_permisos (rol_id, mod_id, perm_ver, perm_crear, perm_editar, perm_eliminar, perm_anular, perm_exportar)
SELECT DISTINCT p.rol_id, g.mod_id, 1, 0, 0, 0, 0, 0
FROM rol_permisos p
JOIN rol_modulos h ON h.mod_id = p.mod_id AND h.mod_slug LIKE 'atencion.%'
JOIN rol_modulos g ON g.mod_slug = 'atencion'
WHERE NOT EXISTS (SELECT 1 FROM rol_permisos x WHERE x.rol_id = p.rol_id AND x.mod_id = g.mod_id);

SELECT m.mod_slug, GROUP_CONCAT(p.rol_id ORDER BY p.rol_id) roles
FROM rol_modulos m LEFT JOIN rol_permisos p ON p.mod_id = m.mod_id
WHERE m.mod_slug LIKE 'atencion%' GROUP BY m.mod_slug;


-- ############################################################################
-- 12 / 12 · Ajuste gestión y reserva por roles · upgrade_2026_11_gestion_roles_reserva.sql
-- ############################################################################
-- ============================================================================
-- Gestión nueva por roles y monto de reserva parametrizable. Idempotente.
--
-- Monto de la reserva de pre-inscripción, del más específico al más general:
--   1) tarifa del curso destino (o de su nivel) → colegio_tarifas.tar_monto_reserva
--   2) monto fijado en la gestión                → colegio_gestiones.ges_monto_reserva
--   3) parámetro del colegio                     → sistema_parametros.preinsc_monto_reserva
-- Requiere upgrade_2026_11_nueva_gestion.sql y upgrade_2026_11_rol_capacidades.sql.
-- ============================================================================

-- La gestión puede dejar el monto vacío (= usar el parámetro del colegio)
ALTER TABLE colegio_gestiones MODIFY ges_monto_reserva DECIMAL(10,2) NULL DEFAULT NULL;
-- Las gestiones que ya existían recibieron 300 como valor por defecto de la
-- columna, no porque alguien lo fijara: pasan a vacío para que manden el
-- parámetro y la tarifa del curso. Sólo la primera vez (aún no existe el
-- parámetro), para no borrar un 300 que el colegio fije después.
UPDATE colegio_gestiones SET ges_monto_reserva = NULL
WHERE ges_monto_reserva = 300
  AND NOT EXISTS (SELECT 1 FROM sistema_parametros WHERE param_clave = 'preinsc_monto_reserva');

-- Reserva por curso/nivel en Tarifas; el monto anual puede quedar vacío si
-- sólo se fija la reserva
ALTER TABLE colegio_tarifas MODIFY tar_monto_anual DECIMAL(10,2) NULL;
SET @db = DATABASE();
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='colegio_tarifas' AND COLUMN_NAME='tar_monto_reserva');
SET @s = IF(@c = 0, 'ALTER TABLE colegio_tarifas ADD COLUMN tar_monto_reserva DECIMAL(10,2) NULL AFTER tar_monto_anual', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

INSERT IGNORE INTO sistema_parametros (param_clave, param_valor, param_descripcion) VALUES
 ('preinsc_monto_reserva', '300', 'Monto de la reserva de pre-inscripción (Bs) cuando ni la tarifa del curso ni la gestión fijan uno');

-- Capacidades nuevas (se cambian en Roles → Capacidades especiales)
-- 9 Director General · 10 Directora Académica · 13 Dir. financiero
INSERT INTO rol_capacidades (rol_id, cap_slug)
SELECT x.rol_id, x.cap FROM (
    SELECT 9 rol_id, 'gestion.preinscripcion' cap UNION ALL SELECT 10, 'gestion.preinscripcion'
    UNION ALL SELECT 9, 'gestion.montos' UNION ALL SELECT 13, 'gestion.montos'
    UNION ALL SELECT 9, 'preinscripcion.cambiar_monto' UNION ALL SELECT 13, 'preinscripcion.cambiar_monto'
) x
JOIN rol_roles r ON r.rol_id = x.rol_id
WHERE NOT EXISTS (SELECT 1 FROM rol_capacidades c WHERE c.rol_id = x.rol_id AND c.cap_slug = x.cap);

SELECT cap_slug, GROUP_CONCAT(rol_id ORDER BY rol_id) roles FROM rol_capacidades
WHERE cap_slug LIKE 'gestion.%' OR cap_slug LIKE 'preinscripcion.%' GROUP BY cap_slug;


-- ############################################################################
-- Fin. Verificación general: tablas nuevas y módulos de Roles agregados.
-- ############################################################################
SELECT TABLE_NAME AS tabla_nueva FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
  'contacto_mensajes','recibo_secuencias','padres_unificados','rol_capacidades','colegio_tarifas',
  'estudiante_gestion','preinscripciones','preinscripciones_pagos','devoluciones','sistema_parametros',
  'estudiante_bloqueo_notas','publicaciones_institucionales','landing_secciones','landing_bloques',
  'atencion_tickets','atencion_mensajes')
ORDER BY TABLE_NAME;
SELECT mod_slug, mod_nombre FROM rol_modulos WHERE mod_slug IN (
  'contacto-mensajes','preinscripciones','devoluciones','promocion','tarifas','parametros',
  'portal-bloqueos','publicaciones','web-publica') OR mod_slug LIKE 'atencion%'
ORDER BY mod_slug;
