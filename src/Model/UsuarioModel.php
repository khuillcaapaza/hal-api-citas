<?php

declare(strict_types=1);

namespace App\Model;

use App\Support\Database;
use PDO;

/**
 * Acceso a datos de usuarios del panel (tabla usuarios).
 */
final class UsuarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** Busca un usuario activo por su nombre de usuario. Null si no existe. */
    public function buscarActivoPorUsuario(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario, nombre, rol, password_hash
               FROM usuarios
              WHERE usuario = ? AND activo = 1
              LIMIT 1'
        );
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Registra la fecha/hora del último acceso. */
    public function registrarAcceso(int $id): void
    {
        $this->pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')
            ->execute([$id]);
    }
}
