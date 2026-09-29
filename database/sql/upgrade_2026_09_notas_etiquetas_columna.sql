-- Identificación del casillero de cada nota.
--
-- Los encabezados de las columnas de notas eran "Nota 1", "Nota 2"… generados
-- por un `for` en la vista: no había forma de decir que la columna 2 de SABER
-- es el examen parcial y la 3 el tema 1. Al imprimir el boletín o revisar una
-- nota con el padre, nadie sabía a qué correspondía cada casillero.
--
-- La etiqueta es por clase (curmatdoc) y por periodo, porque cada docente usa
-- sus columnas como quiere y cambia de un trimestre al otro.
CREATE TABLE IF NOT EXISTS notas_columna_etiquetas (
    colet_id        INT(7)      NOT NULL AUTO_INCREMENT,
    curmatdoc_id    INT(7)      NOT NULL,
    periodo_id      INT(7)      NOT NULL,
    dimension_id    INT(7)      NOT NULL,
    columna_num     TINYINT(2)  NOT NULL DEFAULT 1,
    colet_nombre    VARCHAR(60) NOT NULL,
    colet_usuario   INT(7)      NULL,
    colet_fecha     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (colet_id),
    UNIQUE KEY uq_colet (curmatdoc_id, periodo_id, dimension_id, columna_num),
    KEY idx_colet_clase (curmatdoc_id, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
