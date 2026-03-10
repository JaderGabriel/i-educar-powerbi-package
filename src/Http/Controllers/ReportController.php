<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use Illuminate\View\View;

class ReportController extends BisBaseController
{
    /**
     * Lista de relatórios BI disponíveis.
     */
    public function index(): View
    {
        $relatorios = [
            [
                'nome' => 'Dashboard Geral',
                'descricao' => 'Visão consolidada de matrículas, turmas e indicadores escolares',
                'rota' => route('bis.dashboard'),
            ],
        ];

        return view('bis::reports.index', compact('relatorios'));
    }
}
