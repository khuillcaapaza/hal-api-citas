<?php

declare(strict_types=1);

namespace Tests\Model;

use App\Model\CronogramaModel;
use Tests\TestCase;

final class CronogramaModelTest extends TestCase
{
    /** @return array<string,mixed> Fila "meta" (listado). */
    private function filaMeta(): array
    {
        return ['mes' => '2026-07', 'titulo' => 'Julio', 'excerpt' => 'resumen', 'publicado' => 1];
    }

    /** @return array<string,mixed> Fila completa. */
    private function filaCompleta(string $areas = null): array
    {
        return [
            'mes'            => '2026-07',
            'titulo'         => 'Julio',
            'excerpt'        => 'resumen',
            'indicaciones'   => 'indic',
            'areas'          => $areas ?? json_encode([['area' => 'Cardiología', 'days' => ['Lunes']]]),
            'publicado'      => 1,
            'actualizado_en' => '2026-01-01 00:00:00',
        ];
    }

    public function testPublicadosMapeaMeta(): void
    {
        $pdo  = $this->pdo(query: [$this->stmt(['fetchAll' => [$this->filaMeta()]])]);
        $rows = (new CronogramaModel($pdo))->publicados();

        $this->assertSame('Julio 2026', $rows[0]['monthLabel']);
        $this->assertTrue($rows[0]['publicado']);
    }

    public function testTodosMetaMapeaMeta(): void
    {
        $pdo  = $this->pdo(query: [$this->stmt(['fetchAll' => [$this->filaMeta()]])]);
        $rows = (new CronogramaModel($pdo))->todosMeta();

        $this->assertCount(1, $rows);
        $this->assertSame('Julio', $rows[0]['titulo']);
    }

    public function testPublicadoPorMesDevuelveCompleto(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetch' => $this->filaCompleta()])]);
        $row = (new CronogramaModel($pdo))->publicadoPorMes('2026-07');

        $this->assertSame('Cardiología', $row['areas'][0]['area']);
        $this->assertSame('Julio 2026', $row['monthLabel']);
    }

    public function testPublicadoPorMesDevuelveNull(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetch' => false])]);

        $this->assertNull((new CronogramaModel($pdo))->publicadoPorMes('2099-01'));
    }

    public function testPorMesConAreasInvalidasDevuelveListaVacia(): void
    {
        // areas no es JSON válido -> se normaliza a [].
        $pdo = $this->pdo(prepare: [$this->stmt(['fetch' => $this->filaCompleta('no-es-json')])]);
        $row = (new CronogramaModel($pdo))->porMes('2026-07');

        $this->assertSame([], $row['areas']);
    }

    public function testPorMesDevuelveNull(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetch' => false])]);

        $this->assertNull((new CronogramaModel($pdo))->porMes('2099-01'));
    }

    public function testExisteMesTrue(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetchColumn' => 1])]);

        $this->assertTrue((new CronogramaModel($pdo))->existeMes('2026-07'));
    }

    public function testExisteMesFalse(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['fetchColumn' => false])]);

        $this->assertFalse((new CronogramaModel($pdo))->existeMes('2099-01'));
    }

    public function testCrearEjecutaInsert(): void
    {
        $stmt = $this->stmt();
        $stmt->expects($this->once())->method('execute');
        (new CronogramaModel($this->pdo(prepare: [$stmt])))->crear([
            'mes' => '2026-07', 'titulo' => 'T', 'excerpt' => '', 'indicaciones' => '',
            'areas' => '[]', 'publicado' => 1,
        ]);
    }

    public function testActualizarConFilasAfectadas(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['rowCount' => 1])]);

        $this->assertTrue((new CronogramaModel($pdo))->actualizar('2026-07', $this->campos()));
    }

    public function testActualizarSinFilasPeroExiste(): void
    {
        // UPDATE sin cambios (rowCount 0) + existeMes -> true.
        $pdo = $this->pdo(prepare: [
            $this->stmt(['rowCount' => 0]),
            $this->stmt(['fetchColumn' => 1]),
        ]);

        $this->assertTrue((new CronogramaModel($pdo))->actualizar('2026-07', $this->campos()));
    }

    public function testActualizarSinFilasYNoExiste(): void
    {
        $pdo = $this->pdo(prepare: [
            $this->stmt(['rowCount' => 0]),
            $this->stmt(['fetchColumn' => false]),
        ]);

        $this->assertFalse((new CronogramaModel($pdo))->actualizar('2099-01', $this->campos()));
    }

    public function testEliminarDevuelveTrue(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['rowCount' => 1])]);

        $this->assertTrue((new CronogramaModel($pdo))->eliminar('2026-07'));
    }

    public function testEliminarDevuelveFalse(): void
    {
        $pdo = $this->pdo(prepare: [$this->stmt(['rowCount' => 0])]);

        $this->assertFalse((new CronogramaModel($pdo))->eliminar('2099-01'));
    }

    public function testEtiquetaMesValida(): void
    {
        $this->assertSame('Julio 2026', CronogramaModel::etiquetaMes('2026-07'));
    }

    public function testEtiquetaMesFormatoInvalido(): void
    {
        // No cumple AAAA-MM: devuelve el valor original.
        $this->assertSame('texto', CronogramaModel::etiquetaMes('texto'));
    }

    public function testEtiquetaMesIndiceFueraDeRango(): void
    {
        // Coincide con el patrón pero el mes 13 está fuera de rango.
        $this->assertSame('2026-13', CronogramaModel::etiquetaMes('2026-13'));
    }

    /** @return array<string,mixed> */
    private function campos(): array
    {
        return [
            'mes' => '2026-07', 'titulo' => 'T', 'excerpt' => '', 'indicaciones' => '',
            'areas' => '[]', 'publicado' => 1,
        ];
    }
}
