-- ============================================================================
-- Mensajes del formulario de contacto de la página pública
-- ----------------------------------------------------------------------------
-- El formulario de /contacto no enviaba nada: no tenía action ni nombres en los
-- campos, y el JavaScript mostraba "¡Mensaje recibido!" sin guardar el mensaje.
-- "Solicitar inscripción" lleva a este formulario con el motivo INSCRIPCION.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
