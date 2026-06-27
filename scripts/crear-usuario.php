<?php

declare(strict_types=1);

/**
 * Crea o actualiza un usuario con la contraseña cifrada (password_hash).
 *
 * Uso:
 *   php scripts/crear-usuario.php <usuario> <password> "<nombre>" [rol]
 *
 * Ejemplo:
 *   php scripts/crear-usuario.php admin Secreta123 "Administrador" admin
 */

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno (.env) para la conexión a la BD
if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

$usuario  = $argv[1] ?? null;
$password = $argv[2] ?? null;
$nombre   = $argv[3] ?? null;
$rol      = $argv[4] ?? 'usuario';

if ($usuario === null || $password === null || $nombre === null) {
    fwrite(STDERR, "Uso: php scripts/crear-usuario.php <usuario> <password> \"<nombre>\" [rol]\n");
    exit(1);
}

/** @var PDO $pdo */
$pdo  = require __DIR__ . '/../src/db.php';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (usuario, password_hash, nombre, rol)
     VALUES (:usuario, :hash, :nombre, :rol)
     ON DUPLICATE KEY UPDATE
        password_hash = VALUES(password_hash),
        nombre        = VALUES(nombre),
        rol           = VALUES(rol)'
);

$stmt->execute([
    ':usuario' => $usuario,
    ':hash'    => $hash,
    ':nombre'  => $nombre,
    ':rol'     => $rol,
]);

echo "Usuario '{$usuario}' creado/actualizado correctamente.\n";
