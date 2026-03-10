<?php

namespace iEducar\Packages\Bis\Services;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BiTurmasReportsService
{
    public const REPORTS = [
        'por-escola' => ['title' => 'Turmas por Escola', 'chart_type' => 'bar'],
        'por-curso' => ['title' => 'Turmas por Curso', 'chart_type' => 'bar'],
        'por-serie' => ['title' => 'Turmas por Série/Etapa', 'chart_type' => 'bar'],
        'por-turno' => ['title' => 'Turmas por Turno', 'chart_type' => 'bar'],
        'por-ano' => ['title' => 'Turmas por Ano', 'chart_type' => 'line'],
        'por-modalidade' => ['title' => 'Turmas por Modalidade', 'chart_type' => 'pie'],
    ];

    public const MODALIDADE_LABELS = [
        1 => 'Ensino regular',
        2 => 'Educação especial',
        3 => 'Educação de Jovens e Adultos (EJA)',
        4 => 'Educação profissional',
    ];

    private const CHART_COLORS = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#06B6D4', '#EC4899', '#84CC16', '#F97316', '#6366F1',
    ];

    public function getReport(string $reportKey, array $filters = []): array
    {
        if (!isset(self::REPORTS[$reportKey])) {
            return ['chart' => null, 'data' => collect(), 'exportData' => []];
        }

        return match ($reportKey) {
            'por-escola' => $this->getPorEscola($filters),
            'por-curso' => $this->getPorCurso($filters),
            'por-serie' => $this->getPorSerie($filters),
            'por-turno' => $this->getPorTurno($filters),
            'por-ano' => $this->getPorAno($filters),
            'por-modalidade' => $this->getPorModalidade($filters),
            default => ['chart' => null, 'data' => collect(), 'exportData' => []],
        };
    }

    public function getAllReports(array $filters = []): array
    {
        $reports = [];
        foreach (array_keys(self::REPORTS) as $key) {
            $reports[$key] = $this->getReport($key, $filters);
        }

        return $reports;
    }

    private function applyBaseFilters($q, array $filters, string $alias = 't', ?string $escolaAlias = null): \Illuminate\Database\Query\Builder
    {
        $ano = $filters['ano'] ?? null;
        $instituicaoId = $filters['instituicao'] ?? null;
        $escolaId = $filters['escola'] ?? null;
        $cursoId = $filters['curso'] ?? null;
        $modalidade = $filters['modalidade'] ?? null;
        $turnoId = $filters['turno'] ?? null;

        $q->where("{$alias}.ativo", 1);

        if ($ano !== null) {
            $q->where("{$alias}.ano", $ano);
        }
        if ($instituicaoId) {
            if ($escolaAlias) {
                $q->where("{$escolaAlias}.ref_cod_instituicao", $instituicaoId);
            } else {
                $q->whereExists(function ($sub) use ($alias, $instituicaoId) {
                    $sub->selectRaw(1)
                        ->from('pmieducar.escola as e_f')
                        ->whereColumn('e_f.cod_escola', "{$alias}.ref_ref_cod_escola")
                        ->where('e_f.ref_cod_instituicao', $instituicaoId);
                });
            }
        }
        if ($escolaId) {
            $q->where("{$alias}.ref_ref_cod_escola", $escolaId);
        }
        if ($cursoId) {
            $q->where("{$alias}.ref_cod_curso", $cursoId);
        }
        if ($modalidade && $modalidade >= 1 && $modalidade <= 4) {
            $q->whereIn("{$alias}.ref_cod_curso", function ($sub) use ($modalidade) {
                $sub->select('cod_curso')->from('pmieducar.curso')->where('modalidade_curso', $modalidade);
            });
        }
        if ($turnoId) {
            $q->where("{$alias}.turma_turno_id", $turnoId);
        }

        return $q;
    }

    private function getPorEscola(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.turma as t')
            ->join('pmieducar.escola as e', 't.ref_ref_cod_escola', '=', 'e.cod_escola')
            ->selectRaw('relatorio.get_nome_escola(e.cod_escola) as escola, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 't', 'e');

        $data = $q->groupByRaw('relatorio.get_nome_escola(e.cod_escola)')->orderByDesc('total')->limit(15)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'escola', 'total', self::REPORTS['por-escola']['title'] . $tituloAno, 'bar');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['escola' => $r->escola, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray()
                ?: [['escola' => 'Nenhuma', 'ano' => $ano ?? '-', 'total' => 0]],
        ];
    }

    private function getPorCurso(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.turma as t')
            ->join('pmieducar.curso as c', 't.ref_cod_curso', '=', 'c.cod_curso')
            ->selectRaw('c.nm_curso as curso, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 't');

        $data = $q->groupBy('c.nm_curso')->orderByDesc('total')->limit(15)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'curso', 'total', self::REPORTS['por-curso']['title'] . $tituloAno, 'bar');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['curso' => $r->curso, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray()
                ?: [['curso' => 'Nenhum', 'ano' => $ano ?? '-', 'total' => 0]],
        ];
    }

    private function getPorSerie(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.turma as t')
            ->join('pmieducar.serie as s', 't.ref_ref_cod_serie', '=', 's.cod_serie')
            ->selectRaw('s.nm_serie as serie, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 't');

        $data = $q->groupBy('s.nm_serie')->orderBy('s.nm_serie')->limit(15)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'serie', 'total', self::REPORTS['por-serie']['title'] . $tituloAno, 'bar');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['serie' => $r->serie, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray()
                ?: [['serie' => 'Nenhuma', 'ano' => $ano ?? '-', 'total' => 0]],
        ];
    }

    private function getPorTurno(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.turma as t')
            ->leftJoin('pmieducar.turma_turno as tt', 't.turma_turno_id', '=', 'tt.id')
            ->selectRaw('coalesce(tt.nome, \'Sem turno\') as turno, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 't');

        $data = $q->groupBy('tt.nome')->orderByDesc('total')->limit(10)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'turno', 'total', self::REPORTS['por-turno']['title'] . $tituloAno, 'bar');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['turno' => $r->turno, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray()
                ?: [['turno' => 'Nenhum', 'ano' => $ano ?? '-', 'total' => 0]],
        ];
    }

    private function getPorAno(array $filters): array
    {
        $anos = DB::table('pmieducar.escola_ano_letivo')->where('ativo', 1)->distinct()->pluck('ano');
        $anoMin = $anos->min() ?? (now()->year - 5);
        $anoMax = $anos->max() ?? now()->year;

        $q = DB::table('pmieducar.turma as t')
            ->selectRaw('t.ano::text as ano, count(*) as total')
            ->where('t.ativo', 1)
            ->whereBetween('t.ano', [$anoMin, $anoMax]);

        $q = $this->applyBaseFilters($q, array_merge($filters, ['ano' => null]), 't');

        $data = $q->groupBy('t.ano')->orderBy('t.ano')->get();
        $chart = $this->buildChart($data, 'ano', 'total', self::REPORTS['por-ano']['title'], 'line');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['ano' => $r->ano, 'total' => $r->total])->toArray()
                ?: [['ano' => '-', 'total' => 0]],
        ];
    }

    private function getPorModalidade(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.turma as t')
            ->join('pmieducar.curso as c', 't.ref_cod_curso', '=', 'c.cod_curso')
            ->selectRaw('coalesce(c.modalidade_curso, 0) as modalidade, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 't');

        $raw = $q->groupBy('c.modalidade_curso')->orderByDesc('total')->get();

        $data = $raw->map(fn ($r) => (object) [
            'modalidade' => self::MODALIDADE_LABELS[$r->modalidade] ?? 'Outras',
            'total' => $r->total,
        ]);

        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'modalidade', 'total', self::REPORTS['por-modalidade']['title'] . $tituloAno, 'pie');

        return [
            'chart' => $chart,
            'data' => $data,
            'exportData' => $data->map(fn ($r) => ['modalidade' => $r->modalidade, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray()
                ?: [['modalidade' => '-', 'ano' => $ano ?? '-', 'total' => 0]],
        ];
    }

    private function buildChart(Collection $data, string $labelKey, string $valueKey, string $title, string $type): ?Chart
    {
        if ($data->isEmpty()) {
            return null;
        }

        $labels = $data->pluck($labelKey)->toArray();
        $values = $data->pluck($valueKey)->map(fn ($v) => (int) $v)->toArray();
        $chartType = $type === 'pie' ? 'doughnut' : $type;

        $chart = new Chart();
        $dataset = $chart->labels($labels)->dataset('Total', $chartType, $values);

        $colors = array_slice(self::CHART_COLORS, 0, max(count($values), 1));
        if (count($values) > count($colors)) {
            $colors = array_merge($colors, array_fill(0, count($values) - count($colors), self::CHART_COLORS[0]));
        }
        $dataset->backgroundColor($colors)->color($colors);

        $chart->title($title)->height(300)
            ->options([
                'legend' => ['display' => true, 'position' => 'bottom'],
                'responsive' => true,
            ]);

        return $chart;
    }

    public function getEscolas(?int $instituicaoId = null): Collection
    {
        $ids = \App_Model_IedFinder::getEscolasByUser($instituicaoId);
        if (empty($ids)) {
            return collect();
        }

        return DB::table('pmieducar.escola')
            ->selectRaw('cod_escola as id, relatorio.get_nome_escola(cod_escola) as nome')
            ->whereIn('cod_escola', array_keys($ids))
            ->where('ativo', 1)
            ->orderByRaw('relatorio.get_nome_escola(cod_escola)')
            ->get();
    }

    public function getCursos(?int $escolaId = null): Collection
    {
        if ($escolaId) {
            $ids = \App_Model_IedFinder::getCursos($escolaId);
            if (empty($ids)) {
                return collect();
            }

            return DB::table('pmieducar.curso')
                ->select('cod_curso as id', 'nm_curso as nome')
                ->whereIn('cod_curso', array_keys($ids))
                ->where('ativo', 1)
                ->orderBy('nm_curso')
                ->get();
        }

        return DB::table('pmieducar.curso')
            ->select('cod_curso as id', 'nm_curso as nome')
            ->where('ativo', 1)
            ->orderBy('nm_curso')
            ->get();
    }

    public function getTurnos(): Collection
    {
        return DB::table('pmieducar.turma_turno')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();
    }

    public function getAnosLetivos(): Collection
    {
        return DB::table('pmieducar.escola_ano_letivo')
            ->where('ativo', 1)
            ->distinct()
            ->orderBy('ano')
            ->pluck('ano')
            ->map(fn ($ano) => (object) ['id' => $ano, 'nome' => (string) $ano])
            ->values();
    }
}
