-- Mensualidades: registrar método de pago (efectivo / QR / mixto), comprobante,
-- datos de transferencia (n° y hora, pago vía WhatsApp/distancia) y n° de recibo.
-- No destructivo: columnas con default para no afectar pagos existentes.

ALTER TABLE pagos_mensualidades
  ADD COLUMN pagos_metodo VARCHAR(10) NOT NULL DEFAULT 'EFECTIVO' AFTER pagos_precio,   -- EFECTIVO | QR | MIXTO
  ADD COLUMN pagos_monto_efectivo DOUBLE NOT NULL DEFAULT 0 AFTER pagos_metodo,
  ADD COLUMN pagos_monto_qr DOUBLE NOT NULL DEFAULT 0 AFTER pagos_monto_efectivo,
  ADD COLUMN pagos_comprobante VARCHAR(255) NULL AFTER pagos_monto_qr,                   -- foto/archivo del QR
  ADD COLUMN pagos_transferencia_nro VARCHAR(60) NULL AFTER pagos_comprobante,
  ADD COLUMN pagos_transferencia_hora VARCHAR(20) NULL AFTER pagos_transferencia_nro,
  ADD COLUMN pagos_recibo_nro VARCHAR(40) NULL AFTER pagos_transferencia_hora,
  ADD COLUMN pagos_via_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER pagos_recibo_nro;
