-- Migración: catálogo definitivo de áreas de atención (según la imagen de
-- "Entrega de citas médicas" del Hospital Antonio Lorena).
--
-- ⚠️ DESTRUCTIVA: elimina TODAS las áreas existentes y las reemplaza por el
-- catálogo oficial con sus códigos. Los cronogramas guardan las áreas por
-- nombre en JSON, por lo que no hay claves foráneas que se rompan, pero si un
-- nombre cambia respecto al guardado, ese cronograma dejará de resolver el área.
--
-- Requiere que las columnas `codigo` y `sin_restriccion` ya existan
-- (ver migracion-codigo-area.sql y migracion-sin-restriccion-area.sql).
-- En HestiaCP: seleccionar la BD del módulo de citas en phpMyAdmin e importar.

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

START TRANSACTION;

DELETE FROM areas_atencion;

INSERT INTO areas_atencion (codigo, nombre, descripcion, sin_restriccion, activo) VALUES
  ('ANEST',     'Anestesiología',                   '', 0, 1),
  ('ARO',       'ARO',                              '', 1, 1),
  ('CARDIO-RQ', 'Cardiología +Riesgo Quirúrgico',   '', 0, 1),
  ('CERIT',     'CERIT',                            '', 1, 1),
  ('CIR-CC',    'Cirugía Cabeza y Cuello',          '', 0, 1),
  ('CIR-CV',    'Cirugía Cardiovascular',           '', 0, 1),
  ('CIR-GEN',   'Cirugía General',                  '', 0, 1),
  ('CIR-PED',   'Cirugía Pediátrica',               '', 0, 1),
  ('CIR-PLAS',  'Cirugía Plástica',                 '', 0, 1),
  ('DERMA',     'Dermatología',                     '', 0, 1),
  ('ENDO',      'Endocrinología',                   '', 0, 1),
  ('GASTRO',    'Gastroenterología',                '', 0, 1),
  ('GERIAT',    'Geriatria',                        '', 1, 1),
  ('GINE',      'Ginecología',                      '', 0, 1),
  ('HEMATO',    'Hematología Clínica',              '', 0, 1),
  ('INFECTO',   'Infectología',                     '', 0, 1),
  ('MFR',       'Medicina Física y Rehabilitación', '', 0, 1),
  ('MED-INT',   'Medicina Interna',                 '', 1, 1),
  ('NEFRO',     'Nefrología',                       '', 0, 1),
  ('NEONATO',   'Neonatología',                     '', 0, 1),
  ('NEUMO',     'Neumología',                       '', 0, 1),
  ('NEUROCIR',  'Neurocirugía',                     '', 0, 1),
  ('NEURO',     'Neurología',                       '', 0, 1),
  ('NUTRI',     'Nutrición',                        '', 0, 1),
  ('ODONTO',    'Odontología',                      '', 1, 1),
  ('OFTALMO',   'Oftalmología',                     '', 0, 1),
  ('ONCO',      'Oncologia',                        '', 1, 1),
  ('OTORRINO',  'Otrorrinolaringologia',            '', 1, 1),
  ('PED',       'Pediatría',                        '', 1, 1),
  ('PSICO',     'Psicologia',                       '', 1, 1),
  ('PSIQ',      'Psiquiatría',                      '', 0, 1),
  ('RADIO',     'Radioterapia',                     '', 0, 1),
  ('REUMA',     'Reumatología',                     '', 0, 1),
  ('TRAUMA',    'Traumatología y Ortopedia',        '', 0, 1),
  ('URO',       'Urología',                         '', 0, 1),
  ('URO-ONCO',  'Urología Oncológica',              '', 0, 1);

COMMIT;
