<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use App\Process;
use iEducar\Packages\Bis\Exports\BiThemeExport;
use iEducar\Packages\Bis\Services\BiChartsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeController extends BisBaseController
{
    private const THEMES = [
        'matriculas' => ['title' => 'BI - Matrículas', 'process' => Process::BI_MATRICULAS],
        'turmas' => ['title' => 'BI - Turmas', 'process' => Process::BI_TURMAS],
        'lancamentos' => ['title' => 'BI - Lançamentos', 'process' => Process::BI_LANCAMENTOS],
        'indicadores' => ['title' => 'BI - Indicadores', 'process' => Process::BI_INDICADORES],
        'inclusao-diversidade' => ['title' => 'BI - Inclusão e Diversidade', 'process' => Process::BI_INCLUSAO_DIVERSIDADE],
        'busca-ativa' => ['title' => 'BI - Busca Ativa', 'process' => Process::BI_BUSCA_ATIVA],
        'educacenso' => ['title' => 'BI - Educacenso/INEP', 'process' => Process::BI_EDUCACENSO],
    ];

    public function show(Request $request, string $theme): View
    {
        if (!isset(self::THEMES[$theme])) {
            abort(404);
        }

        $config = self::THEMES[$theme];
        Gate::authorize('view', $config['process']);

        $service = app(BiChartsService::class);
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

        $result = $service->getForTheme($theme, $anoValido);
        $exportUrl = route('bis.theme.export', ['theme' => $theme]) . '?' . http_build_query(['ano' => $anoValido]);
        $data = $result['data'] ?? [];

        $chartTitles = match ($theme) {
            'lancamentos' => ['notas_etapa' => 'Média de Notas por Etapa', 'faltas_etapa' => 'Faltas por Etapa'],
            'indicadores' => [
                'evasao' => 'Indicador de Evasão',
                'aprovacao' => 'Indicador de Aprovação',
                'reprovacao' => 'Taxa de Reprovação',
                'reclassificacao' => 'Taxa de Reclassificação',
                'abandono' => 'Taxa de Abandono',
                'distorcao_idade_serie' => 'Distorção Idade-Série',
                'beneficios' => 'Benefícios Utilizados',
                'uniformes' => 'Uniformes e Materiais Distribuídos',
            ],
            'inclusao-diversidade' => [
                'por_deficiencia' => 'Matrículas por Deficiência',
                'por_raca' => 'Matrículas por Cor/Raça',
                'por_genero' => 'Matrículas por Gênero',
                'por_aee' => 'AEE - Atendimento Educacional Especializado',
            ],
            'busca-ativa' => [
                'por_resultado' => 'Busca Ativa por Resultado',
                'evolucao' => 'Evolução de Casos da Busca Ativa',
                'programa_evasao' => 'Alunos no Programa de Evasão',
            ],
            'educacenso' => [
                'cobertura_alunos' => 'Cobertura INEP - Alunos',
                'cobertura_turmas' => 'Cobertura INEP - Turmas',
                'cobertura_escolas' => 'Cobertura INEP - Escolas',
                'registros_tipo' => 'Registros Educacenso por Tipo',
            ],
            default => [],
        };

        return view('bis::themes.show', [
            'theme' => $theme,
            'title' => $config['title'],
            'charts' => $result['charts'],
            'chartTitles' => $chartTitles,
            'exportData' => $result['exportData'],
            'exportUrl' => $exportUrl,
            'anosLetivos' => $anosLetivos,
            'anoSelecionado' => $anoValido,
            'data' => $data,
        ]);
    }

    public function export(Request $request, string $theme): BinaryFileResponse
    {
        if (!isset(self::THEMES[$theme])) {
            abort(404);
        }

        $config = self::THEMES[$theme];
        Gate::authorize('view', $config['process']);

        $ano = $request->get('ano') ? (int) $request->get('ano') : null;
        $result = app(BiChartsService::class)->getForTheme($theme, $ano);
        $exportData = $result['exportData'] ?? [];
        $headings = !empty($exportData[0]) ? array_keys($exportData[0]) : [];

        $filename = 'bi_' . $theme . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new BiThemeExport($exportData, $headings),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
