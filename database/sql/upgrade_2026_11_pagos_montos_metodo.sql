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
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

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
