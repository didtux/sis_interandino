-- ═══════════════════════════════════════════════════════════════════════════
-- Upgrade 2026-09 — Horarios especiales por rango de fechas
-- ═══════════════════════════════════════════════════════════════════════════
-- Contexto:
--   asistencia_configuracion define un unico horario por categoria/turno, sin
--   noción de fecha. Cuando el colegio aplica un horario distinto por un
--   periodo (horario de invierno) o suspende clases (receso de vacaciones),
--   los reportes siguen evaluando contra el horario normal y marcan atrasos
--   que nunca existieron, ademas de contar como dias habiles dias sin clases.
--
-- Solucion:
--   asistencia_horarios_especiales          -> cabecera: gestion + rango + tipo
--   asistencia_horarios_especiales_detalle  -> horas por categoria+turno
--
--   esp_tipo = 1  Horario especial: reemplaza entrada/tolerancia/salida.
--   esp_tipo = 2  Receso sin clases: no hay atrasos, faltas ni dias habiles.
--
--   El turno vive en el DETALLE, no en la cabecera: un mismo rango puede
--   afectar categorias de turnos distintos (el horario de invierno cambia la
--   mañana y podria cambiar tambien la tarde). Eso es lo que permite, en UN
--   SOLO rango, que INICIAL entre 8:45, PRIMARIA/SECUNDARIA 8:15 y la
--   SECUNDARIA TARDE entre a otra hora.
--
--   Un receso (esp_tipo=2) no lleva detalle: aplica a todo el colegio.
--
--   Si dos rangos cubren el mismo dia, la regla es fija y no se configura:
--   manda el receso por sobre el horario especial ("no hay clases" pesa mas que
--   "se entra mas tarde"), y entre dos del mismo tipo vale el cargado despues.
--
-- Colacion:
--   utf8mb4_unicode_ci. Estas tablas NO se unen por SQL con colegio_cursos ni
--   colegio_materias (latin1 / utf8mb4_general_ci mezclados): el cruce se hace
--   en PHP para evitar "Illegal mix of collations".
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS asistencia_horarios_especiales (
    esp_id           INT(7) NOT NULL AUTO_INCREMENT,
    esp_nombre       VARCHAR(120) NOT NULL,
    esp_gestion      INT(4) NOT NULL,
    esp_fecha_inicio DATE NOT NULL,
    esp_fecha_fin    DATE NOT NULL,
    esp_tipo         TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=horario especial, 2=receso sin clases',
    esp_observacion  TEXT NULL,
    esp_estado       TINYINT(1) NOT NULL DEFAULT 1,
    esp_creado_por   INT(7) NULL,
    esp_fecha_registro DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (esp_id),
    KEY idx_gestion (esp_gestion),
    KEY idx_rango (esp_fecha_inicio, esp_fecha_fin),
    KEY idx_estado_tipo (esp_estado, esp_tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistencia_horarios_especiales_detalle (
    det_id                INT(7) NOT NULL AUTO_INCREMENT,
    esp_id                INT(7) NOT NULL,
    det_categoria         VARCHAR(80) NOT NULL COMMENT 'coincide con asistencia_configuracion.config_categoria / colegio_cursos.cur_nivel',
    det_turno             VARCHAR(20) NOT NULL DEFAULT 'Mañana' COMMENT 'coincide con asistencia_configuracion.config_turno',
    det_hora_entrada      TIME NOT NULL,
    det_tolerancia_atraso TIME NOT NULL COMMENT 'hora absoluta: marca posterior = atraso',
    det_hora_salida       TIME NOT NULL,
    PRIMARY KEY (det_id),
    UNIQUE KEY uk_esp_categoria_turno (esp_id, det_categoria, det_turno),
    KEY idx_esp (esp_id),
    CONSTRAINT fk_hesp_detalle FOREIGN KEY (esp_id)
        REFERENCES asistencia_horarios_especiales (esp_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────────────────
-- Correccion: el 2do Trimestre quedo guardado como 0026-05-11 (año 26).
-- Mientras este asi, cualquier calculo de asistencia de ese trimestre corre
-- sobre un rango de ~2000 años y da resultados absurdos.
-- ───────────────────────────────────────────────────────────────────────────
UPDATE notas_config_periodos
SET periodo_fecha_inicio = '2026-05-11'
WHERE periodo_gestion = 2026
  AND periodo_numero = 2
  AND YEAR(periodo_fecha_inicio) < 1900;
