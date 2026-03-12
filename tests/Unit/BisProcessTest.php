<?php

namespace iEducar\Packages\Bis\Tests\Unit;

use iEducar\Packages\Bis\BisProcess;
use PHPUnit\Framework\TestCase;

class BisProcessTest extends TestCase
{
    public function test_constantes_menu_bi(): void
    {
        $this->assertSame(9999200, BisProcess::MENU_BI);
    }

    public function test_constantes_dashboard(): void
    {
        $this->assertSame(9999199, BisProcess::BI_DASHBOARD);
    }

    public function test_constantes_matriculas(): void
    {
        $this->assertSame(9999201, BisProcess::BI_MATRICULAS);
    }

    public function test_constantes_turmas(): void
    {
        $this->assertSame(9999202, BisProcess::BI_TURMAS);
    }

    public function test_constantes_lancamentos(): void
    {
        $this->assertSame(9999203, BisProcess::BI_LANCAMENTOS);
    }

    public function test_constantes_indicadores(): void
    {
        $this->assertSame(9999204, BisProcess::BI_INDICADORES);
    }

    public function test_metodos_estaticos_retornam_inteiros(): void
    {
        $this->assertIsInt(BisProcess::menuBi());
        $this->assertIsInt(BisProcess::dashboard());
        $this->assertIsInt(BisProcess::matriculas());
        $this->assertIsInt(BisProcess::turmas());
        $this->assertIsInt(BisProcess::lancamentos());
        $this->assertIsInt(BisProcess::indicadores());
        $this->assertIsInt(BisProcess::inclusaoDiversidade());
        $this->assertIsInt(BisProcess::buscaAtiva());
        $this->assertIsInt(BisProcess::educacenso());
    }

    public function test_all_retorna_array_de_inteiros(): void
    {
        $all = BisProcess::all();

        $this->assertIsArray($all);
        $this->assertCount(9, $all);

        foreach ($all as $process) {
            $this->assertIsInt($process);
        }
    }

    public function test_all_inclui_todos_os_processos(): void
    {
        $all = BisProcess::all();

        $this->assertContains(BisProcess::MENU_BI, $all);
        $this->assertContains(BisProcess::BI_DASHBOARD, $all);
        $this->assertContains(BisProcess::BI_MATRICULAS, $all);
        $this->assertContains(BisProcess::BI_TURMAS, $all);
        $this->assertContains(BisProcess::BI_LANCAMENTOS, $all);
    }
}
