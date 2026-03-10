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
    Route::get('/export/dashboard', [DashboardController::class, 'export'])
        ->name('dashboard.export');
    Route::get('/relatorios', [ReportController::class, 'index'])
        ->name('reports');

    Route::get('/matriculas', [MatriculasController::class, 'index'])
        ->name('matriculas.index');
    Route::get('/matriculas/export/{report}', [MatriculasController::class, 'export'])
        ->where('report', 'por-curso|por-ano|por-escola|por-serie|por-situacao|por-turno|por-modalidade|por-dependencia')
        ->name('matriculas.export');
    Route::post('/matriculas/print-pdf', [MatriculasController::class, 'printPdf'])
        ->name('matriculas.print-pdf');

    Route::get('/turmas', [TurmasController::class, 'index'])
        ->name('turmas.index');
    Route::get('/turmas/export/{report}', [TurmasController::class, 'export'])
        ->where('report', 'por-escola|por-curso|por-serie|por-turno|por-ano|por-modalidade')
        ->name('turmas.export');
    Route::post('/turmas/print-pdf', [TurmasController::class, 'printPdf'])
        ->name('turmas.print-pdf');

    Route::get('/{theme}/export', [ThemeController::class, 'export'])
        ->where('theme', 'lancamentos|indicadores|inclusao-diversidade|busca-ativa|educacenso')
        ->name('theme.export');
    Route::get('/{theme}', [ThemeController::class, 'show'])
        ->where('theme', 'lancamentos|indicadores|inclusao-diversidade|busca-ativa|educacenso')
        ->name('theme');
});
