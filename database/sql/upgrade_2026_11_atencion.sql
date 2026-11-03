-- ============================================================================
-- Canal de atención a padres (Fase H): el padre abre una consulta a un área
-- (secretaría, caja, dirección, psicopedagogía o docentes del curso) y el
-- personal responde en un hilo. No es chat en tiempo real: las pantallas se
-- refrescan solas cada 30 segundos. Idempotente.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
