<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\CronogramaModel;
use App\Support\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CRUD de cronogramas de entrega de citas.
 *
 * Lectura pública (GET /cronogramas) y administración protegida por JWT bajo
 * /admin/cronogramas. Reemplaza la implementación basada en Markdown del sitio.
 */
final class CronogramaController extends Controller
{
    /** Días de la semana canónicos (orden de lunes a domingo). */
    private const WEEKDAYS = [
        'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo',
    ];

    private CronogramaModel $cronogramas;

    public function __construct()
    {
        $this->cronogramas = new CronogramaModel();
    }

    // ── Lectura pública ───────────────────────────────────────────────

    /** GET /cronogramas — metadatos de cronogramas publicados. */
    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, ['cronogramas' => $this->cronogramas->publicados()]);
    }

    /** GET /cronogramas/{mes} — un cronograma publicado completo. */
    public function show(Request $request, Response $response, array $args): Response
    {
        $cronograma = $this->cronogramas->publicadoPorMes((string) $args['mes']);
        if ($cronograma === null) {
            return $this->json($response, ['error' => 'Cronograma no encontrado'], 404);
        }

        return $this->json($response, ['cronograma' => $cronograma]);
    }

    // ── Administración (requiere JWT) ─────────────────────────────────

    /** GET /admin/cronogramas — todos los cronogramas (incluye no publicados). */
    public function adminIndex(Request $request, Response $response): Response
    {
        return $this->json($response, ['cronogramas' => $this->cronogramas->todosMeta()]);
    }

    /** GET /admin/cronogramas/{mes} — un cronograma completo para edición. */
    public function adminShow(Request $request, Response $response, array $args): Response
    {
        $cronograma = $this->cronogramas->porMes((string) $args['mes']);
        if ($cronograma === null) {
            return $this->json($response, ['error' => 'Cronograma no encontrado'], 404);
        }

        return $this->json($response, ['cronograma' => $cronograma]);
    }

    /** POST /admin/cronogramas — crear un cronograma. */
    public function store(Request $request, Response $response): Response
    {
        [$campos, $error] = $this->validar((array) $request->getParsedBody());
        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        if ($this->cronogramas->existeMes($campos['mes'])) {
            return $this->json($response, ['error' => 'Ya existe un cronograma para ese mes.'], 409);
        }

        $this->cronogramas->crear($campos);

        return $this->json($response, [
            'ok'         => true,
            'mes'        => $campos['mes'],
            'monthLabel' => CronogramaModel::etiquetaMes($campos['mes']),
        ], 201);
    }

    /** PUT /admin/cronogramas/{mes} — actualizar un cronograma existente. */
    public function update(Request $request, Response $response, array $args): Response
    {
        $mes = (string) $args['mes'];

        [$campos, $error] = $this->validar((array) $request->getParsedBody(), $mes);
        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        if (!$this->cronogramas->actualizar($mes, $campos)) {
            return $this->json($response, ['error' => 'Cronograma no encontrado'], 404);
        }

        return $this->json($response, ['ok' => true, 'mes' => $mes]);
    }

    /** DELETE /admin/cronogramas/{mes} — eliminar un cronograma. */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        if (!$this->cronogramas->eliminar((string) $args['mes'])) {
            return $this->json($response, ['error' => 'Cronograma no encontrado'], 404);
        }

        return $this->json($response, ['ok' => true]);
    }

    // ── Validación / normalización de entrada ─────────────────────────

    /**
     * Valida el cuerpo de creación/edición. Devuelve [campos, error].
     * En edición, el mes viene fijado por la ruta.
     */
    private function validar(array $data, ?string $mesFijo = null): array
    {
        $mes = trim((string) ($data['mes'] ?? $mesFijo ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return [null, 'El mes debe tener el formato AAAA-MM (ej. 2026-07).'];
        }

        $titulo = trim((string) ($data['titulo'] ?? ''));
        if ($titulo === '') {
            return [null, 'El título es obligatorio.'];
        }

        return [[
            'mes'          => $mes,
            'titulo'       => mb_substr($titulo, 0, 200),
            'excerpt'      => mb_substr(trim((string) ($data['excerpt'] ?? '')), 0, 500),
            'indicaciones' => trim((string) ($data['indicaciones'] ?? '')),
            'areas'        => json_encode($this->sanitizeAreas($data['areas'] ?? []), JSON_UNESCAPED_UNICODE),
            'publicado'    => filter_var($data['publicado'] ?? true, FILTER_VALIDATE_BOOL) ? 1 : 0,
        ], null];
    }

    /** Valida y normaliza la lista de áreas recibida. */
    private function sanitizeAreas($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $orden = array_flip(self::WEEKDAYS);
        $out   = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }
            $area = trim((string) ($item['area'] ?? ''));
            if ($area === '') {
                continue;
            }

            $days = [];
            if (isset($item['days']) && is_array($item['days'])) {
                foreach ($item['days'] as $d) {
                    $norm = $this->normalizeWeekday($d);
                    if ($norm !== null && !in_array($norm, $days, true)) {
                        $days[] = $norm;
                    }
                }
                usort($days, static fn($a, $b) => $orden[$a] <=> $orden[$b]);
            }

            $time     = trim((string) ($item['time'] ?? ''));
            $location = trim((string) ($item['location'] ?? ''));
            $note     = trim((string) ($item['note'] ?? ''));

            $out[] = [
                'area'     => mb_substr($area, 0, 120),
                'days'     => $days,
                'time'     => $time !== '' ? mb_substr($time, 0, 60) : null,
                'location' => $location !== '' ? mb_substr($location, 0, 120) : null,
                'note'     => $note !== '' ? mb_substr($note, 0, 200) : null,
            ];
        }

        return $out;
    }

    /** Normaliza un día (acepta con/sin tildes y mayúsculas) al valor canónico. */
    private function normalizeWeekday($value): ?string
    {
        $map = [
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'miércoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
            'sabado'    => 'Sábado',
            'sábado'    => 'Sábado',
            'domingo'   => 'Domingo',
        ];

        return $map[strtolower(trim((string) $value))] ?? null;
    }
}
