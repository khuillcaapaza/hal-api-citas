-- Esquema de cronogramas de entrega de citas (hal-api-citas)
-- Reemplaza la implementación basada en Markdown del sitio principal por datos
-- dinámicos en MySQL/MariaDB. En HestiaCP usar la BD ya creada
-- (haladminweb_citas_bd) e importar SOLO la sentencia CREATE TABLE (sin USE).

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

CREATE TABLE IF NOT EXISTS cronogramas (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  mes             CHAR(7)       NOT NULL,                 -- 'YYYY-MM'
  titulo          VARCHAR(200)  NOT NULL,
  excerpt         VARCHAR(500)  NOT NULL DEFAULT '',
  indicaciones    TEXT          NULL,                     -- notas en texto/markdown
  areas           LONGTEXT      NOT NULL,                 -- JSON: lista de áreas
  publicado       TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cronogramas_mes (mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estructura del JSON de `areas` (array de objetos):
-- [
--   {
--     "area": "Cirugía",
--     "days": ["Martes", "Jueves"],
--     "time": "7:00 a.m.",
--     "location": "Módulo 1 - Admisión",
--     "note": null
--   }
-- ]
