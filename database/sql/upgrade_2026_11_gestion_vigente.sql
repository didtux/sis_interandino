-- ============================================================================
-- Gestión vigente leída de la base (Fase B)
-- ----------------------------------------------------------------------------
-- Hasta ahora el año en curso salía del reloj (date('Y')) en más de cien
-- lugares y colegio_gestiones.ges_estado no lo leía nadie: el 1 de enero el
-- sistema pasaba a 2027 solo, con caja sin poder cobrar deudas de 2026 y las
-- listas de curso vacías. Desde este script la gestión la decide el colegio.
--
-- Tres estados independientes:
--   ges_estado = 1            gestión académica VIGENTE (una sola)
--   ges_inscripcion_abierta   se puede pre-inscribir/inscribir para esa gestión
--                             aunque todavía no sea la vigente (2027 en noviembre)
--   ges_cerrada               no admite notas ni cobros nuevos
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

ALTER TABLE colegio_gestiones
    ADD COLUMN IF NOT EXISTS ges_inscripcion_abierta TINYINT NOT NULL DEFAULT 0 AFTER ges_estado,
    ADD COLUMN IF NOT EXISTS ges_preinsc_desde DATE NULL AFTER ges_inscripcion_abierta,
    ADD COLUMN IF NOT EXISTS ges_preinsc_hasta DATE NULL AFTER ges_preinsc_desde,
    ADD COLUMN IF NOT EXISTS ges_fecha_inicio DATE NULL AFTER ges_preinsc_hasta,
    ADD COLUMN IF NOT EXISTS ges_fecha_fin DATE NULL AFTER ges_fecha_inicio,
    ADD COLUMN IF NOT EXISTS ges_cerrada TINYINT NOT NULL DEFAULT 0 AFTER ges_fecha_fin,
    ADD COLUMN IF NOT EXISTS ges_monto_reserva DECIMAL(10,2) NOT NULL DEFAULT 300 AFTER ges_cerrada;

-- Si no hay ninguna vigente, se marca la del año actual (la que usaba el reloj).
UPDATE colegio_gestiones SET ges_estado = 1
WHERE ges_anio = YEAR(CURDATE())
  AND NOT EXISTS (SELECT 1 FROM (SELECT ges_id FROM colegio_gestiones WHERE ges_estado = 1) x);

SELECT ges_anio, ges_estado, ges_inscripcion_abierta, ges_cerrada FROM colegio_gestiones ORDER BY ges_anio;
