<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Services\BiDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends BisBaseController
{
    public function index(Request $request): View
    {
        $biProcesses = [BisProcess::menuBi(), BisProcess::dashboard(), BisProcess::matriculas(), BisProcess::turmas(), BisProcess::lancamentos(), BisProcess::indicadores()];
        $canView = collect($biProcesses)->contains(fn ($p) => Gate::allows('view', $p));
        if (!$canView) {
            abort(403, 'Sem permissão para acessar o BI.');
        }

        $service = app(BiDashboardService::class);
        $anosLetivos = $service->getAnosLetivos();
        $anoRequest = $request->get('ano');
        $anoValido = now()->year;
        if ($anoRequest && is_numeric($anoRequest)) {
            $anoValido = (int) $anoRequest;
            if ($anosLetivos->isNotEmpty() && !$anosLetivos->contains('id', $anoValido)) {
                $anoValido = $anosLetivos->last()->id;
            }
        } elseif ($anosLetivos->isNotEmpty()) {
            $anoValido = $anosLetivos->last()->id;
        }

        $summary = $service->getSummary($anoValido);

        $chartMatriculasCurso = $summary['charts']['matriculasCurso'] ?? null;
        $chartTurmasEscola = $summary['charts']['turmasEscola'] ?? null;
        $chartEvolucao = $summary['charts']['evolucao'] ?? null;

        return view('bis::dashboard.index', [
            'summary' => $summary,
            'anosLetivos' => $anosLetivos,
            'anoSelecionado' => $anoValido,
            'chartMatriculasCurso' => $chartMatriculasCurso?->container(),
            'chartMatriculasCursoScript' => $chartMatriculasCurso ? $chartMatriculasCurso->script() : '',
            'chartTurmasEscola' => $chartTurmasEscola?->container(),
            'chartTurmasEscolaScript' => $chartTurmasEscola ? $chartTurmasEscola->script() : '',
            'turmasEscolaTooltips' => $summary['turmasEscolaTooltips'] ?? [],
            'chartEvolucao' => $chartEvolucao?->container(),
            'chartEvolucaoScript' => $chartEvolucao ? $chartEvolucao->script() : '',
        ]);
    }
}
