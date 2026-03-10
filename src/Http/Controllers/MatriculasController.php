<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Exports\BiThemeExport;
use iEducar\Packages\Bis\Services\BiMatriculasReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function export(Request $request, string $report): BinaryFileResponse
    {
        Gate::authorize('view', BisProcess::matriculas());

        if (!isset(BiMatriculasReportsService::REPORTS[$report])) {
            abort(404);
        }

        $anoReq = $request->get('ano');
        $anoValido = ($anoReq && $anoReq !== 'todos') ? (int) $anoReq : null;

        $enturmacao = $request->get('enturmacao');
        if (!in_array($enturmacao, [BiMatriculasReportsService::FILTER_ENTURMACAO_ENTURMADOS, BiMatriculasReportsService::FILTER_ENTURMACAO_NAO_ENTURMADOS], true)) {
            $enturmacao = null;
        }

        $modalidade = $request->get('modalidade');
        $modalidade = ($modalidade && $modalidade >= 1 && $modalidade <= 4) ? (int) $modalidade : null;
        $dependencia = in_array($request->get('dependencia'), ['sim', 'nao'], true) ? $request->get('dependencia') : null;
        $turnoId = $request->get('turno') ? (int) $request->get('turno') : null;

        $filters = [
            'ano' => $anoValido,
            'enturmacao' => $enturmacao,
            'instituicao' => $request->get('ref_cod_instituicao') ? (int) $request->get('ref_cod_instituicao') : null,
            'escola' => $request->get('ref_cod_escola') ? (int) $request->get('ref_cod_escola') : null,
            'curso' => $request->get('ref_cod_curso') ? (int) $request->get('ref_cod_curso') : null,
            'modalidade' => $modalidade,
            'dependencia' => $dependencia,
            'turno' => $turnoId,
        ];

        $result = app(BiMatriculasReportsService::class)->getReport($report, $filters);
        $exportData = $result['exportData'] ?? [];
        $headings = !empty($exportData[0]) ? array_keys($exportData[0]) : [];

        $filename = 'bi_matriculas_' . str_replace(['-'], '_', $report) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new BiThemeExport($exportData, $headings),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function printPdf(Request $request)
    {
        Gate::authorize('view', BisProcess::matriculas());

        $sectionTitle = $request->input('sectionTitle', 'Relatório');
        $chartImage = $request->input('chartImage'); // base64 data URL

        $logoBase64 = $this->resolveLogoBase64();

        $html = view('bis::matriculas.print-pdf', [
            'sectionTitle' => $sectionTitle,
            'chartImage' => $chartImage,
            'logoBase64' => $logoBase64,
        ])->render();

        $pdf = app('dompdf.wrapper')->loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'bi_matriculas_' . \Illuminate\Support\Str::slug($sectionTitle) . '_' . now()->format('Y-m-d_His') . '.pdf',
            ['Content-Type' => 'application/pdf'],
            'inline'
        );
    }

    private function resolveLogoBase64(): ?string
    {
        $logoPath = config('legacy.config.ieducar_image') ?? config('legacy.app.template.pdf.logo') ?? 'intranet/imagens/brasao-republica.png';
        if (empty($logoPath) || str_starts_with((string) $logoPath, 'http')) {
            $logoPath = 'intranet/imagens/brasao-republica.png';
        }
        $fullPath = str_starts_with($logoPath, DIRECTORY_SEPARATOR) || str_starts_with($logoPath, '/') ? $logoPath : public_path($logoPath);
        if (is_file($fullPath)) {
            $mime = \finfo_file(\finfo_open(FILEINFO_MIME_TYPE), $fullPath);
            $data = base64_encode(file_get_contents($fullPath));

            return "data:{$mime};base64,{$data}";
        }

        return null;
    }
}
