-- ============================================================================
-- Web pública editable (Fase F): textos de las páginas, bloques (avisos,
-- requisitos de inscripción por nivel, autoridades, línea del tiempo,
-- documentos) y redes sociales del colegio. Idempotente.
-- Si una clave no tiene fila, la página muestra el texto original
-- (config/landing.php), así que la web no cambia hasta que alguien edite.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
