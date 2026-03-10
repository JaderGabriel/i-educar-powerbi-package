<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use iEducar\Packages\Bis\BisProcess;
use iEducar\Packages\Bis\Exports\BiThemeExport;
use iEducar\Packages\Bis\Services\BiChartsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeController extends BisBaseController
{
    private static function themes(): array
    {
        return [
            'matriculas' => ['title' => 'BI - Matrículas', 'process' => BisProcess::matriculas()],
            'turmas' => ['title' => 'BI - Turmas', 'process' => BisProcess::turmas()],
            'lancamentos' => ['title' => 'BI - Lançamentos', 'process' => BisProcess::lancamentos()],
            'indicadores' => ['title' => 'BI - Indicadores', 'process' => BisProcess::indicadores()],
            'inclusao-diversidade' => ['title' => 'BI - Inclusão e Diversidade', 'process' => BisProcess::inclusaoDiversidade()],
            'busca-ativa' => ['title' => 'BI - Busca Ativa', 'process' => BisProcess::buscaAtiva()],
            'educacenso' => ['title' => 'BI - Educacenso/INEP', 'process' => BisProcess::educacenso()],
        ];
    }

    public function show(Request $request, string $theme): View
    {
        if (!isset(self::themes()[$theme])) {
            abort(404);
        }

        $config = self::themes()[$theme];
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
            'chartDescriptions' => $result['chartDescriptions'] ?? [],
            'exportData' => $result['exportData'],
            'exportUrl' => $exportUrl,
            'anosLetivos' => $anosLetivos,
            'anoSelecionado' => $anoValido,
            'data' => $data,
        ]);
    }

    public function export(Request $request, string $theme): BinaryFileResponse
    {
        if (!isset(self::themes()[$theme])) {
            abort(404);
        }

        $config = self::themes()[$theme];
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
