<?php

namespace iEducar\Packages\Bis\Services;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BiChartsService
{
    private const CHART_COLORS = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#06B6D4', '#EC4899', '#84CC16', '#F97316', '#6366F1',
    ];

    /**
     * Retorna gráficos e dados tabulares para cada tema BI.
     *
     * @param int|null $ano Ano letivo (null = ano atual)
     * @return array{charts: array<string, Chart>, data: array, exportData: array}
     */
    public function getForTheme(string $theme, ?int $ano = null): array
    {
        $ano = $ano ?? now()->year;
        return match ($theme) {
            'matriculas' => $this->getMatriculas($ano),
            'turmas' => $this->getTurmas($ano),
            'lancamentos' => $this->getLancamentos($ano),
            'indicadores' => $this->getIndicadores($ano),
            'inclusao-diversidade' => $this->getInclusaoDiversidade($ano),
            'busca-ativa' => $this->getBuscaAtiva($ano),
            'educacenso' => $this->getEducacenso($ano),
            default => ['charts' => [], 'data' => [], 'exportData' => []],
        };
    }

    /** Retorna anos letivos cadastrados */
    public function getAnosLetivos(): \Illuminate\Support\Collection
    {
        $anos = DB::table('pmieducar.escola_ano_letivo')
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

    private function getMatriculas(int $ano): array
    {
        $matriculasPorCurso = $this->getMatriculasPorCurso($ano);
        $matriculasPorAno = $this->getMatriculasPorAno();
        $charts = [];

        if ($matriculasPorCurso->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($matriculasPorCurso->pluck('curso')->toArray())
                ->dataset('Matrículas', 'bar', $matriculasPorCurso->pluck('total')->toArray());
            $chart->title('Matrículas por Curso (' . $ano . ')')->height(300);
            $charts['matriculas_curso'] = $chart;
        }

        if ($matriculasPorAno->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($matriculasPorAno->pluck('ano')->toArray())
                ->dataset('Matrículas', 'line', $matriculasPorAno->pluck('total')->toArray());
            $chart->title('Matrículas por Ano')->height(300);
            $charts['matriculas_ano'] = $chart;
        }

        $exportData = [];
        foreach ($matriculasPorCurso as $r) {
            $exportData[] = ['curso' => $r->curso, 'ano' => (string) $ano, 'total' => $r->total];
        }
        foreach ($matriculasPorAno as $r) {
            $exportData[] = ['curso' => '(todos)', 'ano' => $r->ano, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['curso' => 'Nenhum', 'ano' => '-', 'total' => 0]];
        }

        return [
            'charts' => $charts,
            'data' => ['matriculasPorCurso' => $matriculasPorCurso, 'matriculasPorAno' => $matriculasPorAno],
            'exportData' => $exportData,
        ];
    }

    private function getTurmas(int $ano): array
    {
        $turmasPorEscola = $this->getTurmasPorEscola($ano);
        $turmasPorAno = $this->getTurmasPorAno();
        $charts = [];

        if ($turmasPorEscola->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($turmasPorEscola->pluck('escola')->toArray())
                ->dataset('Turmas', 'bar', $turmasPorEscola->pluck('total')->toArray());
            $chart->title('Turmas por Escola (' . $ano . ')')->height(300);
            $charts['turmas_escola'] = $chart;
        }

        if ($turmasPorAno->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($turmasPorAno->pluck('ano')->toArray())
                ->dataset('Turmas', 'line', $turmasPorAno->pluck('total')->toArray());
            $chart->title('Turmas por Ano')->height(300);
            $charts['turmas_ano'] = $chart;
        }

        $exportData = [];
        foreach ($turmasPorEscola as $r) {
            $exportData[] = ['escola' => $r->escola, 'ano' => (string) $ano, 'total' => $r->total];
        }
        foreach ($turmasPorAno as $r) {
            $exportData[] = ['escola' => '(todas)', 'ano' => $r->ano, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['escola' => 'Nenhuma', 'ano' => '-', 'total' => 0]];
        }

        return [
            'charts' => $charts,
            'data' => ['turmasPorEscola' => $turmasPorEscola, 'turmasPorAno' => $turmasPorAno],
            'exportData' => $exportData,
        ];
    }

    private function getLancamentos(int $ano): array
    {
        $notasPorEtapa = $this->getMediaNotasPorEtapa($ano);
        $faltasPorEtapa = $this->getFaltasPorEtapa($ano);
        $charts = [];

        if ($notasPorEtapa->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($notasPorEtapa->pluck('etapa')->toArray())
                ->dataset('Média Geral', 'bar', $notasPorEtapa->pluck('media')->map(fn ($v) => round((float) $v, 2))->toArray());
            $chart->title('Média de Notas por Etapa (' . $ano . ')')->height(300);
            $charts['notas_etapa'] = $chart;
        }

        if ($faltasPorEtapa->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($faltasPorEtapa->pluck('etapa')->toArray())
                ->dataset('Total Faltas', 'line', $faltasPorEtapa->pluck('total')->toArray());
            $chart->title('Faltas por Etapa (' . $ano . ')')->height(300);
            $charts['faltas_etapa'] = $chart;
        }

        $exportData = [];
        foreach ($notasPorEtapa as $r) {
            $exportData[] = ['etapa' => $r->etapa, 'media' => round((float) $r->media, 2), 'total_faltas' => '-'];
        }
        foreach ($faltasPorEtapa as $r) {
            $exportData[] = ['etapa' => $r->etapa, 'media' => '-', 'total_faltas' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['etapa' => '-', 'media' => 0, 'total_faltas' => 0]];
        }

        return [
            'charts' => $charts,
            'data' => ['notasPorEtapa' => $notasPorEtapa, 'faltasPorEtapa' => $faltasPorEtapa],
            'exportData' => $exportData,
        ];
    }

    private function getIndicadores(int $ano): array
    {
        $evasao = $this->getIndicadorEvasao();
        $aprovacao = $this->getIndicadorAprovacao();
        $reprovacao = $this->getIndicadorReprovacao();
        $reclassificacao = $this->getIndicadorReclassificacao();
        $abandono = $this->getIndicadorAbandono();
        $distorcao = $this->getIndicadorDistorcaoIdadeSerie();
        $beneficios = $this->getIndicadorBeneficios($ano);
        $uniformes = $this->getIndicadorUniformes($ano);
        $charts = [];

        if ($evasao->isNotEmpty()) {
            $vals = $evasao->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $chart = new Chart();
            $chart->labels($evasao->pluck('ano')->toArray())
                ->dataset('Taxa Evasão (%)', 'line', $vals)->backgroundColor('rgba(239, 68, 68, 0.2)')->color('#EF4444');
            $chart->title('Indicador de Evasão')->height(300);
            $charts['evasao'] = $chart;
        }

        if ($aprovacao->isNotEmpty()) {
            $vals = $aprovacao->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($aprovacao->pluck('ano')->toArray())
                ->dataset('Taxa Aprovação (%)', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Indicador de Aprovação')->height(300);
            $charts['aprovacao'] = $chart;
        }

        if ($reprovacao->isNotEmpty()) {
            $vals = $reprovacao->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($reprovacao->pluck('ano')->toArray())
                ->dataset('Taxa Reprovação (%)', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Taxa de Reprovação')->height(300);
            $charts['reprovacao'] = $chart;
        }

        if ($reclassificacao->isNotEmpty()) {
            $vals = $reclassificacao->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($reclassificacao->pluck('ano')->toArray())
                ->dataset('Taxa Reclassificação (%)', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Taxa de Reclassificação')->height(300);
            $charts['reclassificacao'] = $chart;
        }

        if ($abandono->isNotEmpty()) {
            $vals = $abandono->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $chart = new Chart();
            $chart->labels($abandono->pluck('ano')->toArray())
                ->dataset('Taxa Abandono (%)', 'line', $vals)->backgroundColor('rgba(245, 158, 11, 0.2)')->color('#F59E0B');
            $chart->title('Taxa de Abandono')->height(300);
            $charts['abandono'] = $chart;
        }

        if ($distorcao->isNotEmpty()) {
            $vals = $distorcao->pluck('taxa')->map(fn ($v) => round((float) $v, 1))->toArray();
            $chart = new Chart();
            $chart->labels($distorcao->pluck('ano')->toArray())
                ->dataset('Taxa Distorção (%)', 'line', $vals)->backgroundColor('rgba(139, 92, 246, 0.2)')->color('#8B5CF6');
            $chart->title('Distorção Idade-Série')->height(300);
            $charts['distorcao_idade_serie'] = $chart;
        }

        if ($beneficios->isNotEmpty()) {
            $vals = $beneficios->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($beneficios->pluck('beneficio')->toArray())
                ->dataset('Alunos beneficiados', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Benefícios Utilizados (' . $ano . ')')->height(300);
            $charts['beneficios'] = $chart;
        }

        if ($uniformes->isNotEmpty()) {
            $vals = $uniformes->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($uniformes->pluck('categoria')->toArray())
                ->dataset('Quantidade', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Uniformes e Materiais Distribuídos (' . $ano . ')')->height(300);
            $charts['uniformes'] = $chart;
        }

        $exportData = [];
        foreach ($evasao as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Evasão', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($aprovacao as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Aprovação', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($reprovacao as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Reprovação', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($reclassificacao as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Reclassificação', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($abandono as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Abandono', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($distorcao as $r) {
            $exportData[] = ['ano' => $r->ano, 'tipo' => 'Distorção idade-série', 'taxa' => round((float) $r->taxa, 1)];
        }
        foreach ($beneficios as $r) {
            $exportData[] = ['beneficio' => $r->beneficio, 'total' => $r->total];
        }
        foreach ($uniformes as $r) {
            $exportData[] = ['categoria' => $r->categoria, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['ano' => '-', 'tipo' => '-', 'taxa' => 0]];
        }

        $chartDescriptions = [
            'evasao' => [
                'titulo' => 'Indicador de Evasão',
                'descricao' => 'Percentual de matrículas encerradas com situação de evasão (abandono) em relação ao total de matrículas do ano.',
                'calculo' => 'Taxa = (matrículas com aprovado = 6) / total de matrículas × 100',
            ],
            'aprovacao' => [
                'titulo' => 'Indicador de Aprovação',
                'descricao' => 'Percentual de matrículas aprovadas (concluídas com aprovação, reprovação por faltas ou aprovado após exame) em relação ao total.',
                'calculo' => 'Taxa = (matrículas com aprovado em 1, 2 ou 8) / total de matrículas × 100',
            ],
            'reprovacao' => [
                'titulo' => 'Taxa de Reprovação',
                'descricao' => 'Percentual de matrículas reprovadas (retido ou reprovado por faltas) em relação ao total de matrículas do ano.',
                'calculo' => 'Taxa = (matrículas com aprovado = 2 ou 14) / total de matrículas × 100',
            ],
            'reclassificacao' => [
                'titulo' => 'Taxa de Reclassificação',
                'descricao' => 'Percentual de matrículas reclassificadas (aluno avançou de série por desempenho) em relação ao total.',
                'calculo' => 'Taxa = (matrículas com aprovado = 5) / total de matrículas × 100',
            ],
            'abandono' => [
                'titulo' => 'Taxa de Abandono',
                'descricao' => 'Percentual de matrículas em situação de abandono. Correlaciona com o indicador de evasão.',
                'calculo' => 'Taxa = (matrículas com aprovado = 6) / total de matrículas × 100',
            ],
            'distorcao_idade_serie' => [
                'titulo' => 'Distorção Idade-Série',
                'descricao' => 'Percentual de alunos cuja idade está acima da idade ideal ou máxima esperada para a série cursada.',
                'calculo' => 'Taxa = (alunos com idade > idade_final/ideal da série) / total × 100. Idade calculada em 01/03 do ano letivo.',
            ],
            'beneficios' => [
                'titulo' => 'Benefícios Utilizados',
                'descricao' => 'Quantidade de alunos matriculados que utilizam cada tipo de benefício cadastrado (transporte, alimentação, etc.).',
                'calculo' => 'Contagem distinta de alunos por tipo de benefício vinculado (aluno_beneficio) no ano.',
            ],
            'uniformes' => [
                'titulo' => 'Uniformes e Materiais Distribuídos',
                'descricao' => 'Quantidade de itens de uniforme e material escolar distribuídos no ano letivo.',
                'calculo' => 'Soma das quantidades por categoria (kits completos, agasalhos, camisetas, tênis, etc.) das distribuições do ano.',
            ],
        ];

        return [
            'charts' => $charts,
            'chartDescriptions' => $chartDescriptions,
            'data' => [
                'evasao' => $evasao,
                'aprovacao' => $aprovacao,
                'reprovacao' => $reprovacao,
                'reclassificacao' => $reclassificacao,
                'abandono' => $abandono,
                'distorcao' => $distorcao,
                'beneficios' => $beneficios,
                'uniformes' => $uniformes,
            ],
            'exportData' => $exportData,
        ];
    }

    private function getInclusaoDiversidade(int $ano): array
    {
        $porDeficiencia = $this->getMatriculasPorDeficiencia($ano);
        $porRaca = $this->getMatriculasPorRaca($ano);
        $porGenero = $this->getMatriculasPorGenero($ano);
        $porAee = $this->getMatriculasPorAee($ano);
        $charts = [];

        if ($porDeficiencia->isNotEmpty()) {
            $vals = $porDeficiencia->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($porDeficiencia->pluck('deficiencia')->toArray())
                ->dataset('Matrículas', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Matrículas por Deficiência (' . $ano . ')')->height(300);
            $charts['por_deficiencia'] = $chart;
        }

        if ($porRaca->isNotEmpty()) {
            $vals = $porRaca->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($porRaca->pluck('raca')->toArray())
                ->dataset('Matrículas', 'pie', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Matrículas por Cor/Raça (' . $ano . ')')->height(300);
            $charts['por_raca'] = $chart;
        }

        if ($porGenero->isNotEmpty()) {
            $vals = $porGenero->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($porGenero->pluck('genero')->toArray())
                ->dataset('Matrículas', 'pie', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Matrículas por Gênero (' . $ano . ')')->height(300);
            $charts['por_genero'] = $chart;
        }

        if ($porAee->isNotEmpty()) {
            $vals = $porAee->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($porAee->pluck('tipo')->toArray())
                ->dataset('Matrículas', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('AEE - Atendimento Educacional Especializado (' . $ano . ')')->height(300);
            $charts['por_aee'] = $chart;
        }

        $exportData = [];
        foreach ($porDeficiencia as $r) {
            $exportData[] = ['categoria' => 'Deficiência', 'item' => $r->deficiencia, 'total' => $r->total];
        }
        foreach ($porRaca as $r) {
            $exportData[] = ['categoria' => 'Cor/Raça', 'item' => $r->raca, 'total' => $r->total];
        }
        foreach ($porGenero as $r) {
            $exportData[] = ['categoria' => 'Gênero', 'item' => $r->genero, 'total' => $r->total];
        }
        foreach ($porAee as $r) {
            $exportData[] = ['categoria' => 'AEE', 'item' => $r->tipo, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['categoria' => '-', 'item' => '-', 'total' => 0]];
        }

        return [
            'charts' => $charts,
            'data' => [
                'porDeficiencia' => $porDeficiencia,
                'porRaca' => $porRaca,
                'porGenero' => $porGenero,
                'porAee' => $porAee,
            ],
            'exportData' => $exportData,
        ];
    }

    private function getMatriculasPorDeficiencia(int $ano): Collection
    {
        return cache()->remember(
            'bis.inclusao_deficiencia.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula as m')
                ->join('pmieducar.aluno as a', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
                ->join('cadastro.fisica_deficiencia as fd', 'a.ref_idpes', '=', 'fd.ref_idpes')
                ->join('cadastro.deficiencia as d', 'fd.ref_cod_deficiencia', '=', 'd.cod_deficiencia')
                ->selectRaw('COALESCE(d.nm_deficiencia, \'Não informado\') as deficiencia, count(DISTINCT m.cod_matricula) as total')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->groupBy('d.nm_deficiencia')
                ->orderByDesc('total')
                ->limit(12)
                ->get()
        ) ?: collect();
    }

    private function getMatriculasPorRaca(int $ano): Collection
    {
        return cache()->remember(
            'bis.inclusao_raca.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula as m')
                ->join('pmieducar.aluno as a', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
                ->leftJoin('cadastro.fisica_raca as fr', 'a.ref_idpes', '=', 'fr.ref_idpes')
                ->leftJoin('cadastro.raca as r', 'fr.ref_cod_raca', '=', 'r.cod_raca')
                ->selectRaw('COALESCE(r.nm_raca, \'Não declarado\') as raca, count(DISTINCT m.cod_matricula) as total')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->groupBy('r.nm_raca')
                ->orderByDesc('total')
                ->get()
        ) ?: collect();
    }

    private function getMatriculasPorGenero(int $ano): Collection
    {
        return cache()->remember(
            'bis.inclusao_genero.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula as m')
                ->join('pmieducar.aluno as a', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
                ->join('cadastro.fisica as f', 'a.ref_idpes', '=', 'f.idpes')
                ->selectRaw("CASE f.sexo WHEN 'M' THEN 'Masculino' WHEN 'F' THEN 'Feminino' ELSE 'Não informado' END as genero, count(*) as total")
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->groupBy('f.sexo')
                ->orderByDesc('total')
                ->get()
        ) ?: collect();
    }

    private function getMatriculasPorAee(int $ano): Collection
    {
        $modalidadeLabels = [1 => 'Regular', 2 => 'Educação Especial (AEE)', 3 => 'EJA', 4 => 'Educação Profissional'];
        return cache()->remember(
            'bis.inclusao_aee.' . $ano,
            config('bis.cache_ttl', 300),
            function () use ($ano, $modalidadeLabels) {
                $dados = DB::table('pmieducar.matricula as m')
                    ->join('pmieducar.curso as c', 'm.ref_cod_curso', '=', 'c.cod_curso')
                    ->selectRaw('c.modalidade_curso, count(*) as total')
                    ->where('m.ano', $ano)
                    ->where('m.ativo', 1)
                    ->groupBy('c.modalidade_curso')
                    ->orderByDesc('total')
                    ->get();
                return $dados->map(fn ($r) => (object) [
                    'tipo' => $modalidadeLabels[$r->modalidade_curso] ?? 'Outro',
                    'total' => $r->total,
                ]);
            }
        ) ?: collect();
    }

    private function getBuscaAtiva(int $ano): array
    {
        $porResultado = $this->getBuscaAtivaPorResultado($ano);
        $evolucao = $this->getBuscaAtivaEvolucao($ano);
        $programaEvasao = $this->getBuscaAtivaProgramaEvasao($ano);
        $charts = [];

        if ($porResultado->isNotEmpty()) {
            $vals = $porResultado->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($porResultado->pluck('resultado')->toArray())
                ->dataset('Casos', 'pie', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Busca Ativa por Resultado (' . $ano . ')')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['por_resultado'] = $chart;
        }

        if ($evolucao->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($evolucao->pluck('periodo')->toArray())
                ->dataset('Casos', 'line', $evolucao->pluck('total')->toArray())
                ->backgroundColor('rgba(59, 130, 246, 0.2)')->color('#3B82F6');
            $chart->title('Evolução de Casos da Busca Ativa')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['evolucao'] = $chart;
        }

        if ($programaEvasao->isNotEmpty()) {
            $vals = $programaEvasao->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($programaEvasao->pluck('status')->toArray())
                ->dataset('Alunos', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Alunos no Programa de Evasão')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['programa_evasao'] = $chart;
        }

        $exportData = [];
        foreach ($porResultado as $r) {
            $exportData[] = ['categoria' => 'Resultado', 'item' => $r->resultado, 'total' => $r->total];
        }
        foreach ($evolucao as $r) {
            $exportData[] = ['categoria' => 'Evolução', 'periodo' => $r->periodo, 'total' => $r->total];
        }
        foreach ($programaEvasao as $r) {
            $exportData[] = ['categoria' => 'Programa Evasão', 'status' => $r->status, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['categoria' => '-', 'item' => '-', 'total' => 0]];
        }

        return [
            'charts' => $charts,
            'data' => [
                'porResultado' => $porResultado,
                'evolucao' => $evolucao,
                'programaEvasao' => $programaEvasao,
            ],
            'exportData' => $exportData,
        ];
    }

    /** resultado_busca_ativa: 1=Deixou de Frequentar, 2=Em andamento, 3=Retorno c/ AJ, 4=Retorno s/ AJ, 5=Transferência */
    private function getBuscaAtivaPorResultado(int $ano): Collection
    {
        $labels = [
            1 => 'Deixou de Frequentar',
            2 => 'Em andamento',
            3 => 'Retorno com ausência justificada',
            4 => 'Retorno sem ausência justificada',
            5 => 'Transferência',
        ];
        return cache()->remember(
            'bis.busca_ativa_resultado.' . $ano,
            config('bis.cache_ttl', 300),
            function () use ($ano, $labels) {
                $dados = DB::table('pmieducar.busca_ativa as ba')
                    ->join('pmieducar.matricula as m', 'ba.ref_cod_matricula', '=', 'm.cod_matricula')
                    ->selectRaw('ba.resultado_busca_ativa, count(*) as total')
                    ->where('m.ano', $ano)
                    ->whereNull('ba.deleted_at')
                    ->groupBy('ba.resultado_busca_ativa')
                    ->orderByDesc('total')
                    ->get();
                return $dados->map(fn ($r) => (object) [
                    'resultado' => $labels[$r->resultado_busca_ativa] ?? 'Outro',
                    'total' => $r->total,
                ]);
            }
        ) ?: collect();
    }

    private function getBuscaAtivaEvolucao(int $ano): Collection
    {
        return cache()->remember(
            'bis.busca_ativa_evolucao.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.busca_ativa as ba')
                ->join('pmieducar.matricula as m', 'ba.ref_cod_matricula', '=', 'm.cod_matricula')
                ->selectRaw("to_char(ba.created_at, 'YYYY-MM') as periodo, count(*) as total")
                ->where('m.ano', $ano)
                ->whereNull('ba.deleted_at')
                ->groupByRaw("to_char(ba.created_at, 'YYYY-MM')")
                ->orderBy('periodo')
                ->get()
        ) ?: collect();
    }

    private function getBuscaAtivaProgramaEvasao(int $ano): Collection
    {
        return cache()->remember(
            'bis.busca_ativa_programa.' . $ano,
            config('bis.cache_ttl', 300),
            function () use ($ano) {
                $sim = DB::table('pmieducar.busca_ativa as ba')
                    ->join('pmieducar.matricula as m', 'ba.ref_cod_matricula', '=', 'm.cod_matricula')
                    ->where('m.ano', $ano)
                    ->where('ba.aluno_incluso_programa_evasao', true)
                    ->whereNull('ba.deleted_at')
                    ->count();
                $nao = DB::table('pmieducar.busca_ativa as ba')
                    ->join('pmieducar.matricula as m', 'ba.ref_cod_matricula', '=', 'm.cod_matricula')
                    ->where('m.ano', $ano)
                    ->where(function ($q) {
                        $q->where('ba.aluno_incluso_programa_evasao', false)
                            ->orWhereNull('ba.aluno_incluso_programa_evasao');
                    })
                    ->whereNull('ba.deleted_at')
                    ->count();
                return collect([
                    (object) ['status' => 'Incluso no programa', 'total' => $sim],
                    (object) ['status' => 'Não incluso', 'total' => $nao],
                ])->filter(fn ($r) => $r->total > 0);
            }
        ) ?: collect();
    }

    private function getEducacenso(int $ano): array
    {
        $coberturaAlunos = $this->getEducacensoCoberturaAlunos($ano);
        $coberturaTurmas = $this->getEducacensoCoberturaTurmas($ano);
        $coberturaEscolas = $this->getEducacensoCoberturaEscolas();
        $registrosPorTipo = $this->getEducacensoRegistrosPorTipo($ano);
        $charts = [];

        if ($coberturaAlunos->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels($coberturaAlunos->pluck('ano')->toArray());
            $chart->dataset('Com INEP (%)', 'bar', $coberturaAlunos->pluck('pct_inep')->map(fn ($v) => round((float) $v, 1))->toArray())
                ->backgroundColor('#10B981')->color('#10B981');
            $chart->dataset('Sem INEP (%)', 'bar', $coberturaAlunos->pluck('pct_sem_inep')->map(fn ($v) => round((float) $v, 1))->toArray())
                ->backgroundColor('#F59E0B')->color('#F59E0B');
            $chart->title('Cobertura INEP - Alunos Matriculados')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['cobertura_alunos'] = $chart;
        }

        if ($coberturaTurmas->isNotEmpty()) {
            $vals = $coberturaTurmas->pluck('pct_inep')->map(fn ($v) => round((float) $v, 1))->toArray();
            $chart = new Chart();
            $chart->labels($coberturaTurmas->pluck('ano')->toArray());
            $chart->dataset('Turmas com INEP (%)', 'line', $vals)
                ->backgroundColor('rgba(59, 130, 246, 0.2)')->color('#3B82F6');
            $chart->title('Cobertura INEP - Turmas')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['cobertura_turmas'] = $chart;
        }

        if ($coberturaEscolas->isNotEmpty()) {
            $chart = new Chart();
            $chart->labels(['Escolas']);
            $chart->dataset('Com código INEP', 'bar', [$coberturaEscolas->first()->com_inep ?? 0])
                ->backgroundColor('#10B981')->color('#10B981');
            $chart->dataset('Sem código INEP', 'bar', [$coberturaEscolas->first()->sem_inep ?? 0])
                ->backgroundColor('#F59E0B')->color('#F59E0B');
            $chart->title('Cobertura INEP - Escolas')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['cobertura_escolas'] = $chart;
        }

        if ($registrosPorTipo->isNotEmpty()) {
            $vals = $registrosPorTipo->pluck('total')->toArray();
            $colors = array_slice(self::CHART_COLORS, 0, count($vals)) ?: [self::CHART_COLORS[0]];
            $chart = new Chart();
            $chart->labels($registrosPorTipo->pluck('tipo')->toArray());
            $chart->dataset('Registros', 'bar', $vals)->backgroundColor($colors)->color($colors);
            $chart->title('Registros Educacenso por Tipo (' . $ano . ')')->height(300)
                ->options(['legend' => ['display' => true, 'position' => 'bottom']]);
            $charts['registros_tipo'] = $chart;
        }

        $exportData = [];
        foreach ($coberturaAlunos as $r) {
            $exportData[] = ['ano' => $r->ano, 'com_inep' => $r->com_inep, 'sem_inep' => $r->sem_inep, 'pct_inep' => round((float) $r->pct_inep, 1)];
        }
        foreach ($coberturaTurmas as $r) {
            $exportData[] = ['ano' => $r->ano, 'com_inep' => $r->com_inep, 'sem_inep' => $r->sem_inep, 'pct_inep' => round((float) $r->pct_inep, 1)];
        }
        foreach ($coberturaEscolas as $r) {
            $exportData[] = ['tipo' => 'Escolas', 'com_inep' => $r->com_inep ?? 0, 'sem_inep' => $r->sem_inep ?? 0];
        }
        foreach ($registrosPorTipo as $r) {
            $exportData[] = ['tipo' => $r->tipo, 'total' => $r->total];
        }
        if (empty($exportData)) {
            $exportData = [['ano' => '-', 'com_inep' => 0, 'sem_inep' => 0]];
        }

        $chartDescriptions = [
            'cobertura_alunos' => [
                'titulo' => 'Cobertura INEP - Alunos Matriculados',
                'descricao' => 'Percentual de matrículas ativas que possuem código INEP vinculado ao aluno. O código INEP é obrigatório para o envio do Censo Escolar.',
                'calculo' => 'Com INEP = alunos com cod_aluno_inep > 0 em educacenso_cod_aluno. Sem INEP = alunos sem registro ou com código zero.',
            ],
            'cobertura_turmas' => [
                'titulo' => 'Cobertura INEP - Turmas',
                'descricao' => 'Percentual de turmas que possuem código INEP cadastrado. A cobertura adequada garante a homologação dos dados no Educacenso.',
                'calculo' => 'Com INEP = turmas com cod_turma_inep > 0 em educacenso_cod_turma. Sem INEP = turmas sem registro ou com código zero.',
            ],
            'cobertura_escolas' => [
                'titulo' => 'Cobertura INEP - Escolas',
                'descricao' => 'Quantidade de escolas com e sem código INEP. Todas as escolas devem ter o código da unidade cadastrado para o Censo Escolar.',
                'calculo' => 'Com código INEP = escolas com cod_escola_inep > 0 em educacenso_cod_escola. Sem código = escolas sem registro ou com código zero.',
            ],
            'registros_tipo' => [
                'titulo' => 'Registros Educacenso por Tipo',
                'descricao' => 'Quantidade de registros exportados por tipo no formato do Censo Escolar (escola, turma, docente, aluno, etc.). Útil para conferência antes do envio.',
                'calculo' => 'Contagem de registros nas views/tabelas de exportação do Educacenso por tipo de registro para o ano selecionado.',
            ],
        ];

        return [
            'charts' => $charts,
            'chartDescriptions' => $chartDescriptions,
            'data' => [
                'coberturaAlunos' => $coberturaAlunos,
                'coberturaTurmas' => $coberturaTurmas,
                'coberturaEscolas' => $coberturaEscolas,
                'registrosPorTipo' => $registrosPorTipo,
            ],
            'exportData' => $exportData,
        ];
    }

    private function getEducacensoCoberturaAlunos(int $ano): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.educacenso_cobertura_alunos',
            config('bis.cache_ttl', 300),
            function () use ($anoAtual) {
                return DB::table('pmieducar.matricula as m')
                    ->join('pmieducar.aluno as a', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
                    ->leftJoin('modules.educacenso_cod_aluno as ea', 'a.cod_aluno', '=', 'ea.cod_aluno')
                    ->selectRaw("m.ano::text as ano,
                        count(DISTINCT CASE WHEN ea.cod_aluno_inep IS NOT NULL AND ea.cod_aluno_inep > 0 THEN m.ref_cod_aluno END) as com_inep,
                        count(DISTINCT CASE WHEN ea.cod_aluno_inep IS NULL OR ea.cod_aluno_inep = 0 THEN m.ref_cod_aluno END) as sem_inep,
                        count(DISTINCT m.ref_cod_aluno) as total,
                        count(DISTINCT CASE WHEN ea.cod_aluno_inep IS NOT NULL AND ea.cod_aluno_inep > 0 THEN m.ref_cod_aluno END) * 100.0 / nullif(count(DISTINCT m.ref_cod_aluno), 0) as pct_inep,
                        count(DISTINCT CASE WHEN ea.cod_aluno_inep IS NULL OR ea.cod_aluno_inep = 0 THEN m.ref_cod_aluno END) * 100.0 / nullif(count(DISTINCT m.ref_cod_aluno), 0) as pct_sem_inep")
                    ->where('m.ativo', 1)
                    ->whereBetween('m.ano', [$anoAtual - 4, $anoAtual])
                    ->groupBy('m.ano')
                    ->orderBy('m.ano')
                    ->get();
            }
        ) ?: collect();
    }

    private function getEducacensoCoberturaTurmas(int $ano): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.educacenso_cobertura_turmas',
            config('bis.cache_ttl', 300),
            function () use ($anoAtual) {
                return DB::table('pmieducar.turma as t')
                    ->leftJoin('modules.educacenso_cod_turma as et', 't.cod_turma', '=', 'et.cod_turma')
                    ->selectRaw("t.ano::text as ano,
                        count(CASE WHEN et.cod_turma_inep IS NOT NULL AND et.cod_turma_inep > 0 THEN t.cod_turma END) as com_inep,
                        count(CASE WHEN et.cod_turma_inep IS NULL OR et.cod_turma_inep = 0 THEN t.cod_turma END) as sem_inep,
                        count(*) as total,
                        count(CASE WHEN et.cod_turma_inep IS NOT NULL AND et.cod_turma_inep > 0 THEN t.cod_turma END) * 100.0 / nullif(count(*), 0) as pct_inep")
                    ->where('t.ativo', 1)
                    ->whereBetween('t.ano', [$anoAtual - 4, $anoAtual])
                    ->groupBy('t.ano')
                    ->orderBy('t.ano')
                    ->get();
            }
        ) ?: collect();
    }

    private function getEducacensoCoberturaEscolas(): Collection
    {
        return cache()->remember(
            'bis.educacenso_cobertura_escolas',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.escola as e')
                ->leftJoin('modules.educacenso_cod_escola as ee', 'e.cod_escola', '=', 'ee.cod_escola')
                ->selectRaw("count(CASE WHEN ee.cod_escola_inep IS NOT NULL AND ee.cod_escola_inep > 0 THEN e.cod_escola END) as com_inep,
                    count(CASE WHEN ee.cod_escola_inep IS NULL OR ee.cod_escola_inep = 0 THEN e.cod_escola END) as sem_inep")
                ->where('e.ativo', 1)
                ->get()
        ) ?: collect();
    }

    private function getEducacensoRegistrosPorTipo(int $ano): Collection
    {
        return cache()->remember(
            'bis.educacenso_registros.' . $ano,
            config('bis.cache_ttl', 300),
            function () use ($ano) {
                $result = collect();
                $queries = [
                    'Registro 20 (Turma)' => "SELECT count(*) as total FROM public.educacenso_record20 WHERE \"anoTurma\" = {$ano}",
                    'Registro 40 (Gestores)' => 'SELECT count(*) as total FROM public.educacenso_record40',
                    'Registro 50 (Docentes)' => "SELECT count(*) as total FROM public.educacenso_record50 WHERE \"anoTurma\" = {$ano}",
                    'Registro 60 (Alunos)' => "SELECT count(*) as total FROM public.educacenso_record60 WHERE \"anoTurma\" = {$ano}",
                ];
                foreach ($queries as $label => $sql) {
                    try {
                        $row = DB::selectOne($sql);
                        $total = (int) ($row->total ?? 0);
                        $result->push((object) ['tipo' => $label, 'total' => $total]);
                    } catch (\Throwable $e) {
                        $result->push((object) ['tipo' => $label, 'total' => 0]);
                    }
                }
                return $result;
            }
        ) ?: collect();
    }

    private function getMatriculasPorCurso(int $ano): Collection
    {
        return cache()->remember(
            'bis.matriculas_por_curso.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula as m')
                ->join('pmieducar.curso as c', 'm.ref_cod_curso', '=', 'c.cod_curso')
                ->selectRaw('c.nm_curso as curso, count(*) as total')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->groupBy('c.nm_curso')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
        );
    }

    private function getMatriculasPorAno(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.matriculas_por_ano',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula')
                ->selectRaw('ano::text as ano, count(*) as total')
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    private function getTurmasPorEscola(int $ano): Collection
    {
        return cache()->remember(
            'bis.turmas_por_escola.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.turma as t')
                ->join('pmieducar.escola as e', 't.ref_ref_cod_escola', '=', 'e.cod_escola')
                ->selectRaw('e.nome as escola, count(*) as total')
                ->where('t.ano', $ano)
                ->where('t.ativo', 1)
                ->groupBy('e.nome')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
        );
    }

    private function getTurmasPorAno(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.turmas_por_ano',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.turma')
                ->selectRaw('ano::text as ano, count(*) as total')
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    private function getMediaNotasPorEtapa(int $ano): Collection
    {
        return cache()->remember(
            'bis.media_notas_etapa.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('modules.nota_componente_curricular as ncc')
                ->join('modules.nota_aluno as na', 'ncc.nota_aluno_id', '=', 'na.id')
                ->join('pmieducar.matricula as m', 'na.matricula_id', '=', 'm.cod_matricula')
                ->selectRaw('ncc.etapa as etapa, round(avg(ncc.nota::numeric), 2) as media')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->whereRaw("ncc.nota ~ '^[0-9]+\.?[0-9]*$'")
                ->groupBy('ncc.etapa')
                ->orderBy('ncc.etapa')
                ->get()
        );
    }

    private function getFaltasPorEtapa(int $ano): Collection
    {
        return cache()->remember(
            'bis.faltas_por_etapa.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('modules.falta_geral as fg')
                ->join('modules.falta_aluno as fa', 'fg.falta_aluno_id', '=', 'fa.id')
                ->join('pmieducar.matricula as m', 'fa.matricula_id', '=', 'm.cod_matricula')
                ->selectRaw('fg.etapa as etapa, sum(fg.quantidade) as total')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->groupBy('fg.etapa')
                ->orderBy('fg.etapa')
                ->get()
        ) ?: collect();
    }

    private function getIndicadorEvasao(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.evasao',
            config('bis.cache_ttl', 300),
            function () use ($anoAtual) {
                $dados = DB::table('pmieducar.matricula')
                    ->selectRaw('ano::text as ano, count(*) filter (where aprovado = 6) * 100.0 / nullif(count(*), 0) as taxa')
                    ->where('ativo', 1)
                    ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                    ->groupBy('ano')
                    ->orderBy('ano')
                    ->get();
                return $dados;
            }
        );
    }

    private function getIndicadorAprovacao(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.aprovacao',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula')
                ->selectRaw("ano::text as ano, count(*) filter (where aprovado in (1, 2, 8)) * 100.0 / nullif(count(*), 0) as taxa")
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    /** Taxa % de matrículas reprovadas (retido + reprovado por faltas) - aprovado 2, 14 */
    private function getIndicadorReprovacao(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.reprovacao',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula')
                ->selectRaw('ano::text as ano, count(*) filter (where aprovado in (2, 14)) * 100.0 / nullif(count(*), 0) as taxa')
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    /** Taxa % de matrículas reclassificadas - aprovado 5 */
    private function getIndicadorReclassificacao(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.reclassificacao',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula')
                ->selectRaw('ano::text as ano, count(*) filter (where aprovado = 5) * 100.0 / nullif(count(*), 0) as taxa')
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    /** Taxa % de matrículas em abandono - aprovado 6 */
    private function getIndicadorAbandono(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.abandono',
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula')
                ->selectRaw('ano::text as ano, count(*) filter (where aprovado = 6) * 100.0 / nullif(count(*), 0) as taxa')
                ->where('ativo', 1)
                ->whereBetween('ano', [$anoAtual - 4, $anoAtual])
                ->groupBy('ano')
                ->orderBy('ano')
                ->get()
        );
    }

    /** Taxa % de alunos em distorção idade-série (idade > esperada para a série) */
    private function getIndicadorDistorcaoIdadeSerie(): Collection
    {
        $anoAtual = now()->year;
        return cache()->remember(
            'bis.distorcao_idade_serie',
            config('bis.cache_ttl', 300),
            function () use ($anoAtual) {
                $dados = DB::table('pmieducar.matricula as m')
                    ->join('pmieducar.aluno as a', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
                    ->join('cadastro.fisica as f', 'a.ref_idpes', '=', 'f.idpes')
                    ->leftJoin('pmieducar.serie as s', 'm.ref_ref_cod_serie', '=', 's.cod_serie')
                    ->where('m.ativo', 1)
                    ->whereNotNull('f.data_nasc')
                    ->whereBetween('m.ano', [$anoAtual - 4, $anoAtual])
                    ->selectRaw("
                        m.ano::text as ano,
                        count(*) filter (where
                            extract(year from age(make_date(m.ano::int, 3, 1), f.data_nasc))::int
                            > COALESCE(NULLIF(s.idade_final, 0), s.idade_ideal, 99)
                        ) * 100.0 / nullif(count(*), 0) as taxa
                    ")
                    ->groupBy('m.ano')
                    ->orderBy('m.ano')
                    ->get();
                return $dados;
            }
        );
    }

    /** Contagem de benefícios utilizados por alunos matriculados no ano */
    private function getIndicadorBeneficios(int $ano): Collection
    {
        return cache()->remember(
            'bis.beneficios.' . $ano,
            config('bis.cache_ttl', 300),
            fn () => DB::table('pmieducar.matricula as m')
                ->join('pmieducar.aluno_aluno_beneficio as aab', 'm.ref_cod_aluno', '=', 'aab.aluno_id')
                ->join('pmieducar.aluno_beneficio as ab', 'aab.aluno_beneficio_id', '=', 'ab.cod_aluno_beneficio')
                ->where('m.ano', $ano)
                ->where('m.ativo', 1)
                ->where('ab.ativo', 1)
                ->selectRaw('ab.nm_beneficio as beneficio, count(DISTINCT m.ref_cod_aluno) as total')
                ->groupBy('ab.nm_beneficio')
                ->orderByDesc('total')
                ->limit(12)
                ->get()
        );
    }

    /** Contagem de uniformes e materiais distribuídos no ano */
    private function getIndicadorUniformes(int $ano): Collection
    {
        return cache()->remember(
            'bis.uniformes.' . $ano,
            config('bis.cache_ttl', 300),
            function () use ($ano) {
                $qtyCols = [
                    'complete_kit' => 'Kits completos',
                    'coat_pants_qty' => 'Agasalhos',
                    'shirt_short_qty' => 'Camisetas manga curta',
                    'shirt_long_qty' => 'Camisetas manga longa',
                    'socks_qty' => 'Meias',
                    'shorts_tactel_qty' => 'Bermudas',
                    'shorts_coton_qty' => 'Bermudas femininas',
                    'sneakers_qty' => 'Tênis',
                ];

                $selectParts = [];
                foreach (array_keys($qtyCols) as $col) {
                    if ($col === 'complete_kit') {
                        $selectParts[] = "sum(CASE WHEN complete_kit = true THEN 1 ELSE 0 END) as complete_kit";
                    } else {
                        $selectParts[] = "sum(COALESCE(\"{$col}\", 0)::int) as {$col}";
                    }
                }

                $row = DB::table('uniform_distributions')
                    ->where('year', $ano)
                    ->selectRaw(implode(', ', $selectParts))
                    ->first();

                if (!$row) {
                    return collect();
                }

                $result = collect();
                foreach ($qtyCols as $col => $label) {
                    $total = (int) ($row->{$col} ?? 0);
                    if ($total > 0) {
                        $result->push((object) ['categoria' => $label, 'total' => $total]);
                    }
                }

                $countDist = DB::table('uniform_distributions')->where('year', $ano)->count();
                if ($countDist > 0 && $result->isEmpty()) {
                    $result->push((object) ['categoria' => 'Distribuições realizadas', 'total' => $countDist]);
                }

                return $result->sortByDesc('total')->values();
            }
        );
    }
}
