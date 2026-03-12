<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Services\BiMatriculasReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MatriculasController extends BisBaseController
{
    public function index(Request $request): View
    {
        Gate::authorize('view', BisProcess::matriculas());

        $service = app(BiMatriculasReportsService::class);
        $anosLetivos = $service->getAnosLetivos();
        $anoRequest = $request->get('ano');
        $anoValido = null;
        if ($anoRequest && $anoRequest !== 'todos') {
            $anoValido = (int) $anoRequest;
            if ($anosLetivos->isNotEmpty() && !$anosLetivos->contains('id', $anoValido)) {
                $anoValido = $anosLetivos->last()->id;
            }
        } elseif ($anoRequest === 'todos') {
            $anoValido = null;
        } elseif ($anosLetivos->isNotEmpty()) {
            $anoValido = $anosLetivos->last()->id;
        }

        $enturmacao = $request->get('enturmacao');
        if (!in_array($enturmacao, [BiMatriculasReportsService::FILTER_ENTURMACAO_ENTURMADOS, BiMatriculasReportsService::FILTER_ENTURMACAO_NAO_ENTURMADOS], true)) {
            $enturmacao = null;
        }

        $instituicoes = \App\Models\LegacyInstitution::active()->orderBy('nm_instituicao')->get();
        $singleInstitution = $instituicoes->count() === 1;
        $instituicaoId = $request->get('ref_cod_instituicao') ? (int) $request->get('ref_cod_instituicao') : null;
        if ($singleInstitution) {
            $instituicaoId = $instituicoes->first()->cod_instituicao;
        }
        $escolasFiltradas = $service->getEscolas($instituicaoId);
        $escolaIdsValidos = $escolasFiltradas->pluck('id')->toArray();
        $escolaId = $request->get('ref_cod_escola') ? (int) $request->get('ref_cod_escola') : null;
        if ($escolaId && !in_array($escolaId, $escolaIdsValidos)) {
            $escolaId = null;
        }

        $cursosFiltrados = $service->getCursos($escolaId);
        $cursoIdsValidos = $cursosFiltrados->pluck('id')->toArray();
        $cursoId = $request->get('ref_cod_curso') ? (int) $request->get('ref_cod_curso') : null;
        if ($cursoId && !in_array($cursoId, $cursoIdsValidos)) {
            $cursoId = null;
        }

        $modalidade = $request->get('modalidade');
        $modalidade = ($modalidade && $modalidade >= 1 && $modalidade <= 4) ? (int) $modalidade : null;

        $dependencia = $request->get('dependencia');
        $dependencia = in_array($dependencia, ['sim', 'nao'], true) ? $dependencia : null;

        $turnoId = $request->get('turno') ? (int) $request->get('turno') : null;

        $filters = [
            'ano' => $anoValido,
            'enturmacao' => $enturmacao,
            'instituicao' => $instituicaoId,
            'escola' => $escolaId,
            'curso' => $cursoId,
            'modalidade' => $modalidade,
            'dependencia' => $dependencia,
            'turno' => $turnoId,
        ];

        $rawReports = $service->getAllReports($filters);
        $reports = [];
        foreach ($rawReports as $key => $r) {
            $reports[$key] = array_merge($r, ['title' => BiMatriculasReportsService::REPORTS[$key]['title'] ?? $key]);
        }

        return view('bis::matriculas.index', [
            'title' => 'BI - Matrículas',
            'reports' => $reports,
            'filters' => $filters,
            'anosLetivos' => $anosLetivos,
            'instituicoes' => $instituicoes,
            'singleInstitution' => $singleInstitution,
            'escolas' => $escolasFiltradas,
            'cursos' => $cursosFiltrados,
            'turnos' => $service->getTurnos(),
        ]);
    }
}
