-- ═══════════════════════════════════════════════════════════════════════════
-- Upgrade 2026-09 — Separar el promedio trimestral OFICIAL del decimal exacto
-- ═══════════════════════════════════════════════════════════════════════════
-- Contexto:
--   La pantalla del docente calcula PROM. TRIM. como la suma de los promedios
--   de cada dimension YA REDONDEADOS, mientras el servidor guardaba la suma sin
--   redondear. Al imprimir, ROUND() sobre ese decimal producia un off-by-one
--   (pantalla 82 / boletin 83).
--
-- Decision:
--   nota_promedio_trimestral -> valor OFICIAL entero (igual a la pantalla y al
--                               PROM. TRIM. del Excel cargado). Lo usan todos
--                               los reportes, boletines y el centralizador.
--   nota_promedio_decimal    -> suma exacta con decimales. Se usa UNICAMENTE en
--                               el cuadro de honor / rankings para desempatar.
--
-- Nota: las filas anteriores a este upgrade quedan con NULL en la columna nueva.
--       Los rankings usan COALESCE(nota_promedio_decimal, nota_promedio_trimestral),
--       y como esas filas viejas guardan justamente el decimal exacto, el
--       desempate sigue funcionando sin migrar datos.
-- ═══════════════════════════════════════════════════════════════════════════

ALTER TABLE colegio_notas
  ADD COLUMN nota_promedio_decimal DECIMAL(5,2) NULL DEFAULT NULL
  AFTER nota_promedio_trimestral;
