<?php

use App\Menu;
use App\Process;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration do pacote BI - Adiciona menu e submenus do Business Intelligence.
 */
class AddBiMenu extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $escolaMenu = Menu::query()->where('process', Process::MENU_SCHOOL)->first();
        if (!$escolaMenu) {
            return;
        }

        $biMenu = Menu::query()->updateOrCreate([
            'parent_id' => $escolaMenu->getKey(),
            'old' => Process::MENU_BI,
        ], [
            'title' => 'BI',
            'description' => 'Business Intelligence - Dashboards e relatórios analíticos',
            'link' => '/bis',
            'icon' => 'fa-chart-line',
            'order' => 8,
            'type' => 2,
            'process' => Process::MENU_BI,
            'parent_old' => Process::MENU_SCHOOL,
            'active' => true,
        ]);

        $temas = [
            ['title' => 'Dashboard', 'link' => '/bis', 'order' => 0, 'process' => Process::BI_DASHBOARD],
            ['title' => 'Matrículas', 'link' => '/bis/matriculas', 'order' => 1, 'process' => Process::BI_MATRICULAS],
            ['title' => 'Turmas', 'link' => '/bis/turmas', 'order' => 2, 'process' => Process::BI_TURMAS],
            ['title' => 'Lançamentos', 'link' => '/bis/lancamentos', 'order' => 3, 'process' => Process::BI_LANCAMENTOS],
            ['title' => 'Indicadores', 'link' => '/bis/indicadores', 'order' => 4, 'process' => Process::BI_INDICADORES],
            ['title' => 'Inclusão e Diversidade', 'link' => '/bis/inclusao-diversidade', 'order' => 5, 'process' => Process::BI_INCLUSAO_DIVERSIDADE],
            ['title' => 'Busca Ativa', 'link' => '/bis/busca-ativa', 'order' => 6, 'process' => Process::BI_BUSCA_ATIVA],
            ['title' => 'Educacenso/INEP', 'link' => '/bis/educacenso', 'order' => 7, 'process' => Process::BI_EDUCACENSO],
        ];

        foreach ($temas as $tema) {
            Menu::query()->updateOrCreate([
                'parent_id' => $biMenu->getKey(),
                'title' => $tema['title'],
            ], [
                'description' => "BI - {$tema['title']}",
                'link' => $tema['link'],
                'order' => $tema['order'],
                'type' => 3,
                'process' => $tema['process'],
                'parent_old' => Process::MENU_BI,
                'active' => true,
            ]);
        }

        $schoolProcess = Process::MENU_SCHOOL;
        $biProcesses = [
            Process::MENU_BI,
            Process::BI_DASHBOARD,
            Process::BI_MATRICULAS,
            Process::BI_TURMAS,
            Process::BI_LANCAMENTOS,
            Process::BI_INDICADORES,
            Process::BI_INCLUSAO_DIVERSIDADE,
            Process::BI_BUSCA_ATIVA,
            Process::BI_EDUCACENSO,
        ];

        foreach ($biProcesses as $biProcess) {
            DB::statement(
                "INSERT INTO pmieducar.menu_tipo_usuario (ref_cod_tipo_usuario, cadastra, visualiza, exclui, menu_id)
                 SELECT ref_cod_tipo_usuario, 1, 1, 1, (SELECT id FROM public.menus WHERE process = {$biProcess} LIMIT 1)
                 FROM pmieducar.menu_tipo_usuario
                 WHERE menu_id = (SELECT id FROM public.menus WHERE process = {$schoolProcess} LIMIT 1)
                 AND (SELECT id FROM public.menus WHERE process = {$biProcess} LIMIT 1) IS NOT NULL
                 AND NOT EXISTS (
                     SELECT 1 FROM pmieducar.menu_tipo_usuario mtu
                     WHERE mtu.ref_cod_tipo_usuario = menu_tipo_usuario.ref_cod_tipo_usuario
                       AND mtu.menu_id = (SELECT id FROM public.menus WHERE process = {$biProcess} LIMIT 1)
                 )"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $biProcesses = [
            Process::BI_DASHBOARD,
            Process::BI_MATRICULAS,
            Process::BI_TURMAS,
            Process::BI_LANCAMENTOS,
            Process::BI_INDICADORES,
            Process::BI_INCLUSAO_DIVERSIDADE,
            Process::BI_BUSCA_ATIVA,
            Process::BI_EDUCACENSO,
        ];

        foreach ($biProcesses as $process) {
            DB::statement('DELETE FROM pmieducar.menu_tipo_usuario WHERE menu_id = (SELECT id FROM public.menus WHERE process = ' . $process . ')');
            Menu::query()->where('process', $process)->delete();
        }

        DB::statement('DELETE FROM pmieducar.menu_tipo_usuario WHERE menu_id = (SELECT id FROM public.menus WHERE process = ' . Process::MENU_BI . ')');
        Menu::query()->where('process', Process::MENU_BI)->delete();
    }
}
