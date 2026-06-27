<?php

declare(strict_types=1);

use App\Controller\AreaController;
use Slim\App;

/**
 * Rutas del catálogo de áreas de atención.
 *
 * Lectura pública (GET /areas) y CRUD protegido por JWT bajo /admin/areas.
 * La lógica vive en App\Controller\AreaController (arquitectura MVC).
 */
return function (App $app): void {
    // Lectura pública.
    $app->get('/areas', [AreaController::class, 'index']);

    // Administración (requiere JWT).
    $app->get('/admin/areas', [AreaController::class, 'adminIndex']);
    $app->post('/admin/areas', [AreaController::class, 'store']);
    $app->put('/admin/areas/{id}', [AreaController::class, 'update']);
    $app->delete('/admin/areas/{id}', [AreaController::class, 'destroy']);
};
