<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde .env (si existe)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$app = AppFactory::create();

// Base path: '/api' en producción (HestiaCP), vacío en local. Configurable por .env
$basePath = $_ENV['APP_BASE_PATH'] ?? '';
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// Middlewares (se ejecutan en orden inverso al registro)
(require __DIR__ . '/../src/middleware.php')($app);

// Rutas
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
