<?php

declare(strict_types=1);

/**
 * Rellena el campo `codigo` de las áreas que aún no lo tienen.
 *
 * Deriva un código estable a partir del nombre (sin tildes, en mayúsculas,
 * separado por guiones) y garantiza su unicidad. Solo toca filas con código
 * NULL o vacío, por lo que es idempotente: se puede volver a ejecutar sin
 * alterar los códigos ya asignados.
 *
 * Uso (tras la Parte 1 de sql/migracion-codigo-area.sql):
 *   php scripts/backfill-codigo-areas.php
 */

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

/** Convierte un nombre de área en un código candidato (MAYÚS-CON-GUIONES). */
function slugCodigo(string $nombre): string
{
    $t = $nombre;
    if (function_exists('transliterator_transliterate')) {
        $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Upper()', $t) ?: $t;
    } else {
        $t = strtoupper(strtr($t, [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ñ' => 'N',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        ]));
    }
    $t = preg_replace('/[^A-Z0-9]+/', '-', $t) ?? '';
    $t = trim($t, '-');

    return $t === '' ? 'AREA' : substr($t, 0, 40);
}

/** @var PDO $pdo */
$pdo = require __DIR__ . '/../src/db.php';

$pendientes = $pdo
    ->query("SELECT id, nombre FROM areas_atencion WHERE codigo IS NULL OR codigo = '' ORDER BY id ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

if ($pendientes === []) {
    echo "No hay áreas pendientes de código.\n";
    exit(0);
}

// Códigos ya ocupados, para garantizar unicidad.
$usados = array_flip(array_map(
    static fn($c) => (string) $c,
    $pdo->query("SELECT codigo FROM areas_atencion WHERE codigo IS NOT NULL AND codigo <> ''")
        ->fetchAll(PDO::FETCH_COLUMN) ?: []
));

$update = $pdo->prepare('UPDATE areas_atencion SET codigo = :codigo WHERE id = :id');
$asignados = 0;

foreach ($pendientes as $area) {
    $base = slugCodigo((string) $area['nombre']);
    $codigo = $base;
    $n = 2;
    while (isset($usados[$codigo])) {
        $sufijo = '-' . $n;
        $codigo = substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
        $n++;
    }
    $usados[$codigo] = true;

    $update->execute([':codigo' => $codigo, ':id' => (int) $area['id']]);
    echo "#{$area['id']}  {$area['nombre']}  ->  {$codigo}\n";
    $asignados++;
}

echo "\nListo: {$asignados} código(s) asignado(s).\n";
echo "Ahora ejecuta la Parte 2 de sql/migracion-codigo-area.sql para fijar NOT NULL + UNIQUE.\n";
