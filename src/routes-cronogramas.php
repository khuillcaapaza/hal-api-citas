<?php

declare(strict_types=1);

use App\Controller\CronogramaController;
use Slim\App;

/**
 * Rutas de cronogramas de entrega de citas.
 *
 * Lectura pública (GET /cronogramas) y CRUD protegido por JWT bajo
 * /admin/cronogramas. La lógica vive en App\Controller\CronogramaController.
 */
return function (App $app): void {
    // Lectura pública.
    $app->get('/cronogramas', [CronogramaController::class, 'index']);
    $app->get('/cronogramas/{mes}', [CronogramaController::class, 'show']);

    // Administración (requiere JWT).
    $app->get('/admin/cronogramas', [CronogramaController::class, 'adminIndex']);
    $app->get('/admin/cronogramas/{mes}', [CronogramaController::class, 'adminShow']);
    $app->post('/admin/cronogramas', [CronogramaController::class, 'store']);
    $app->put('/admin/cronogramas/{mes}', [CronogramaController::class, 'update']);
    $app->delete('/admin/cronogramas/{mes}', [CronogramaController::class, 'destroy']);
};
