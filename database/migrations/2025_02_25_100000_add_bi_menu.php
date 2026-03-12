<?php

use App\Menu;
use App\Process;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use iEducar\Packages\Bis\BisProcess;

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

        $biMenuProcess = BisProcess::menuBi();

        $biMenu = Menu::query()->updateOrCreate([
            'parent_id' => $escolaMenu->getKey(),
            'old' => $biMenuProcess,
        ], [
            'title' => 'BI',
            'description' => 'Business Intelligence - Dashboards e relatórios analíticos',
            'link' => '/bis',
            'icon' => 'fa-chart-line',
            'order' => 8,
            'type' => 2,
            'process' => $biMenuProcess,
            'parent_old' => Process::MENU_SCHOOL,
            'active' => true,
        ]);

        $temas = [
            ['title' => 'Dashboard', 'link' => '/bis', 'order' => 0, 'process' => BisProcess::dashboard()],
            ['title' => 'Matrículas', 'link' => '/bis/matriculas', 'order' => 1, 'process' => BisProcess::matriculas()],
            ['title' => 'Turmas', 'link' => '/bis/turmas', 'order' => 2, 'process' => BisProcess::turmas()],
            ['title' => 'Lançamentos', 'link' => '/bis/lancamentos', 'order' => 3, 'process' => BisProcess::lancamentos()],
            ['title' => 'Indicadores', 'link' => '/bis/indicadores', 'order' => 4, 'process' => BisProcess::indicadores()],
            ['title' => 'Inclusão e Diversidade', 'link' => '/bis/inclusao-diversidade', 'order' => 5, 'process' => BisProcess::inclusaoDiversidade()],
            ['title' => 'Busca Ativa', 'link' => '/bis/busca-ativa', 'order' => 6, 'process' => BisProcess::buscaAtiva()],
            ['title' => 'Educacenso/INEP', 'link' => '/bis/educacenso', 'order' => 7, 'process' => BisProcess::educacenso()],
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
                'parent_old' => $biMenuProcess,
                'active' => true,
            ]);
        }

        $schoolProcess = Process::MENU_SCHOOL;
        $biProcesses = BisProcess::all();

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
        $biProcesses = BisProcess::all();

        foreach ($biProcesses as $process) {
            DB::statement('DELETE FROM pmieducar.menu_tipo_usuario WHERE menu_id = (SELECT id FROM public.menus WHERE process = ' . $process . ')');
            Menu::query()->where('process', $process)->delete();
        }

        $biMenuProcess = BisProcess::menuBi();

        DB::statement('DELETE FROM pmieducar.menu_tipo_usuario WHERE menu_id = (SELECT id FROM public.menus WHERE process = ' . $biMenuProcess . ')');
        Menu::query()->where('process', $biMenuProcess)->delete();
    }
}
