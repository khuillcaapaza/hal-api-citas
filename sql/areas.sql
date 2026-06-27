-- Catálogo de áreas de atención (hal-api-citas)
-- Permite gestionar dinámicamente las áreas/servicios que aparecen en los
-- cronogramas. Lectura pública (GET /areas) y CRUD protegido por JWT bajo
-- /admin/areas. En HestiaCP usar la BD ya creada (haladminweb_citas_bd) e
-- importar SOLO la sentencia CREATE TABLE (sin USE).

-- USE hal_citas;  -- (solo en local; en Hestia seleccionar la BD en phpMyAdmin)

CREATE TABLE IF NOT EXISTS areas_atencion (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120)  NOT NULL,
  descripcion     VARCHAR(300)  NOT NULL DEFAULT '',
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_areas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
