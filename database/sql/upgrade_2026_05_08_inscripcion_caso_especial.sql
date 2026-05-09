-- =====================================================================
-- Upgrade: Inscripción "Caso Especial".
-- Permite registrar a un estudiante con un mes de inicio anterior al actual
-- sin que los meses previos a ese inicio se contabilicen como vencidos.
-- Ej: estudiante registrado en abril pero su periodo arranca en marzo:
--     insc_caso_especial = 1, insc_mes_inicio = 3
-- =====================================================================

ALTER TABLE `inscripciones`
    ADD COLUMN IF NOT EXISTS `insc_caso_especial` TINYINT(1) NOT NULL DEFAULT 0
    AFTER `insc_sin_factura`;

ALTER TABLE `inscripciones`
    ADD COLUMN IF NOT EXISTS `insc_mes_inicio` TINYINT NULL DEFAULT NULL
    AFTER `insc_caso_especial`;
