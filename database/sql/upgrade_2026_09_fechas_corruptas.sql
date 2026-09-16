-- ═══════════════════════════════════════════════════════════════════════════
-- Upgrade 2026-09 — Fechas con año imposible
-- ═══════════════════════════════════════════════════════════════════════════
-- Sintoma observado:
--   El boletin mostraba "TF (Faltas) 521775" y "TOTAL DIAS HAB. 521840".
--
-- Causa:
--   notas_config_periodos.periodo_fecha_inicio del 1er Trimestre quedo cargado
--   como '0026-02-02' (año 26 en vez de 2026). El calculo de asistencia recorre
--   dia por dia el rango del periodo, asi que iteraba ~2000 años: 521775 dias
--   habiles, todos contados como falta porque no hay marcaciones en el año 26.
--
--   Lo mismo puede pasar con cualquier fecha mal tipeada, por eso ademas del
--   arreglo de datos se agrego un tope en AsistenciaResumenService: un periodo
--   de mas de 3 años se recorta y no vuelve a producir numeros imposibles.
--
--   Aparte, 27 marcaciones quedaron con fecha '0206-05-18' (por '2026-05-18').
--   26 de esos estudiantes YA tienen marcacion ese dia, o sea que son la misma
--   marcacion duplicada con el año mal. Se corrige la fecha en vez de borrarlas:
--   las presencias se cuentan por dia distinto, asi que un duplicado no altera
--   ningun total, y no se pierde el registro del que no estaba duplicado.
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. Periodos con año imposible ──────────────────────────────────────────
-- Se reconstruye el año a partir de la gestion, conservando dia y mes.
UPDATE notas_config_periodos
SET periodo_fecha_inicio = DATE_FORMAT(periodo_fecha_inicio, CONCAT(periodo_gestion, '-%m-%d'))
WHERE YEAR(periodo_fecha_inicio) < 1900
   OR YEAR(periodo_fecha_inicio) > 2100;

UPDATE notas_config_periodos
SET periodo_fecha_fin = DATE_FORMAT(periodo_fecha_fin, CONCAT(periodo_gestion, '-%m-%d'))
WHERE YEAR(periodo_fecha_fin) < 1900
   OR YEAR(periodo_fecha_fin) > 2100;

-- ── 2. Marcaciones con año imposible ───────────────────────────────────────
-- '0206-05-18' -> '2026-05-18'. Se toma el año de la gestion vigente de las
-- marcaciones sanas para no depender de un valor escrito a mano.
UPDATE colegio_asistencia
SET asis_fecha = DATE_FORMAT(
        asis_fecha,
        CONCAT((SELECT * FROM (
                    SELECT YEAR(MAX(asis_fecha)) FROM colegio_asistencia
                    WHERE YEAR(asis_fecha) BETWEEN 1900 AND 2100
                ) AS g), '-%m-%d'))
WHERE YEAR(asis_fecha) < 1900
   OR YEAR(asis_fecha) > 2100;

-- ── 3. Comprobacion ────────────────────────────────────────────────────────
-- Las tres consultas deben devolver 0.
-- SELECT COUNT(*) FROM notas_config_periodos WHERE YEAR(periodo_fecha_inicio) < 1900 OR YEAR(periodo_fecha_fin) < 1900;
-- SELECT COUNT(*) FROM colegio_asistencia   WHERE YEAR(asis_fecha) < 1900 OR YEAR(asis_fecha) > 2100;
-- SELECT COUNT(*) FROM asistencia_atrasos   WHERE YEAR(atraso_fecha) < 1900 OR YEAR(atraso_fecha) > 2100;
