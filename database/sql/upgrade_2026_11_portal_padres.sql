-- ============================================================================
-- Portal de padres (Fase E): bloqueo de notas por mora y publicaciones del
-- colegio.
-- Requiere upgrade_2026_11_nueva_gestion.sql (sistema_parametros). Idempotente.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
