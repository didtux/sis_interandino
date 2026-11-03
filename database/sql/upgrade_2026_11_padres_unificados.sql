-- ============================================================================
-- Registro de padres unificados (Fase B)
-- ----------------------------------------------------------------------------
-- Cada inscripción creaba un padre nuevo aunque ya existiera con el mismo CI:
-- 138 CI tienen más de un pfam_codigo, cada uno con un hijo distinto. Por eso
-- en Caja aparece un solo hijo por padre y el portal muestra datos incompletos.
--
-- La unificación la hace `php artisan padres:unificar` (primero sin --aplicar,
-- que sólo genera un CSV para revisar). Esta tabla guarda cada movimiento
-- origen → destino para poder rastrearlo o revertirlo.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS padres_unificados (
    pu_id      INT AUTO_INCREMENT PRIMARY KEY,
    pu_origen  VARCHAR(14) NOT NULL,
    pu_destino VARCHAR(14) NOT NULL,
    pu_fecha   DATETIME NOT NULL,
    KEY idx_pu_origen (pu_origen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
