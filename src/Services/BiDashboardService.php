<?php

namespace iEducar\Packages\Bis\Services;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BiDashboardService
{
    /** Retorna anos letivos cadastrados (pmieducar.escola_ano_letivo) */
    public function getAnosLetivos(): \Illuminate\Support\Collection
    {
        $anos = \Illuminate\Support\Facades\DB::table('pmieducar.escola_ano_letivo')
            ->where('ativo', 1)
            ->distinct()
            ->orderBy('ano')
            ->pluck('ano');

        $anoAtual = now()->year;
        if ($anos->isEmpty()) {
            $anos = collect([$anoAtual - 1, $anoAtual]);
        }

        return $anos->map(fn ($a) => (object) ['id' => $a, 'nome' => (string) $a])->values();
    }

    /**
     * @param int|null $ano Ano letivo (null = ano atual)
     */
    public function getSummary(?int $ano = null): array
    {
        $anoAtual = $ano ?? now()->year;

        $matriculasAtivas = DB::table('pmieducar.matricula')
            ->where('ano', $anoAtual)
            ->where('ativo', 1)
            ->count();

        $totalTurmas = DB::table('pmieducar.turma')
            ->where('ano', $anoAtual)
            ->where('ativo', 1)
            ->count();

        $totalEscolas = DB::table('pmieducar.escola')->where('ativo', 1)->count();

        $totalCursos = DB::table('pmieducar.curso')->where('ativo', 1)->count();

        $matriculasPorSituacao = DB::table('pmieducar.matricula')
            ->selectRaw("case aprovado when 1 then 'Aprovado' when 2 then 'Reprovado' when 3 then 'Cursando' when 4 then 'Transferido' when 5 then 'Reclassificado' when 6 then 'Abandono' when 7 then 'Em exame' when 8 then 'Aprovado após exame' else 'Outros' end as situacao, count(*) as total")
            ->where('ano', $anoAtual)
            ->where('ativo', 1)
            ->groupBy('aprovado')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $matriculasPorCurso = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.curso as c', 'm.ref_cod_curso', '=', 'c.cod_curso')
            ->selectRaw('c.nm_curso as curso, count(*) as total')
            ->where('m.ano', $anoAtual)
            ->where('m.ativo', 1)
            ->groupBy('c.nm_curso')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $turmasPorEscolaDetalhado = DB::table('pmieducar.turma as t')
            ->join('pmieducar.escola as e', 't.ref_ref_cod_escola', '=', 'e.cod_escola')
            ->leftJoin('pmieducar.turma_turno as tt', 't.turma_turno_id', '=', 'tt.id')
            ->selectRaw('relatorio.get_nome_escola(e.cod_escola) as escola, tt.nome as turno, count(*) as total')
            ->where('t.ano', $anoAtual)
            ->where('t.ativo', 1)
            ->groupByRaw('relatorio.get_nome_escola(e.cod_escola), tt.nome')
            ->get();

        $turmasPorEscola = $turmasPorEscolaDetalhado->groupBy('escola')->map(function ($itens, $escola) {
            $total = $itens->sum('total');
            $porTurno = $itens->filter(fn ($r) => !empty($r->turno))->map(fn ($r) => ($r->turno ?? 'Sem turno') . ': ' . $r->total)->implode(', ');
            if (empty(trim($porTurno))) {
                $porTurno = '-';
            }

            $tooltipLinhas = [$escola, "Total: {$total} turmas", $porTurno];

            return (object) [
                'escola' => $escola,
                'total' => $total,
                'porTurno' => $porTurno,
                'tooltip' => implode("\n", $tooltipLinhas),
            ];
        })->sortByDesc('total')->take(6)->values();

        $turmasPorEscolaLegacy = $turmasPorEscola->map(fn ($r) => (object) ['escola' => $r->escola, 'total' => $r->total]);

        $evolucaoAnual = DB::table('pmieducar.matricula')
            ->selectRaw('ano::text as ano, count(*) as total')
            ->where('ativo', 1)
            ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
            ->groupBy('ano')
            ->orderBy('ano')
            ->get();

        $charts = [];
        if ($matriculasPorCurso->isNotEmpty()) {
            $charts['matriculasCurso'] = $this->buildBarChartColored(
                $matriculasPorCurso->pluck('curso')->toArray(),
                $matriculasPorCurso->pluck('total')->map(fn ($v) => (int) $v)->toArray()
            );
        }
        if ($turmasPorEscola->isNotEmpty()) {
            $labels = $turmasPorEscola->pluck('escola')->toArray();
            $values = $turmasPorEscola->pluck('total')->map(fn ($v) => (int) $v)->toArray();
            $tooltips = $turmasPorEscola->pluck('tooltip')->toArray();
            $charts['turmasEscola'] = $this->buildPieChartTurmasEscola($labels, $values, $tooltips);
        }
        if ($evolucaoAnual->isNotEmpty()) {
            $charts['evolucao'] = $this->buildLineChartColored(
                $evolucaoAnual->pluck('ano')->toArray(),
                $evolucaoAnual->pluck('total')->map(fn ($v) => (int) $v)->toArray()
            );
        }

        return [
            'matriculasAtivas' => $matriculasAtivas,
            'totalTurmas' => $totalTurmas,
            'totalEscolas' => $totalEscolas,
            'totalCursos' => $totalCursos,
            'anoAtual' => $anoAtual,
            'matriculasPorSituacao' => $matriculasPorSituacao,
            'matriculasPorCurso' => $matriculasPorCurso,
            'turmasPorEscola' => $turmasPorEscolaLegacy,
            'turmasEscolaTooltips' => $turmasPorEscola->pluck('tooltip')->toArray(),
            'evolucaoAnual' => $evolucaoAnual,
            'charts' => $charts,
        ];
    }

    private const CHART_COLORS = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#06B6D4', '#EC4899', '#84CC16', '#F97316', '#6366F1',
    ];

    private function buildBarChartColored(array $labels, array $values): Chart
    {
        $chart = new Chart();
        $dataset = $chart->labels($labels)->dataset('Total', 'bar', $values);
        $colors = array_slice(self::CHART_COLORS, 0, count($values)) ?: [self::CHART_COLORS[0]];
        $dataset->backgroundColor($colors)->color($colors);
        $chart->height(250)->options([
            'responsive' => true,
            'legend' => ['display' => false],
            'scales' => [
                'yAxes' => [['ticks' => ['beginAtZero' => true]]],
            ],
        ]);

        return $chart;
    }

    /** Gráfico pie: proporções por escola, sem legenda, tooltip completo com nome e turmas por turno */
    private function buildPieChartTurmasEscola(array $labels, array $values, array $tooltips): Chart
    {
        $chart = new Chart();
        $dataset = $chart->labels($labels)->dataset('Turmas', 'pie', $values);
        $colors = array_slice(self::CHART_COLORS, 0, count($values)) ?: [self::CHART_COLORS[0]];
        $dataset->backgroundColor($colors)->color($colors);
        $chart->height(280)->options([
            'responsive' => true,
            'legend' => ['display' => false],
            'tooltips' => [
                'enabled' => true,
                'backgroundColor' => 'rgba(30, 41, 59, 0.95)',
                'titleFontSize' => 13,
                'bodyFontSize' => 12,
                'bodySpacing' => 6,
                'cornerRadius' => 8,
            ],
        ]);

        return $chart;
    }

    private function buildLineChartColored(array $labels, array $values): Chart
    {
        $chart = new Chart();
        $dataset = $chart->labels($labels)->dataset('Matrículas', 'line', $values);
        $dataset->backgroundColor('rgba(59, 130, 246, 0.2)')->color('#3B82F6');
        $chart->height(250)->options([
            'responsive' => true,
            'legend' => ['display' => false],
            'scales' => [
                'yAxes' => [['ticks' => ['beginAtZero' => true]]],
            ],
        ]);

        return $chart;
    }
}
