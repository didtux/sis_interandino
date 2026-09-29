-- Kardex docente: que el docente pueda entregar el documento que le pide dirección.
--
-- Hasta ahora dirección registraba el pedido en docente_kardex y el docente no
-- lo veía por ningún lado: su única bandeja era Comunicados, que es otra tabla.
-- El pedido quedaba en PENDIENTE hasta que alguien de dirección subía el archivo
-- a mano (kdx_archivo), así que no había forma de saber quién entregó y cuándo.
--
-- Se separa el archivo que adjunta dirección (kdx_archivo: modelo, formulario,
-- instructivo) del que sube el docente (kdx_archivo_docente: la entrega).
ALTER TABLE docente_kardex
    ADD COLUMN kdx_archivo_docente VARCHAR(255) NULL AFTER kdx_archivo,
    ADD COLUMN kdx_fecha_entrega_docente DATETIME NULL AFTER kdx_archivo_docente;
