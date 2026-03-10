<?php

namespace iEducar\Packages\Bis\Services;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BiMatriculasReportsService
{
    /** Relatórios disponíveis no BI Matrículas */
    public const REPORTS = [
        'por-curso' => ['title' => 'Matrículas por Curso', 'chart_type' => 'bar'],
        'por-ano' => ['title' => 'Matrículas por Ano', 'chart_type' => 'line'],
        'por-escola' => ['title' => 'Matrículas por Escola', 'chart_type' => 'bar'],
        'por-serie' => ['title' => 'Matrículas por Etapa/Ano', 'chart_type' => 'bar'],
        'por-situacao' => ['title' => 'Matrículas por Situação', 'chart_type' => 'pie'],
        'por-turno' => ['title' => 'Matrículas por Turno', 'chart_type' => 'bar'],
        'por-modalidade' => ['title' => 'Matrículas por Modalidade', 'chart_type' => 'pie'],
        'por-dependencia' => ['title' => 'Matrículas com/sem Dependência', 'chart_type' => 'pie'],
    ];

    /** modalidade_curso: 1=Regular, 2=Educação especial, 3=EJA, 4=Educação profissional */
    public const MODALIDADE_LABELS = [
        1 => 'Ensino regular',
        2 => 'Educação especial',
        3 => 'Educação de Jovens e Adultos (EJA)',
        4 => 'Educação profissional',
    ];

    /** Situação de enturmação: null = todas, 'enturmados' = só enturmados, 'nao_enturmados' = só não enturmados */
    public const FILTER_ENTURMACAO_TODAS = null;
    public const FILTER_ENTURMACAO_ENTURMADOS = 'enturmados';
    public const FILTER_ENTURMACAO_NAO_ENTURMADOS = 'nao_enturmados';

    /**
     * Ordem pedagógica INEP das etapas de ensino (Educação Infantil → EF → EM → EJA → Profissional).
     * Usado para ordenar séries/etapas no gráfico Matrículas por Etapa/Ano.
     */
    private const ORDEM_INEP_ETAPA = [
        1 => 1,    // Ed. Infantil - Creche
        2 => 2,    // Ed. Infantil - Pré-escola
        3 => 3,    // Ed. Infantil - Unificada
        56 => 4,   // Ed. Infantil e EF - Multietapa
        14 => 10,  // EF 9 anos - 1º Ano
        15 => 11,  // 2º Ano
        16 => 12,  // 3º Ano
        17 => 13,  // 4º Ano
        18 => 14,  // 5º Ano
        19 => 15,  // 6º Ano
        20 => 16,  // 7º Ano
        21 => 17,  // 8º Ano
        41 => 18,  // 9º Ano
        22 => 19,  // EF - Multi
        23 => 20,  // EF - Correção de Fluxo
        24 => 21,  // EF 8 e 9 - Multi
        25 => 30,  // EM - 1ª Série
        26 => 31,  // 2ª Série
        27 => 32,  // 3ª Série
        28 => 33,  // 4ª Série
        29 => 34,  // EM - Não Seriada
        35 => 35,  // EM Normal - 1ª
        36 => 36,  // 2ª
        37 => 37,  // 3ª
        38 => 38,  // 4ª
        30 => 40,  // Técnico Integrado 1ª
        31 => 41, 32 => 42, 33 => 43, 34 => 44,
        39 => 50,  // Técnico Concomitante
        40 => 51,  // Técnico Subsequente
        64 => 52,  // Técnico Misto
        68 => 53,  // FIC Concomitante
        69 => 60,  // EJA - EF Anos iniciais
        70 => 61,  // EJA - EF Anos finais
        72 => 62,  // EJA - EF Anos iniciais e finais
        71 => 63,  // EJA - EM
        73 => 64, 74 => 65, 67 => 66,
    ];

    /**
     * @param array{ano?: int|null, enturmacao?: string|null, instituicao?: int|null, escola?: int|null, curso?: int|null, modalidade?: int|null, dependencia?: string|null, turno?: int|null} $filters
     * @return array{chart: Chart|null, data: Collection, exportData: array}
     */
    public function getReport(string $reportKey, array $filters = []): array
    {
        if (!isset(self::REPORTS[$reportKey])) {
            return ['chart' => null, 'data' => collect(), 'exportData' => []];
        }

        return match ($reportKey) {
            'por-curso' => $this->getPorCurso($filters),
            'por-ano' => $this->getPorAno($filters),
            'por-escola' => $this->getPorEscola($filters),
            'por-serie' => $this->getPorSerie($filters),
            'por-situacao' => $this->getPorSituacao($filters),
            'por-turno' => $this->getPorTurno($filters),
            'por-modalidade' => $this->getPorModalidade($filters),
            'por-dependencia' => $this->getPorDependencia($filters),
            default => ['chart' => null, 'data' => collect(), 'exportData' => []],
        };
    }

    /**
     * @return array{chart: Chart|null, data: Collection, exportData: array}
     */
    public function getAllReports(array $filters = []): array
    {
        $reports = [];
        foreach (array_keys(self::REPORTS) as $key) {
            $reports[$key] = $this->getReport($key, $filters);
        }
        return $reports;
    }

    /**
     * @param array{ano?: int|null, enturmacao?: string|null, instituicao?: int|null, escola?: int|null, curso?: int|null, modalidade?: int|null, dependencia?: string|null, turno?: int|null} $filters
     */
    private function applyBaseFilters($q, array $filters, string $alias = 'm', ?string $escolaAlias = null): \Illuminate\Database\Query\Builder
    {
        $ano = $filters['ano'] ?? null;
        $enturmacao = $filters['enturmacao'] ?? null;
        $instituicaoId = $filters['instituicao'] ?? null;
        $escolaId = $filters['escola'] ?? null;
        $cursoId = $filters['curso'] ?? null;
        $modalidade = $filters['modalidade'] ?? null;
        $dependencia = $filters['dependencia'] ?? null;
        $turnoId = $filters['turno'] ?? null;

        $q->where("{$alias}.ativo", 1);

        if ($ano !== null) {
            $q->where("{$alias}.ano", $ano);
        }
        if ($instituicaoId) {
            $ea = $escolaAlias ?? 'e_filtro';
            if (!$escolaAlias) {
                $q->join('pmieducar.escola as e_filtro', "{$alias}.ref_ref_cod_escola", '=', 'e_filtro.cod_escola');
            }
            $q->where("{$ea}.ref_cod_instituicao", $instituicaoId);
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
        if ($dependencia === 'sim') {
            $q->where("{$alias}.dependencia", true);
        } elseif ($dependencia === 'nao') {
            $q->where(function ($w) use ($alias) {
                $w->where("{$alias}.dependencia", false)->orWhereNull("{$alias}.dependencia");
            });
        }
        if ($enturmacao === self::FILTER_ENTURMACAO_ENTURMADOS) {
            $q->whereExists(function ($sub) use ($alias, $turnoId) {
                $sub->selectRaw(1)
                    ->from('pmieducar.matricula_turma as mt')
                    ->leftJoin('pmieducar.turma as t_turno', 'mt.ref_cod_turma', '=', 't_turno.cod_turma')
                    ->whereColumn('mt.ref_cod_matricula', "{$alias}.cod_matricula")
                    ->where('mt.ativo', 1);
                if ($turnoId) {
                    $sub->whereRaw('COALESCE(mt.turno_id, t_turno.turma_turno_id) = ?', [$turnoId]);
                }
            });
        } elseif ($enturmacao === self::FILTER_ENTURMACAO_NAO_ENTURMADOS) {
            $q->whereNotExists(function ($sub) use ($alias) {
                $sub->selectRaw(1)
                    ->from('pmieducar.matricula_turma as mt')
                    ->whereColumn('mt.ref_cod_matricula', "{$alias}.cod_matricula")
                    ->where('mt.ativo', 1);
            });
        } elseif ($turnoId) {
            $q->whereExists(function ($sub) use ($alias, $turnoId) {
                $sub->selectRaw(1)
                    ->from('pmieducar.matricula_turma as mt')
                    ->leftJoin('pmieducar.turma as t_turno', 'mt.ref_cod_turma', '=', 't_turno.cod_turma')
                    ->whereColumn('mt.ref_cod_matricula', "{$alias}.cod_matricula")
                    ->where('mt.ativo', 1)
                    ->whereRaw('COALESCE(mt.turno_id, t_turno.turma_turno_id) = ?', [$turnoId]);
            });
        }
        return $q;
    }

    private function getPorCurso(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.curso as c', 'm.ref_cod_curso', '=', 'c.cod_curso')
            ->selectRaw('c.nm_curso as curso, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $data = $q->groupBy('c.nm_curso')->orderByDesc('total')->limit(15)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'curso', 'total', self::REPORTS['por-curso']['title'] . $tituloAno, 'bar');
        $exportData = $data->map(fn ($r) => ['curso' => $r->curso, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['curso' => 'Nenhum', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    private function getPorAno(array $filters): array
    {
        $anos = DB::table('pmieducar.escola_ano_letivo')->where('ativo', 1)->distinct()->pluck('ano');
        $anoMin = $anos->min() ?? (now()->year - 5);
        $anoMax = $anos->max() ?? now()->year;

        $q = DB::table('pmieducar.matricula as m')
            ->selectRaw('m.ano::text as ano, count(*) as total')
            ->where('m.ativo', 1)
            ->whereBetween('m.ano', [$anoMin, $anoMax]);

        $q = $this->applyBaseFilters($q, array_merge($filters, ['ano' => null]), 'm');

        $data = $q->groupBy('m.ano')->orderBy('m.ano')->get();
        $chart = $this->buildChart($data, 'ano', 'total', self::REPORTS['por-ano']['title'], 'line');
        $exportData = $data->map(fn ($r) => ['ano' => $r->ano, 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['ano' => '-', 'total' => 0]]];
    }

    private function getPorEscola(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.escola as e', 'm.ref_ref_cod_escola', '=', 'e.cod_escola')
            ->selectRaw('relatorio.get_nome_escola(e.cod_escola) as escola, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 'm', 'e');

        $data = $q->groupByRaw('relatorio.get_nome_escola(e.cod_escola)')->orderByDesc('total')->limit(15)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'escola', 'total', self::REPORTS['por-escola']['title'] . $tituloAno, 'bar');
        $exportData = $data->map(fn ($r) => ['escola' => $r->escola, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['escola' => 'Nenhuma', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    private function getPorSerie(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $ordemCase = $this->buildOrdemInepCase();
        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.serie as s', 'm.ref_ref_cod_serie', '=', 's.cod_serie')
            ->leftJoin('pmieducar.curso as c', 's.ref_cod_curso', '=', 'c.cod_curso')
            ->leftJoin('pmieducar.nivel_ensino as ne', 'c.ref_cod_nivel_ensino', '=', 'ne.cod_nivel_ensino')
            ->selectRaw("s.nm_serie as serie, min({$ordemCase}) as ordem_inep, count(*) as total");

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $data = $q->groupBy('s.nm_serie')
            ->orderBy('ordem_inep')
            ->orderBy('s.nm_serie')
            ->limit(15)
            ->get()
            ->map(fn ($r) => (object) ['serie' => $r->serie, 'total' => $r->total]);
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'serie', 'total', self::REPORTS['por-serie']['title'] . $tituloAno, 'bar');
        $exportData = $data->map(fn ($r) => ['serie' => $r->serie, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['serie' => 'Nenhuma', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    /** Retorna expressão SQL CASE para ordem pedagógica INEP. Quando etapa_educacenso é NULL, infere por nivel_ensino. */
    private function buildOrdemInepCase(): string
    {
        $cases = [];
        foreach (self::ORDEM_INEP_ETAPA as $etapaId => $ordem) {
            $cases[] = "when s.etapa_educacenso = {$etapaId} then {$ordem}";
        }
        $whenClauses = implode(' ', $cases);
        $nivelFallback = "when lower(ne.nm_nivel) like '%infantil%' then 100 + coalesce(s.etapa_curso, 0) " .
            "when lower(ne.nm_nivel) like '%fundamental%' then 200 + coalesce(s.etapa_curso, 0) " .
            "when lower(ne.nm_nivel) like '%médio%' or lower(ne.nm_nivel) like '%medio%' then 300 + coalesce(s.etapa_curso, 0) " .
            "when lower(ne.nm_nivel) like '%eja%' or lower(ne.nm_nivel) like '%jovens%' then 400 + coalesce(s.etapa_curso, 0) " .
            "else 500 + coalesce(s.etapa_curso, 0)";

        return "(case when s.etapa_educacenso is not null then (case {$whenClauses} else 9999 end) " .
            "else (case {$nivelFallback} end) end)";
    }

    private function getPorSituacao(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $situacoes = [
            1 => 'Aprovado',
            2 => 'Reprovado',
            3 => 'Cursando',
            4 => 'Transferido',
            5 => 'Reclassificado',
            6 => 'Abandono',
            7 => 'Em exame',
            8 => 'Aprovado após exame',
        ];

        $q = DB::table('pmieducar.matricula as m')
            ->selectRaw('coalesce(m.aprovado, 0) as aprovado, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $raw = $q->groupBy('m.aprovado')->orderByDesc('total')->get();

        $data = $raw->map(fn ($r) => (object) [
            'situacao' => $situacoes[$r->aprovado] ?? 'Outros',
            'total' => $r->total,
        ]);

        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'situacao', 'total', self::REPORTS['por-situacao']['title'] . $tituloAno, 'pie');
        $exportData = $data->map(fn ($r) => ['situacao' => $r->situacao, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['situacao' => '-', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    /** Gráfico por turno - apenas matrículas enturmadas (via matricula_turma + turma) */
    private function getPorTurno(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.matricula_turma as mt', function ($j) {
                $j->on('m.cod_matricula', '=', 'mt.ref_cod_matricula')->where('mt.ativo', 1);
            })
            ->leftJoin('pmieducar.turma as t', 'mt.ref_cod_turma', '=', 't.cod_turma')
            ->leftJoin('pmieducar.turma_turno as tt', DB::raw('tt.id'), '=', DB::raw('COALESCE(mt.turno_id, t.turma_turno_id)'))
            ->selectRaw('coalesce(tt.nome, \'Sem turno\') as turno, count(distinct m.cod_matricula) as total');

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $data = $q->groupBy('tt.nome')->orderByDesc('total')->limit(10)->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'turno', 'total', self::REPORTS['por-turno']['title'] . $tituloAno, 'bar');
        $exportData = $data->map(fn ($r) => ['turno' => $r->turno, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['turno' => 'Nenhum', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    /** Gráfico por modalidade do curso */
    private function getPorModalidade(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.curso as c', 'm.ref_cod_curso', '=', 'c.cod_curso')
            ->selectRaw('coalesce(c.modalidade_curso, 0) as modalidade, count(*) as total');

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $raw = $q->groupBy('c.modalidade_curso')->orderByDesc('total')->get();

        $data = $raw->map(fn ($r) => (object) [
            'modalidade' => self::MODALIDADE_LABELS[$r->modalidade] ?? 'Outras',
            'total' => $r->total,
        ]);

        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'modalidade', 'total', self::REPORTS['por-modalidade']['title'] . $tituloAno, 'pie');
        $exportData = $data->map(fn ($r) => ['modalidade' => $r->modalidade, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['modalidade' => '-', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    /** Gráfico com/sem dependência */
    private function getPorDependencia(array $filters): array
    {
        $ano = $filters['ano'] ?? null;
        $tipoExpr = "case when coalesce(m.dependencia, false) then 'Com dependência' else 'Sem dependência' end";
        $q = DB::table('pmieducar.matricula as m')
            ->selectRaw("{$tipoExpr} as tipo, count(*) as total");

        $q = $this->applyBaseFilters($q, $filters, 'm');

        $data = $q->groupByRaw($tipoExpr)->orderByDesc('total')->get();
        $tituloAno = $ano ? " ({$ano})" : ' (todos os anos)';
        $chart = $this->buildChart($data, 'tipo', 'total', self::REPORTS['por-dependencia']['title'] . $tituloAno, 'pie');
        $exportData = $data->map(fn ($r) => ['tipo' => $r->tipo, 'ano' => $ano ?? 'todos', 'total' => $r->total])->toArray();

        return ['chart' => $chart, 'data' => $data, 'exportData' => $exportData ?: [['tipo' => '-', 'ano' => $ano ?? '-', 'total' => 0]]];
    }

    /** Paleta de cores para gráficos (Chart.js) */
    private const CHART_COLORS = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#06B6D4', '#EC4899', '#84CC16', '#F97316', '#6366F1',
        '#14B8A6', '#A855F7', '#EAB308', '#22C55E', '#E11D48',
    ];

    private function buildChart(Collection $data, string $labelKey, string $valueKey, string $title, string $type): ?Chart
    {
        if ($data->isEmpty()) {
            return null;
        }

        $labels = $data->pluck($labelKey)->toArray();
        $values = $data->pluck($valueKey)->map(fn ($v) => (int) $v)->toArray();
        $chartType = $type === 'pie' ? 'doughnut' : $type;

        $chart = new Chart();
        $dataset = $chart->labels($labels)
            ->dataset('Total', $chartType, $values);

        $count = count($values);
        $colors = array_slice(self::CHART_COLORS, 0, max($count, 1));
        if ($count > count($colors)) {
            $colors = array_merge($colors, array_fill(0, $count - count($colors), self::CHART_COLORS[0]));
        }
        $dataset->backgroundColor($colors)->color($colors);

        $chart->title($title)->height(300)
            ->options([
                'legend' => ['display' => true, 'position' => 'bottom'],
                'responsive' => true,
            ]);

        return $chart;
    }

    /** Retorna escolas para filtro, opcionalmente por instituição */
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

    /** Retorna cursos para filtro, opcionalmente por escola */
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

    /** Retorna turnos cadastrados (turma_turno) */
    public function getTurnos(): Collection
    {
        return DB::table('pmieducar.turma_turno')
            ->select('id', 'nome')
            ->orderBy('id')
            ->get();
    }

    /** Retorna anos letivos cadastrados no sistema (escola_ano_letivo) */
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
