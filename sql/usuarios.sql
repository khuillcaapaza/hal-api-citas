-- Esquema de autenticación para el sistema de citas (hal-api-citas)
-- Ejecutar en MySQL/MariaDB. En HestiaCP usar la BD ya creada (p. ej. haladminweb_bdtest)
-- y omitir las dos primeras líneas (CREATE DATABASE / USE).

CREATE DATABASE IF NOT EXISTS hal_citas
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hal_citas;

CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  usuario         VARCHAR(50)     NOT NULL,
  password_hash   VARCHAR(255)    NOT NULL,
  nombre          VARCHAR(120)    NOT NULL,
  rol             VARCHAR(30)     NOT NULL DEFAULT 'usuario',
  activo          TINYINT(1)      NOT NULL DEFAULT 1,
  ultimo_acceso   DATETIME        NULL,
  creado_en       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTA: no se insertan usuarios aquí. Las contraseñas deben cifrarse con
-- password_hash() de PHP. Usar el script: php scripts/crear-usuario.php
