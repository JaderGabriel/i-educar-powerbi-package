<?php

use App\Menu;
use App\Process;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona o item Dashboard como primeiro no menu BI (para instalações existentes).
 */
class AddBiDashboardMenuItem extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $biMenu = Menu::query()->where('process', Process::MENU_BI)->first();
        if (!$biMenu) {
            return;
        }

        Menu::query()->updateOrCreate([
            'parent_id' => $biMenu->getKey(),
            'title' => 'Dashboard',
        ], [
            'description' => 'BI - Dashboard',
            'link' => '/bis',
            'order' => 0,
            'type' => 3,
            'process' => Process::BI_DASHBOARD,
            'parent_old' => Process::MENU_BI,
            'active' => true,
        ]);

        $schoolProcess = Process::MENU_SCHOOL;
        $biProcess = Process::BI_DASHBOARD;

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DELETE FROM pmieducar.menu_tipo_usuario WHERE menu_id = (SELECT id FROM public.menus WHERE process = ' . Process::BI_DASHBOARD . ')');
        Menu::query()->where('process', Process::BI_DASHBOARD)->where('title', 'Dashboard')->delete();
    }
}
