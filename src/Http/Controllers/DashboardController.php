<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Exports\BiThemeExport;
use iEducar\Packages\Bis\Services\BiDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function export(Request $request): BinaryFileResponse
    {
        $biProcesses = [BisProcess::menuBi(), BisProcess::dashboard(), BisProcess::matriculas(), BisProcess::turmas(), BisProcess::lancamentos(), BisProcess::indicadores()];
        $canView = collect($biProcesses)->contains(fn ($p) => Gate::allows('view', $p));
        if (!$canView) {
            abort(403, 'Sem permissão para acessar o BI.');
        }

        $ano = $request->get('ano') ? (int) $request->get('ano') : null;
        $service = app(BiDashboardService::class);
        $summary = $service->getSummary($ano);

        $exportData = [];
        foreach ($summary['matriculasPorCurso'] ?? [] as $r) {
            $exportData[] = ['tipo' => 'Matrículas por Segmento', 'categoria' => $r->curso, 'total' => $r->total];
        }
        foreach ($summary['turmasPorEscola'] ?? [] as $r) {
            $exportData[] = ['tipo' => 'Turmas por Escola', 'categoria' => $r->escola, 'total' => $r->total];
        }
        foreach ($summary['evolucaoAnual'] ?? [] as $r) {
            $exportData[] = ['tipo' => 'Evolução Anual', 'ano' => $r->ano, 'total' => $r->total];
        }

        if (empty($exportData)) {
            $exportData = [['tipo' => '-', 'categoria' => '-', 'total' => 0]];
        }

        $headings = !empty($exportData[0]) ? array_keys($exportData[0]) : [];

        return Excel::download(
            new BiThemeExport($exportData, $headings),
            'bi_dashboard_' . now()->format('Y-m-d_His') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
