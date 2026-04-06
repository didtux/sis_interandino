-- Campos de auditoría para notas
ALTER TABLE colegio_notas
    ADD COLUMN nota_guardado_por INT(7) NULL AFTER nota_observacion,
    ADD COLUMN nota_fecha_guardado DATETIME NULL AFTER nota_guardado_por,
    ADD COLUMN nota_enviado_por INT(7) NULL AFTER nota_fecha_guardado,
    ADD COLUMN nota_fecha_envio DATETIME NULL AFTER nota_enviado_por;
