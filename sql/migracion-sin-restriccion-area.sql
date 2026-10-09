-- Migración: agrega el campo `sin_restriccion` a areas_atencion.
--
-- Marca las áreas que entregan cupos todos los días (sin restricción de
-- fecha). La carga masiva de horarios por CSV omite estas áreas: solo las
-- áreas con sin_restriccion = 0 aplican la lógica de asignación de horarios.
--
-- En HestiaCP ejecutar en phpMyAdmin sobre la BD del módulo de citas.

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

ALTER TABLE areas_atencion
  ADD COLUMN sin_restriccion TINYINT(1) NOT NULL DEFAULT 0 AFTER descripcion;
