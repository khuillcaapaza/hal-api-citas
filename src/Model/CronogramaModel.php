<?php

declare(strict_types=1);

namespace App\Model;

use App\Support\Database;
use PDO;

/**
 * Acceso a datos de los cronogramas de entrega de citas (tabla cronogramas).
 *
 * Las áreas se almacenan como JSON en la columna `areas`.
 */
final class CronogramaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** Metadatos de los cronogramas publicados (del más reciente al más antiguo). */
    public function publicados(): array
    {
        $rows = $this->pdo->query(
            'SELECT mes, titulo, excerpt, publicado FROM cronogramas
              WHERE publicado = 1 ORDER BY mes DESC'
        )->fetchAll();

        return array_map([$this, 'mapMeta'], $rows);
    }

    /** Metadatos de todos los cronogramas (incluye no publicados). */
    public function todosMeta(): array
    {
        $rows = $this->pdo->query(
            'SELECT mes, titulo, excerpt, publicado FROM cronogramas ORDER BY mes DESC'
        )->fetchAll();

        return array_map([$this, 'mapMeta'], $rows);
    }

    /** Un cronograma publicado completo por mes, o null. */
    public function publicadoPorMes(string $mes): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cronogramas WHERE mes = ? AND publicado = 1 LIMIT 1'
        );
        $stmt->execute([$mes]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->map($row);
    }

    /** Un cronograma completo por mes (publicado o no), o null. */
    public function porMes(string $mes): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cronogramas WHERE mes = ? LIMIT 1');
        $stmt->execute([$mes]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->map($row);
    }

    /** ¿Existe un cronograma para ese mes? */
    public function existeMes(string $mes): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM cronogramas WHERE mes = ? LIMIT 1');
        $stmt->execute([$mes]);

        return $stmt->fetchColumn() !== false;
    }

    /** Inserta un cronograma. */
    public function crear(array $campos): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cronogramas (mes, titulo, excerpt, indicaciones, areas, publicado)
             VALUES (:mes, :titulo, :excerpt, :indicaciones, :areas, :publicado)'
        );
        $stmt->execute($campos);
    }

    /**
     * Actualiza un cronograma existente por mes.
     * Devuelve true si el cronograma existe (haya o no cambios efectivos).
     */
    public function actualizar(string $mes, array $campos): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cronogramas
                SET titulo = :titulo, excerpt = :excerpt, indicaciones = :indicaciones,
                    areas = :areas, publicado = :publicado
              WHERE mes = :mes_actual'
        );
        $stmt->execute([
            'titulo'       => $campos['titulo'],
            'excerpt'      => $campos['excerpt'],
            'indicaciones' => $campos['indicaciones'],
            'areas'        => $campos['areas'],
            'publicado'    => $campos['publicado'],
            'mes_actual'   => $mes,
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        // Sin filas afectadas: puede que no exista o que no haya cambios.
        return $this->existeMes($mes);
    }

    /** Elimina un cronograma. Devuelve true si se borró alguna fila. */
    public function eliminar(string $mes): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cronogramas WHERE mes = ?');
        $stmt->execute([$mes]);

        return $stmt->rowCount() > 0;
    }

    /** Etiqueta legible "Julio 2026" a partir de "YYYY-MM". */
    public static function etiquetaMes(string $mes): string
    {
        $nombres = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        ];
        if (preg_match('/^(\d{4})-(\d{2})$/', $mes, $m)) {
            $idx = (int) $m[2] - 1;
            if ($idx >= 0 && $idx <= 11) {
                return $nombres[$idx] . ' ' . $m[1];
            }
        }

        return $mes;
    }

    /** Convierte una fila de BD a la forma completa de la API. */
    private function map(array $row): array
    {
        $areas = json_decode((string) $row['areas'], true);
        if (!is_array($areas)) {
            $areas = [];
        }

        return [
            'mes'          => $row['mes'],
            'monthLabel'   => self::etiquetaMes((string) $row['mes']),
            'titulo'       => $row['titulo'],
            'excerpt'      => $row['excerpt'],
            'indicaciones' => $row['indicaciones'] ?? '',
            'areas'        => $areas,
            'publicado'    => (int) $row['publicado'] === 1,
            'actualizado'  => $row['actualizado_en'] ?? null,
        ];
    }

    /** Versión "meta" (sin áreas ni indicaciones) para listados. */
    private function mapMeta(array $row): array
    {
        return [
            'mes'        => $row['mes'],
            'monthLabel' => self::etiquetaMes((string) $row['mes']),
            'titulo'     => $row['titulo'],
            'excerpt'    => $row['excerpt'],
            'publicado'  => (int) $row['publicado'] === 1,
        ];
    }
}
