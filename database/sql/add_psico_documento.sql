-- ====================================================================
-- Agrega campo psico_documento (archivo adjunto) a psicopedagogia_casos
-- Fecha: 2026-05-07
-- ====================================================================

ALTER TABLE `psicopedagogia_casos`
    ADD COLUMN IF NOT EXISTS `psico_documento` VARCHAR(255) NULL
        AFTER `psico_observaciones`;


SELECT 'Campo psico_documento agregado.' AS resultado;
