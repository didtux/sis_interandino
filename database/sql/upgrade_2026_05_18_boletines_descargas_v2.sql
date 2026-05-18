-- ════════════════════════════════════════════════════════════════════
-- Fase 2 (v2) — Anulación de descargas + vínculo con Pagos
-- Aplicar UNA sola vez después de upgrade_2026_05_18_boletines_descargas.sql
-- ════════════════════════════════════════════════════════════════════

-- Columnas para anular descargas (no se borran físicamente — auditoría completa).
ALTER TABLE `boletines_descargas`
  ADD COLUMN `descarga_anulada`        TINYINT(1)   NOT NULL DEFAULT 0 AFTER `descarga_observacion`,
  ADD COLUMN `descarga_anulada_motivo` VARCHAR(255) NULL               AFTER `descarga_anulada`,
  ADD COLUMN `descarga_anulada_por`    INT          NULL               AFTER `descarga_anulada_motivo`,
  ADD COLUMN `descarga_anulada_at`     DATETIME     NULL               AFTER `descarga_anulada_por`,
  ADD COLUMN `pserv_id_cobro`          BIGINT       NULL               AFTER `descarga_anulada_at`;

-- pserv_id_cobro: FK opcional a pagos_servicios.pserv_id (cuando la copia se cobra).

-- Servicio para facturar reimpresiones.
INSERT IGNORE INTO `servicios`
  (`serv_codigo`, `serv_nombre`, `serv_descripcion`, `serv_costo`, `serv_estado`)
VALUES
  ('REIMPR_BOLETIN',
   'Reimpresion de boletin de calificaciones',
   'Cobro por copia adicional del boletin de un trimestre o anual',
   10.00, 1);
