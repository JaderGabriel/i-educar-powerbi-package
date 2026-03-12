<?php

namespace iEducar\Packages\Bis\Tests\Unit;

use iEducar\Packages\Bis\Services\BiDashboardService;
use Tests\TestCase;

class BiDashboardServiceTest extends TestCase
{
    private BiDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BiDashboardService();
    }

    public function test_get_anos_letivos_retorna_collection(): void
    {
        $result = $this->service->getAnosLetivos();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertNotEmpty($result);
    }

    public function test_get_anos_letivos_itens_tem_id_e_nome(): void
    {
        $result = $this->service->getAnosLetivos();

        foreach ($result as $item) {
            $this->assertObjectHasProperty('id', $item);
            $this->assertObjectHasProperty('nome', $item);
            $this->assertIsInt($item->id);
            $this->assertIsString($item->nome);
        }
    }

    public function test_get_summary_retorna_estrutura_esperada(): void
    {
        $ano = now()->year;
        $result = $this->service->getSummary($ano);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('matriculasAtivas', $result);
        $this->assertArrayHasKey('totalTurmas', $result);
        $this->assertArrayHasKey('totalEscolas', $result);
        $this->assertArrayHasKey('totalCursos', $result);
        $this->assertArrayHasKey('anoAtual', $result);
        $this->assertArrayHasKey('matriculasPorSituacao', $result);
        $this->assertArrayHasKey('matriculasPorCurso', $result);
        $this->assertArrayHasKey('turmasPorEscola', $result);
        $this->assertArrayHasKey('evolucaoAnual', $result);
        $this->assertArrayHasKey('charts', $result);
        $this->assertEquals($ano, $result['anoAtual']);
    }

    public function test_get_summary_com_ano_null_usa_ano_atual(): void
    {
        $result = $this->service->getSummary(null);

        $this->assertEquals(now()->year, $result['anoAtual']);
    }
}
