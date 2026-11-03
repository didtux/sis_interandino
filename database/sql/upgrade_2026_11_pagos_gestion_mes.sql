-- ============================================================================
-- Gestión y mes guardados en cada mensualidad (Fase B)
-- ----------------------------------------------------------------------------
-- Hasta ahora la gestión de un pago salía de YEAR(pagos_fecha) y el mes del
-- texto del concepto ("Mensualidad Marzo"). Con la pre-inscripción, una cuota
-- de 2027 cobrada en diciembre de 2026 contaría para 2026. Desde ahora se
-- guardan al cobrar; las filas viejas se completan acá con la misma regla que
-- usaba el sistema (App\Models\Pago::mes_correspondiente).
--
-- También se registra quién anuló, cuándo y por qué (antes sólo pagos_estado=0).
-- Idempotente.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

ALTER TABLE pagos_mensualidades
    ADD COLUMN IF NOT EXISTS pagos_gestion  SMALLINT NULL AFTER pagos_fecha,
    ADD COLUMN IF NOT EXISTS pagos_mes      TINYINT  NULL AFTER pagos_gestion,
    ADD COLUMN IF NOT EXISTS insc_codigo    VARCHAR(14) NULL AFTER pagos_mes,
    ADD COLUMN IF NOT EXISTS anulado_por    INT NULL,
    ADD COLUMN IF NOT EXISTS anulado_fecha  DATETIME NULL,
    ADD COLUMN IF NOT EXISTS anulado_motivo VARCHAR(255) NULL;

ALTER TABLE pagos_mensualidades
    ADD INDEX IF NOT EXISTS idx_pagos_est_gestion (est_codigo, pagos_gestion, pagos_estado);

UPDATE pagos_mensualidades SET pagos_gestion = YEAR(pagos_fecha)
WHERE pagos_gestion IS NULL AND pagos_fecha IS NOT NULL;

-- Mes: primero el nombre del mes en el concepto (en el mismo orden que el
-- modelo), si no, enero cuenta como febrero y febrero–noviembre como su mes.
-- Los cobros genéricos de varios meses ("Mensualidades" de 1000 Bs o más)
-- quedan en NULL: cubren más de un mes y el modelo los reparte por monto.
UPDATE pagos_mensualidades SET pagos_mes = CASE
        WHEN LOWER(TRIM(concepto)) IN ('mensualidades','mensualidad','pago mensualidades') AND pagos_precio >= 1000 THEN NULL
        WHEN concepto LIKE '%Febrero%'    THEN 2
        WHEN concepto LIKE '%Marzo%'      THEN 3
        WHEN concepto LIKE '%Abril%'      THEN 4
        WHEN concepto LIKE '%Mayo%'       THEN 5
        WHEN concepto LIKE '%Junio%'      THEN 6
        WHEN concepto LIKE '%Julio%'      THEN 7
        WHEN concepto LIKE '%Agosto%'     THEN 8
        WHEN concepto LIKE '%Septiembre%' THEN 9
        WHEN concepto LIKE '%Octubre%'    THEN 10
        WHEN concepto LIKE '%Noviembre%'  THEN 11
        WHEN MONTH(pagos_fecha) = 1 THEN 2
        WHEN MONTH(pagos_fecha) BETWEEN 2 AND 11 THEN MONTH(pagos_fecha)
        ELSE NULL END
WHERE pagos_mes IS NULL;

SELECT pagos_gestion, COUNT(*) filas, SUM(pagos_mes IS NULL) sin_mes
FROM pagos_mensualidades GROUP BY pagos_gestion;
