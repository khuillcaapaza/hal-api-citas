<?php

declare(strict_types=1);

namespace Tests\Controller;

use App\Controller\AreaController;
use App\Model\AreaModel;
use Tests\TestCase;

final class AreaControllerTest extends TestCase
{
    /** @return array<string,mixed> Área mapeada de ejemplo. */
    private function area(): array
    {
        return ['id' => 1, 'nombre' => 'Cardiología', 'descripcion' => 'desc', 'activo' => true];
    }

    private function controller(?AreaModel $areas = null): AreaController
    {
        return new AreaController($areas ?? $this->createMock(AreaModel::class));
    }

    public function testIndexDevuelveActivas(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('activas')->willReturn([$this->area()]);

        $resp = $this->controller($areas)->index($this->request(), $this->response());

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertCount(1, $this->jsonBody($resp)['areas']);
    }

    public function testAdminIndexDevuelveTodas(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('todas')->willReturn([$this->area()]);

        $resp = $this->controller($areas)->adminIndex($this->request(), $this->response());

        $this->assertSame(200, $resp->getStatusCode());
    }

    public function testStoreNombreVacio(): void
    {
        $resp = $this->controller()->store($this->request('POST', ['nombre' => '']), $this->response());
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function testStoreNombreDuplicado(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('existeNombre')->willReturn(true);

        $resp = $this->controller($areas)->store(
            $this->request('POST', ['nombre' => 'Cardiología']),
            $this->response()
        );
        $this->assertSame(409, $resp->getStatusCode());
    }

    public function testStoreExitoso(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('existeNombre')->willReturn(false);
        $areas->method('crear')->willReturn($this->area());

        $resp = $this->controller($areas)->store(
            $this->request('POST', ['nombre' => 'Cardiología', 'descripcion' => 'desc', 'activo' => true]),
            $this->response()
        );

        $this->assertSame(201, $resp->getStatusCode());
        $this->assertTrue($this->jsonBody($resp)['ok']);
    }

    public function testUpdateNombreVacio(): void
    {
        $resp = $this->controller()->update($this->request('PUT', ['nombre' => '']), $this->response(), ['id' => 1]);
        $this->assertSame(422, $resp->getStatusCode());
    }

    public function testUpdateNoEncontrada(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('encontrar')->willReturn(null);

        $resp = $this->controller($areas)->update(
            $this->request('PUT', ['nombre' => 'Cardiología']),
            $this->response(),
            ['id' => 99]
        );
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testUpdateNombreDuplicado(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('encontrar')->willReturn($this->area());
        $areas->method('existeNombre')->willReturn(true);

        $resp = $this->controller($areas)->update(
            $this->request('PUT', ['nombre' => 'Cardiología']),
            $this->response(),
            ['id' => 1]
        );
        $this->assertSame(409, $resp->getStatusCode());
    }

    public function testUpdateExitoso(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('encontrar')->willReturn($this->area());
        $areas->method('existeNombre')->willReturn(false);
        $areas->method('actualizar')->willReturn($this->area());

        $resp = $this->controller($areas)->update(
            $this->request('PUT', ['nombre' => 'Cardiología']),
            $this->response(),
            ['id' => 1]
        );

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertTrue($this->jsonBody($resp)['ok']);
    }

    public function testDestroyNoEncontrada(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('eliminar')->willReturn(false);

        $resp = $this->controller($areas)->destroy($this->request('DELETE'), $this->response(), ['id' => 99]);
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testDestroyExitoso(): void
    {
        $areas = $this->createMock(AreaModel::class);
        $areas->method('eliminar')->willReturn(true);

        $resp = $this->controller($areas)->destroy($this->request('DELETE'), $this->response(), ['id' => 1]);
        $this->assertSame(200, $resp->getStatusCode());
    }
}
