<?php

declare(strict_types=1);

namespace App\Model;

use App\Support\Database;
use PDO;

/**
 * Acceso a datos del catálogo de áreas de atención (tabla areas_atencion).
 */
class AreaModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /** Áreas activas, ordenadas por nombre (lectura pública). */
    public function activas(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, nombre, descripcion, activo FROM areas_atencion
              WHERE activo = 1 ORDER BY nombre ASC'
        )->fetchAll();

        return array_map([$this, 'map'], $rows);
    }

    /** Todas las áreas (activas e inactivas), para el panel. */
    public function todas(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, nombre, descripcion, activo FROM areas_atencion ORDER BY nombre ASC'
        )->fetchAll();

        return array_map([$this, 'map'], $rows);
    }

    /** Devuelve un área mapeada por id, o null si no existe. */
    public function encontrar(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, descripcion, activo FROM areas_atencion WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->map($row);
    }

    /** ¿Existe ya un área con ese nombre? (opcionalmente excluyendo un id). */
    public function existeNombre(string $nombre, ?int $exceptoId = null): bool
    {
        if ($exceptoId === null) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM areas_atencion WHERE nombre = ? LIMIT 1');
            $stmt->execute([$nombre]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM areas_atencion WHERE nombre = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$nombre, $exceptoId]);
        }

        return $stmt->fetchColumn() !== false;
    }

    /** Inserta un área y devuelve la fila mapeada. */
    public function crear(array $campos): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO areas_atencion (nombre, descripcion, activo)
             VALUES (:nombre, :descripcion, :activo)'
        );
        $stmt->execute($campos);

        return $this->encontrar((int) $this->pdo->lastInsertId());
    }

    /** Actualiza un área existente y devuelve la fila mapeada. */
    public function actualizar(int $id, array $campos): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE areas_atencion
                SET nombre = :nombre, descripcion = :descripcion, activo = :activo
              WHERE id = :id'
        );
        $stmt->execute($campos + ['id' => $id]);

        return $this->encontrar($id);
    }

    /** Elimina un área. Devuelve true si se borró alguna fila. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM areas_atencion WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /** Convierte una fila de BD a la forma de la API. */
    private function map(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'nombre'      => $row['nombre'],
            'descripcion' => $row['descripcion'] ?? '',
            'activo'      => (int) $row['activo'] === 1,
        ];
    }
}
