-- Migración: agrega el campo `titulo_sin_restriccion` a cronogramas.
--
-- Guarda el título de la sección de áreas que atienden todos los días
-- (ej. "Especialidades con la entrega de cupos todos los días"). La lista de
-- áreas no se guarda aquí: se deriva del catálogo (areas_atencion con
-- sin_restriccion = 1) al construir la respuesta.
--
-- En HestiaCP: seleccionar la BD del módulo de citas en phpMyAdmin e importar.

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

ALTER TABLE cronogramas
  ADD COLUMN titulo_sin_restriccion VARCHAR(200) NOT NULL DEFAULT '' AFTER indicaciones;
