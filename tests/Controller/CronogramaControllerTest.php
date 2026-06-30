<?php

declare(strict_types=1);

namespace Tests\Controller;

use App\Controller\CronogramaController;
use App\Model\CronogramaModel;
use Tests\TestCase;

final class CronogramaControllerTest extends TestCase
{
    /** @return array<string,mixed> Cronograma de ejemplo. */
    private function cronograma(): array
    {
        return [
            'mes' => '2026-07', 'monthLabel' => 'Julio 2026', 'titulo' => 'Julio',
            'excerpt' => 'resumen', 'indicaciones' => '', 'areas' => [], 'publicado' => true,
        ];
    }

    private function controller(?CronogramaModel $cronogramas = null): CronogramaController
    {
        return new CronogramaController($cronogramas ?? $this->createMock(CronogramaModel::class));
    }

    // ── Lectura pública ───────────────────────────────────────────────

    public function testIndexDevuelvePublicados(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('publicados')->willReturn([$this->cronograma()]);

        $resp = $this->controller($model)->index($this->request(), $this->response());

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertCount(1, $this->jsonBody($resp)['cronogramas']);
    }

    public function testShowNoEncontrado(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('publicadoPorMes')->willReturn(null);

        $resp = $this->controller($model)->show($this->request(), $this->response(), ['mes' => '2099-01']);
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testShowExitoso(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('publicadoPorMes')->willReturn($this->cronograma());

        $resp = $this->controller($model)->show($this->request(), $this->response(), ['mes' => '2026-07']);
        $this->assertSame(200, $resp->getStatusCode());
    }

    // ── Administración ────────────────────────────────────────────────

    public function testAdminIndexDevuelveTodos(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('todosMeta')->willReturn([$this->cronograma()]);

        $resp = $this->controller($model)->adminIndex($this->request(), $this->response());
        $this->assertSame(200, $resp->getStatusCode());
    }

    public function testAdminShowNoEncontrado(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('porMes')->willReturn(null);

        $resp = $this->controller($model)->adminShow($this->request(), $this->response(), ['mes' => '2099-01']);
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testAdminShowExitoso(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('porMes')->willReturn($this->cronograma());

        $resp = $this->controller($model)->adminShow($this->request(), $this->response(), ['mes' => '2026-07']);
        $this->assertSame(200, $resp->getStatusCode());
    }

    // ── store ─────────────────────────────────────────────────────────

    public function testStoreMesInvalido(): void
    {
        $resp = $this->controller()->store(
            $this->request('POST', ['mes' => 'no-valido', 'titulo' => 'T']),
            $this->response()
        );
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function testStoreTituloVacio(): void
    {
        $resp = $this->controller()->store(
            $this->request('POST', ['mes' => '2026-07', 'titulo' => '']),
            $this->response()
        );
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function testStoreMesDuplicado(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('existeMes')->willReturn(true);

        $resp = $this->controller($model)->store(
            $this->request('POST', ['mes' => '2026-07', 'titulo' => 'Julio']),
            $this->response()
        );
        $this->assertSame(409, $resp->getStatusCode());
    }

    public function testStoreExitosoNormalizaAreas(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('existeMes')->willReturn(false);
        $model->expects($this->once())->method('crear');

        $resp = $this->controller($model)->store(
            $this->request('POST', [
                'mes'    => '2026-07',
                'titulo' => 'Julio',
                'areas'  => [
                    [
                        'area'     => 'Cardiología',
                        'days'     => ['Lunes', 'martes', 'dia-invalido', 'Lunes'],
                        'time'     => '08:00',
                        'location' => 'Piso 1',
                        'note'     => 'Traer DNI',
                    ],
                    'no-es-array',
                    ['area' => ''],
                    ['area' => 'Sin horario'],
                ],
            ]),
            $this->response()
        );

        $this->assertSame(201, $resp->getStatusCode());
        $this->assertSame('Julio 2026', $this->jsonBody($resp)['monthLabel']);
    }

    public function testStoreAreasNoEsArray(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('existeMes')->willReturn(false);

        $resp = $this->controller($model)->store(
            $this->request('POST', ['mes' => '2026-08', 'titulo' => 'Agosto', 'areas' => 'no-es-array']),
            $this->response()
        );
        $this->assertSame(201, $resp->getStatusCode());
    }

    // ── update ────────────────────────────────────────────────────────

    public function testUpdateTituloVacio(): void
    {
        // El mes viene de la ruta; título vacío -> 422.
        $resp = $this->controller()->update(
            $this->request('PUT', ['titulo' => '']),
            $this->response(),
            ['mes' => '2026-07']
        );
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function testUpdateNoEncontrado(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('actualizar')->willReturn(false);

        $resp = $this->controller($model)->update(
            $this->request('PUT', ['titulo' => 'Julio']),
            $this->response(),
            ['mes' => '2099-01']
        );
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testUpdateExitoso(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('actualizar')->willReturn(true);

        $resp = $this->controller($model)->update(
            $this->request('PUT', ['titulo' => 'Julio']),
            $this->response(),
            ['mes' => '2026-07']
        );
        $this->assertSame(200, $resp->getStatusCode());
    }

    // ── destroy ───────────────────────────────────────────────────────

    public function testDestroyNoEncontrado(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('eliminar')->willReturn(false);

        $resp = $this->controller($model)->destroy($this->request('DELETE'), $this->response(), ['mes' => '2099-01']);
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testDestroyExitoso(): void
    {
        $model = $this->createMock(CronogramaModel::class);
        $model->method('eliminar')->willReturn(true);

        $resp = $this->controller($model)->destroy($this->request('DELETE'), $this->response(), ['mes' => '2026-07']);
        $this->assertSame(200, $resp->getStatusCode());
    }
}
