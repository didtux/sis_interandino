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
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
