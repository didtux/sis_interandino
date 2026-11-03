-- ============================================================================
-- Numeración de recibos sin duplicados (Fase B)
-- ----------------------------------------------------------------------------
-- El número de recibo se calculaba como MAX(código)+1 sin bloqueo: dos cajeras
-- cobrando al mismo tiempo podían sacar el mismo número. Desde ahora cada serie
-- tiene un contador y App\Services\ReciboNumerador lo incrementa con
-- SELECT ... FOR UPDATE dentro de una transacción.
--
-- Se mantienen las series y formatos existentes:
--   MEN-REC / MEN-TAL  mensualidades           REC00001 / TAL00001 (5 dígitos)
--   INS-REC / INS-TAL  pago de inscripción     REC000001 / TAL000001 (6 dígitos)
-- El contador arranca en el número más alto ya emitido. Idempotente: si la
-- serie ya existe, sólo la sube si alguien emitió números más altos por fuera.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS recibo_secuencias (
    serie    VARCHAR(10) PRIMARY KEY,
    prefijo  VARCHAR(5)  NOT NULL,
    digitos  TINYINT     NOT NULL,
    ultimo   INT         NOT NULL DEFAULT 0,
    descripcion VARCHAR(120) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO recibo_secuencias (serie, prefijo, digitos, ultimo, descripcion) VALUES
 ('MEN-REC', 'REC', 5, 0, 'Mensualidades con factura'),
 ('MEN-TAL', 'TAL', 5, 0, 'Mensualidades sin factura (talonario)'),
 ('INS-REC', 'REC', 6, 0, 'Pago de inscripción con factura'),
 ('INS-TAL', 'TAL', 6, 0, 'Pago de inscripción sin factura (talonario)')
ON DUPLICATE KEY UPDATE serie = serie;

UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(pagos_codigo, 4) AS UNSIGNED)) FROM pagos_mensualidades
    WHERE pagos_codigo REGEXP '^REC[0-9]{5}$'), 0)) WHERE s.serie = 'MEN-REC';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(pagos_codigo, 4) AS UNSIGNED)) FROM pagos_mensualidades
    WHERE pagos_codigo REGEXP '^TAL[0-9]{5}$'), 0)) WHERE s.serie = 'MEN-TAL';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(inscpago_recibo, 4) AS UNSIGNED)) FROM inscripciones_pagos
    WHERE inscpago_recibo REGEXP '^REC[0-9]{6}$'), 0)) WHERE s.serie = 'INS-REC';
UPDATE recibo_secuencias s SET ultimo = GREATEST(s.ultimo, COALESCE((
    SELECT MAX(CAST(SUBSTRING(inscpago_recibo, 4) AS UNSIGNED)) FROM inscripciones_pagos
    WHERE inscpago_recibo REGEXP '^TAL[0-9]{6}$'), 0)) WHERE s.serie = 'INS-TAL';

SELECT * FROM recibo_secuencias;
