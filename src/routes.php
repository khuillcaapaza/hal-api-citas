<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {

    // Helper: escribe una respuesta JSON con el código de estado indicado
    $json = static function (Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    };

    // Healthcheck público (verifica que la API responde)
    $app->get('/health', function (Request $request, Response $response) use ($json): Response {
        return $json($response, ['status' => 'ok', 'time' => date('c')]);
    });

    // POST /login: autentica por usuario + contraseña y emite un JWT
    $app->post('/login', function (Request $request, Response $response) use ($json): Response {
        $data     = (array) $request->getParsedBody();
        $usuario  = trim((string) ($data['usuario'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($usuario === '' || $password === '') {
            return $json($response, ['error' => 'Usuario y contraseña son obligatorios'], 422);
        }

        /** @var PDO $pdo */
        $pdo  = require __DIR__ . '/db.php';
        $stmt = $pdo->prepare(
            'SELECT id, usuario, nombre, rol, password_hash
               FROM usuarios
              WHERE usuario = ? AND activo = 1
              LIMIT 1'
        );
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        // Mensaje genérico: no revelar si el usuario existe o no
        if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
            return $json($response, ['error' => 'Credenciales inválidas'], 401);
        }

        // Registrar último acceso
        $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')
            ->execute([$user['id']]);

        $now = time();
        $ttl = (int) ($_ENV['JWT_TTL'] ?? 28800); // 8 h por defecto
        $payload = [
            'iat'     => $now,
            'exp'     => $now + $ttl,
            'sub'     => (int) $user['id'],
            'usuario' => $user['usuario'],
            'nombre'  => $user['nombre'],
            'rol'     => $user['rol'],
        ];
        $token = JWT::encode($payload, (string) ($_ENV['JWT_SECRET'] ?? ''), 'HS256');

        return $json($response, [
            'token'   => $token,
            'usuario' => [
                'id'      => (int) $user['id'],
                'usuario' => $user['usuario'],
                'nombre'  => $user['nombre'],
                'rol'     => $user['rol'],
            ],
        ]);
    });

    // GET /me: devuelve los datos del usuario autenticado (requiere JWT válido)
    $app->get('/me', function (Request $request, Response $response) use ($json): Response {
        $claims = $request->getAttribute('token'); // claims decodificados del JWT

        if ($claims === null) {
            return $json($response, ['error' => 'No autorizado'], 401);
        }

        return $json($response, ['usuario' => $claims]);
    });
};
