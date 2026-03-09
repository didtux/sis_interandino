-- Corregir estados de ventas (solo completado o anulado)
UPDATE ventas_ventas SET venta_estado = 'completado' WHERE venta_estado NOT IN ('completado', 'anulado');

-- Agregar índice para mejorar rendimiento
CREATE INDEX idx_ventas_estado ON ventas_ventas(venta_estado);
CREATE INDEX idx_ventas_fecha ON ventas_ventas(venta_fecha);
CREATE INDEX idx_ventas_codigo ON ventas_ventas(ven_codigo);
