<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Services\BiTurmasReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TurmasController extends BisBaseController
{
    public function index(Request $request): View
    {
        Gate::authorize('view', BisProcess::turmas());

        $service = app(BiTurmasReportsService::class);
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

        $turnoId = $request->get('turno') ? (int) $request->get('turno') : null;

        $filters = [
            'ano' => $anoValido,
            'instituicao' => $instituicaoId,
            'escola' => $escolaId,
            'curso' => $cursoId,
            'modalidade' => $modalidade,
            'turno' => $turnoId,
        ];

        $rawReports = $service->getAllReports($filters);
        $reports = [];
        foreach ($rawReports as $key => $r) {
            $reports[$key] = array_merge($r, ['title' => BiTurmasReportsService::REPORTS[$key]['title'] ?? $key]);
        }

        return view('bis::turmas.index', [
            'title' => 'BI - Turmas',
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
