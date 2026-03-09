-- Agregar campo número de bus a la tabla transporte_vehiculos
ALTER TABLE transporte_vehiculos 
ADD COLUMN veh_numero_bus VARCHAR(20) NULL AFTER veh_codigo;
