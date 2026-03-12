<?php

use iEducar\Packages\Bis\Http\Controllers\DashboardController;
use iEducar\Packages\Bis\Http\Controllers\MatriculasController;
use iEducar\Packages\Bis\Http\Controllers\ReportController;
use iEducar\Packages\Bis\Http\Controllers\ThemeController;
use iEducar\Packages\Bis\Http\Controllers\TurmasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'ieducar.navigation', 'ieducar.footer', 'ieducar.suspended', 'auth', 'ieducar.checkresetpassword', 'ieducar.bi.menu'],
    'prefix' => 'bis',
    'as' => 'bis.',
], function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/relatorios', [ReportController::class, 'index'])
        ->name('reports');

    Route::get('/matriculas', [MatriculasController::class, 'index'])
        ->name('matriculas.index');

    Route::get('/turmas', [TurmasController::class, 'index'])
        ->name('turmas.index');

    Route::get('/{theme}', [ThemeController::class, 'show'])
        ->where('theme', 'lancamentos|indicadores|inclusao-diversidade|busca-ativa|educacenso')
        ->name('theme');
});
