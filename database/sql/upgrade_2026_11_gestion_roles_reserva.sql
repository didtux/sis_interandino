-- ============================================================================
-- Gestión nueva por roles y monto de reserva parametrizable. Idempotente.
--
-- Monto de la reserva de pre-inscripción, del más específico al más general:
--   1) tarifa del curso destino (o de su nivel) → colegio_tarifas.tar_monto_reserva
--   2) monto fijado en la gestión                → colegio_gestiones.ges_monto_reserva
--   3) parámetro del colegio                     → sistema_parametros.preinsc_monto_reserva
-- Requiere upgrade_2026_11_nueva_gestion.sql y upgrade_2026_11_rol_capacidades.sql.
-- ============================================================================
-- El archivo está en UTF-8: sin esto, el cliente mysql de Windows guarda los acentos rotos ("Atenci├│n").
SET NAMES utf8mb4;

-- La gestión puede dejar el monto vacío (= usar el parámetro del colegio)
ALTER TABLE colegio_gestiones MODIFY ges_monto_reserva DECIMAL(10,2) NULL DEFAULT NULL;
-- Las gestiones que ya existían recibieron 300 como valor por defecto de la
-- columna, no porque alguien lo fijara: pasan a vacío para que manden el
-- parámetro y la tarifa del curso. Sólo la primera vez (aún no existe el
-- parámetro), para no borrar un 300 que el colegio fije después.
UPDATE colegio_gestiones SET ges_monto_reserva = NULL
WHERE ges_monto_reserva = 300
  AND NOT EXISTS (SELECT 1 FROM sistema_parametros WHERE param_clave = 'preinsc_monto_reserva');

-- Reserva por curso/nivel en Tarifas; el monto anual puede quedar vacío si
-- sólo se fija la reserva
ALTER TABLE colegio_tarifas MODIFY tar_monto_anual DECIMAL(10,2) NULL;
SET @db = DATABASE();
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='colegio_tarifas' AND COLUMN_NAME='tar_monto_reserva');
SET @s = IF(@c = 0, 'ALTER TABLE colegio_tarifas ADD COLUMN tar_monto_reserva DECIMAL(10,2) NULL AFTER tar_monto_anual', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

INSERT IGNORE INTO sistema_parametros (param_clave, param_valor, param_descripcion) VALUES
 ('preinsc_monto_reserva', '300', 'Monto de la reserva de pre-inscripción (Bs) cuando ni la tarifa del curso ni la gestión fijan uno');

-- Capacidades nuevas (se cambian en Roles → Capacidades especiales)
-- 9 Director General · 10 Directora Académica · 13 Dir. financiero
INSERT INTO rol_capacidades (rol_id, cap_slug)
SELECT x.rol_id, x.cap FROM (
    SELECT 9 rol_id, 'gestion.preinscripcion' cap UNION ALL SELECT 10, 'gestion.preinscripcion'
    UNION ALL SELECT 9, 'gestion.montos' UNION ALL SELECT 13, 'gestion.montos'
    UNION ALL SELECT 9, 'preinscripcion.cambiar_monto' UNION ALL SELECT 13, 'preinscripcion.cambiar_monto'
) x
JOIN rol_roles r ON r.rol_id = x.rol_id
WHERE NOT EXISTS (SELECT 1 FROM rol_capacidades c WHERE c.rol_id = x.rol_id AND c.cap_slug = x.cap);

SELECT cap_slug, GROUP_CONCAT(rol_id ORDER BY rol_id) roles FROM rol_capacidades
WHERE cap_slug LIKE 'gestion.%' OR cap_slug LIKE 'preinscripcion.%' GROUP BY cap_slug;
