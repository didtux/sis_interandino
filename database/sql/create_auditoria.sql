CREATE TABLE IF NOT EXISTS sistema_auditoria (
    audit_id INT(11) NOT NULL AUTO_INCREMENT,
    audit_usuario_id INT(7) NOT NULL,
    audit_usuario_nombre VARCHAR(100) NOT NULL,
    audit_accion ENUM('crear','editar','eliminar') NOT NULL,
    audit_modulo VARCHAR(50) NOT NULL,
    audit_descripcion TEXT NOT NULL,
    audit_registro_id VARCHAR(50) NULL,
    audit_datos_anteriores JSON NULL,
    audit_datos_nuevos JSON NULL,
    audit_ip VARCHAR(45) NULL,
    audit_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_usuario (audit_usuario_id),
    KEY idx_modulo (audit_modulo),
    KEY idx_fecha (audit_fecha),
    KEY idx_accion (audit_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
