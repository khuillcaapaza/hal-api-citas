<?php

declare(strict_types=1);

namespace Tests\Model;

use App\Model\AreaModel;
use Tests\TestCase;

final class AreaModelTest extends TestCase
{
    /** @return array<string,mixed> Fila cruda de BD. */
    private function fila(): array
    {
        return ['id' => 1, 'nombre' => 'Cardiología', 'descripcion' => 'desc', 'activo' => 1];
    }

    public function testActivasMapeaFilas(): void
    {
        $pdo   = $this->pdo(query: [$this->stmt(['fetchAll' => [$this->fila()]])]);
        $areas = (new AreaModel($pdo))->activas();

        $this->assertSame(1, $areas[0]['id']);
        $this->assertSame('Cardiología', $areas[0]['nombre']);
        $this->assertTrue($areas[0]['activo']);
    }

    public function testTodasMapeaFilas(): void
    {
        $pdo   = $this->pdo(query: [$this->stmt(['fetchAll' => [$this->fila()]])]);
        $areas = (new AreaModel($pdo))->todas();

        $this->assertCount(1, $areas);
        $this->assertSame('desc', $areas[0]['descripcion']);
    }

    public function testEncontrarDevuelveArea(): void
    {
        $pdo  = $this->pdo(prepare: [$this->stmt(['fetch' => $this->fila()])]);
        $area = (new AreaModel($pdo))->encontrar(1);

        $this->assertSame(1, $area['id']);
    }

    public function testEncontrarDevuelveNull(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetch' => false])]);

        $this->assertNull((new AreaModel($pdo))->encontrar(99));
    }

    public function testExisteNombreSinExcepto(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetchColumn' => 1])]);

        $this->assertTrue((new AreaModel($pdo))->existeNombre('Cardiología'));
    }

    public function testExisteNombreConExcepto(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetchColumn' => false])]);

        $this->assertFalse((new AreaModel($pdo))->existeNombre('Cardiología', 1));
    }

    public function testCrearInsertaYDevuelveArea(): void
    {
        // INSERT + encontrar(lastInsertId).
        $pdo = $this->pdo(
            prepare: [$this->stmt(), $this->stmt(['fetch' => $this->fila()])],
            lastInsertId: '1'
        );

        $area = (new AreaModel($pdo))->crear([
            'nombre' => 'Cardiología', 'descripcion' => 'desc', 'activo' => 1,
        ]);

        $this->assertSame('Cardiología', $area['nombre']);
    }

    public function testActualizarYDevuelveArea(): void
    {
        // UPDATE + encontrar(id).
        $pdo = $this->pdo(prepare: [$this->stmt(), $this->stmt(['fetch' => $this->fila()])]);

        $area = (new AreaModel($pdo))->actualizar(1, [
            'nombre' => 'Cardiología', 'descripcion' => 'desc', 'activo' => 1,
        ]);

        $this->assertSame(1, $area['id']);
    }

    public function testEliminarDevuelveTrue(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['rowCount' => 1])]);

        $this->assertTrue((new AreaModel($pdo))->eliminar(1));
    }

    public function testEliminarDevuelveFalse(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['rowCount' => 0])]);

        $this->assertFalse((new AreaModel($pdo))->eliminar(99));
    }
}
