<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\UsuarioModel;
use App\Support\Controller;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Autenticación: login (emite JWT) y datos del usuario autenticado.
 */
final class AuthController extends Controller
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    /** POST /login: autentica por usuario + contraseña y emite un JWT. */
    public function login(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $usuario  = trim((string) ($data['usuario'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($usuario === '' || $password === '') {
            return $this->json($response, ['error' => 'Usuario y contraseña son obligatorios'], 422);
        }

        $user = $this->usuarios->buscarActivoPorUsuario($usuario);

        // Mensaje genérico: no revelar si el usuario existe o no.
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return $this->json($response, ['error' => 'Credenciales inválidas'], 401);
        }

        $this->usuarios->registrarAcceso((int) $user['id']);

        $now     = time();
        $ttl     = (int) ($_ENV['JWT_TTL'] ?? 28800); // 8 h por defecto
        $payload = [
            'iat'     => $now,
            'exp'     => $now + $ttl,
            'sub'     => (int) $user['id'],
            'usuario' => $user['usuario'],
            'nombre'  => $user['nombre'],
            'rol'     => $user['rol'],
        ];
        $token = JWT::encode($payload, (string) ($_ENV['JWT_SECRET'] ?? ''), 'HS256');

        return $this->json($response, [
            'token'   => $token,
            'usuario' => [
                'id'      => (int) $user['id'],
                'usuario' => $user['usuario'],
                'nombre'  => $user['nombre'],
                'rol'     => $user['rol'],
            ],
        ]);
    }

    /** GET /me: devuelve los datos del usuario autenticado (requiere JWT válido). */
    public function me(Request $request, Response $response): Response
    {
        $claims = $request->getAttribute('token'); // claims decodificados del JWT

        if ($claims === null) {
            return $this->json($response, ['error' => 'No autorizado'], 401);
        }

        return $this->json($response, ['usuario' => $claims]);
    }
}
