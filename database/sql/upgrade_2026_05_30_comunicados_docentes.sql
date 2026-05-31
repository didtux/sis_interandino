-- Módulo de Comunicados / Documentación requerida a docentes.
-- Dirección crea un comunicado (general o a docentes seleccionados) con fecha límite;
-- el docente sube el archivo desde su usuario; el sistema clasifica la entrega
-- (EN FECHA / FUERA DE FECHA / NO ENTREGÓ) y dirección puede observar o anular.

CREATE TABLE IF NOT EXISTS comunicados_docentes (
  com_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  com_titulo VARCHAR(150) NOT NULL,
  com_descripcion TEXT NULL,
  com_fecha_limite DATE NULL,
  com_requiere_archivo TINYINT(1) NOT NULL DEFAULT 1,
  com_archivo VARCHAR(255) NULL,
  com_para_todos TINYINT(1) NOT NULL DEFAULT 1,
  com_creado_por INT NULL,
  com_creado_por_nombre VARCHAR(150) NULL,
  com_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  com_estado TINYINT(1) NOT NULL DEFAULT 1,
  com_motivo_anulacion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comunicados_destinatarios (
  cd_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  com_id BIGINT UNSIGNED NOT NULL,
  doc_codigo VARCHAR(20) NOT NULL,
  cd_archivo VARCHAR(255) NULL,
  cd_fecha_entrega DATETIME NULL,
  cd_estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
  cd_observacion VARCHAR(255) NULL,
  KEY idx_com (com_id),
  KEY idx_doc (doc_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
