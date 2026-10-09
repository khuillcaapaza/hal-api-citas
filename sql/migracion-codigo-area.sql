-- Migración: agrega el campo `codigo` (identificador estable) a areas_atencion.
--
-- El código identifica a cada área de forma única e inmutable, de modo que la
-- carga masiva de horarios por CSV pueda referirse al área por su código en
-- lugar de por su nombre (que es variable). En HestiaCP ejecutar en phpMyAdmin
-- sobre la BD del módulo de citas (haladminweb_citas_bd).
--
-- Pasos:
--   1) Agregar la columna como NULL (permite backfill sin violar NOT NULL).
--   2) Rellenar los códigos de las áreas existentes:
--        php scripts/backfill-codigo-areas.php
--   3) Volver a ejecutar la parte 2 de este archivo para fijar NOT NULL + UNIQUE.

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

-- ── Parte 1: agregar la columna (ejecutar ANTES del backfill) ──────────
ALTER TABLE areas_atencion
  ADD COLUMN codigo VARCHAR(40) NULL AFTER id;

-- ── Parte 2: fijar restricciones (ejecutar DESPUÉS del backfill) ───────
-- ALTER TABLE areas_atencion
--   MODIFY codigo VARCHAR(40) NOT NULL,
--   ADD UNIQUE KEY uq_areas_codigo (codigo);
