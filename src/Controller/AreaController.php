<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\AreaModel;
use App\Support\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CRUD del catálogo de áreas de atención.
 *
 * Lectura pública (GET /areas) y administración protegida por JWT bajo
 * /admin/areas. Las áreas alimentan el selector del editor de cronogramas.
 */
final class AreaController extends Controller
{
    private AreaModel $areas;

    public function __construct()
    {
        $this->areas = new AreaModel();
    }

    /** GET /areas — áreas activas (lectura pública). */
    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, ['areas' => $this->areas->activas()]);
    }

    /** GET /admin/areas — todas las áreas, para el panel. */
    public function adminIndex(Request $request, Response $response): Response
    {
        return $this->json($response, ['areas' => $this->areas->todas()]);
    }

    /** POST /admin/areas — crear un área. */
    public function store(Request $request, Response $response): Response
    {
        [$campos, $error] = $this->validar((array) $request->getParsedBody());
        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        if ($this->areas->existeNombre($campos['nombre'])) {
            return $this->json($response, ['error' => 'Ya existe un área con ese nombre.'], 409);
        }

        $area = $this->areas->crear($campos);

        return $this->json($response, ['ok' => true, 'area' => $area], 201);
    }

    /** PUT /admin/areas/{id} — actualizar un área existente. */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        [$campos, $error] = $this->validar((array) $request->getParsedBody());
        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        if ($this->areas->encontrar($id) === null) {
            return $this->json($response, ['error' => 'Área no encontrada'], 404);
        }

        if ($this->areas->existeNombre($campos['nombre'], $id)) {
            return $this->json($response, ['error' => 'Ya existe un área con ese nombre.'], 409);
        }

        $area = $this->areas->actualizar($id, $campos);

        return $this->json($response, ['ok' => true, 'area' => $area]);
    }

    /** DELETE /admin/areas/{id} — eliminar un área. */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->areas->eliminar((int) $args['id'])) {
            return $this->json($response, ['error' => 'Área no encontrada'], 404);
        }

        return $this->json($response, ['ok' => true]);
    }

    /**
     * Valida el cuerpo de creación/edición. Devuelve [campos, error].
     */
    private function validar(array $data): array
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return [null, 'El nombre del área es obligatorio.'];
        }

        return [[
            'nombre'      => mb_substr($nombre, 0, 120),
            'descripcion' => mb_substr(trim((string) ($data['descripcion'] ?? '')), 0, 300),
            'activo'      => filter_var($data['activo'] ?? true, FILTER_VALIDATE_BOOL) ? 1 : 0,
        ], null];
    }
}
