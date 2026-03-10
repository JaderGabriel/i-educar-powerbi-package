@extends('layout.default')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bi-primary: #2563eb;
            --bi-primary-light: #3b82f6;
            --bi-secondary: #0ea5e9;
            --bi-success: #10b981;
            --bi-warning: #f59e0b;
            --bi-surface: #f8fafc;
            --bi-card-shadow: 0 1px 3px rgba(0,0,0,0.08);
            --bi-card-shadow-hover: 0 4px 12px rgba(0,0,0,0.12);
            --bi-radius: 12px;
            --bi-radius-sm: 8px;
        }
        .bi-dashboard {
            font-family: 'DM Sans', 'Open Sans', sans-serif;
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 16px;
        }
        .bi-dashboard-header {
            margin-bottom: 32px;
        }
        .bi-dashboard-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 6px 0;
        }
        .bi-dashboard-subtitle {
            font-size: 14px;
            color: #64748b;
        }
        .bi-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .bi-kpi-card {
            background: #fff;
            border-radius: var(--bi-radius);
            padding: 20px;
            box-shadow: var(--bi-card-shadow);
            transition: box-shadow 0.2s, transform 0.2s;
            border: 1px solid #e2e8f0;
        }
        .bi-kpi-card:hover {
            box-shadow: var(--bi-card-shadow-hover);
            transform: translateY(-2px);
        }
        .bi-kpi-card-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .bi-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--bi-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .bi-kpi-icon.matriculas { background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); color: var(--bi-primary); }
        .bi-kpi-icon.turmas { background: linear-gradient(135deg, #ccfbf1 0%, #5eead4 100%); color: #0d9488; }
        .bi-kpi-icon.escolas { background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); color: #b45309; }
        .bi-kpi-icon.cursos { background: linear-gradient(135deg, #e9d5ff 0%, #c084fc 100%); color: #7c3aed; }
        .bi-kpi-value {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
        }
        .bi-kpi-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .bi-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 16px 0;
        }
        .bi-modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .bi-module-card {
            background: #fff;
            border-radius: var(--bi-radius);
            padding: 24px;
            box-shadow: var(--bi-card-shadow);
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.2s;
        }
        .bi-module-card:hover {
            box-shadow: var(--bi-card-shadow-hover);
            border-color: var(--bi-primary-light);
            color: inherit;
        }
        .bi-module-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--bi-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }
        .bi-module-icon.matriculas { background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); color: var(--bi-primary); }
        .bi-module-icon.turmas { background: linear-gradient(135deg, #ccfbf1 0%, #5eead4 100%); color: #0d9488; }
        .bi-module-icon.lancamentos { background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); color: #b45309; }
        .bi-module-icon.indicadores { background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%); color: #b45309; }
        .bi-module-icon.inclusao { background: linear-gradient(135deg, #a5f3fc 0%, #22d3ee 100%); color: #0e7490; }
        .bi-module-icon.busca-ativa { background: linear-gradient(135deg, #ffedd5 0%, #fdba74 100%); color: #c2410c; }
        .bi-module-icon.educacenso { background: linear-gradient(135deg, #e0e7ff 0%, #a5b4fc 100%); color: #4338ca; }
        .bi-module-card h3 { font-size: 16px; font-weight: 600; margin: 0 0 6px 0; color: #1e293b; }
        .bi-module-card p { font-size: 13px; color: #64748b; margin: 0; }
        .bi-charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .bi-chart-card {
            background: #fff;
            border-radius: var(--bi-radius);
            padding: 20px;
            box-shadow: var(--bi-card-shadow);
            border: 1px solid #e2e8f0;
        }
        .bi-chart-card-title { font-size: 15px; font-weight: 600; margin: 0 0 16px 0; color: #334155; }
        .bi-chart-container { min-height: 250px; }
        .bi-year-switch { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 16px; }
        .bi-year-btn { padding: 6px 14px; border-radius: var(--bi-radius-sm); border: 1px solid #e2e8f0; background: #fff; color: #64748b; text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .bi-year-btn:hover { border-color: var(--bi-primary-light); color: var(--bi-primary); }
        .bi-year-btn.active { background: var(--bi-primary); border-color: var(--bi-primary); color: #fff; }
        .bi-powered { font-size: 12px; color: #64748b; margin-top: 8px; }
        .bi-powered-link { color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .bi-powered-link:hover { color: var(--bi-primary); }
        @media print {
            .bi-modules-grid, .bi-no-print { display: none !important; }
        }
    </style>
@endpush

@section('content')
<div class="bi-dashboard">
    <div class="bi-dashboard-header">
        <h1 class="bi-dashboard-title">Business Intelligence</h1>
        <p class="bi-dashboard-subtitle">Visão consolidada dos dados escolares · Ano {{ $anoSelecionado ?? $summary['anoAtual'] ?? now()->year }}</p>
        <x-bi-powered />
    </div>

    <div class="bi-year-switch bi-no-print">
        <span style="font-size: 13px; color: #64748b;">Ano letivo:</span>
        @foreach($anosLetivos ?? [] as $a)
            <a href="{{ route('bis.dashboard', ['ano' => $a->id]) }}" class="bi-year-btn {{ ($anoSelecionado ?? $summary['anoAtual']) == $a->id ? 'active' : '' }}">{{ $a->nome }}</a>
        @endforeach
    </div>

    @php
        $ano = $anoSelecionado ?? $summary['anoAtual'] ?? now()->year;
        $urlMatriculas = route('bis.matriculas.index', ['ano' => $ano]);
        $urlTurmas = route('bis.turmas.index', ['ano' => $ano]);
    @endphp
    <div class="bi-kpi-grid">
        <a href="{{ $urlMatriculas }}" class="bi-kpi-card bi-kpi-card-link" title="Ver análise de matrículas">
            <div class="bi-kpi-icon matriculas"><i class="fa fa-user-plus"></i></div>
            <div class="bi-kpi-value">{{ number_format($summary['matriculasAtivas'] ?? 0, 0, ',', '.') }}</div>
            <div class="bi-kpi-label">Matrículas ativas</div>
        </a>
        <a href="{{ $urlTurmas }}" class="bi-kpi-card bi-kpi-card-link" title="Ver análise de turmas">
            <div class="bi-kpi-icon turmas"><i class="fa fa-object-group"></i></div>
            <div class="bi-kpi-value">{{ number_format($summary['totalTurmas'] ?? 0, 0, ',', '.') }}</div>
            <div class="bi-kpi-label">Turmas</div>
        </a>
        <a href="{{ $urlTurmas }}" class="bi-kpi-card bi-kpi-card-link" title="Ver turmas por escola">
            <div class="bi-kpi-icon escolas"><i class="fa fa-building"></i></div>
            <div class="bi-kpi-value">{{ number_format($summary['totalEscolas'] ?? 0, 0, ',', '.') }}</div>
            <div class="bi-kpi-label">Escolas</div>
        </a>
        <a href="{{ $urlMatriculas }}" class="bi-kpi-card bi-kpi-card-link" title="Ver matrículas por curso">
            <div class="bi-kpi-icon cursos"><i class="fa fa-book"></i></div>
            <div class="bi-kpi-value">{{ number_format($summary['totalCursos'] ?? 0, 0, ',', '.') }}</div>
            <div class="bi-kpi-label">Cursos</div>
        </a>
    </div>

    <h2 class="bi-section-title">Módulos de análise</h2>
    <div class="bi-modules-grid bi-no-print">
        @php
            $modules = [
                ['title' => 'Matrículas', 'url' => route('bis.matriculas.index'), 'icon' => 'fa-users', 'slug' => 'matriculas', 'desc' => 'Análise de matrículas por curso, escola, situação e mais'],
                ['title' => 'Turmas', 'url' => route('bis.turmas.index'), 'icon' => 'fa-object-group', 'slug' => 'turmas', 'desc' => 'Distribuição de turmas por escola, curso e turno'],
                ['title' => 'Lançamentos', 'url' => route('bis.theme', ['theme' => 'lancamentos']), 'icon' => 'fa-edit', 'slug' => 'lancamentos', 'desc' => 'Notas e faltas por etapa'],
                ['title' => 'Indicadores', 'url' => route('bis.theme', ['theme' => 'indicadores']), 'icon' => 'fa-line-chart', 'slug' => 'indicadores', 'desc' => 'Evasão, aprovação, reprovação e indicadores educacionais'],
                ['title' => 'Inclusão e Diversidade', 'url' => route('bis.theme', ['theme' => 'inclusao-diversidade']), 'icon' => 'fa-users', 'slug' => 'inclusao', 'desc' => 'Matrículas por deficiência, cor/raça, gênero e AEE'],
                ['title' => 'Busca Ativa', 'url' => route('bis.theme', ['theme' => 'busca-ativa']), 'icon' => 'fa-search', 'slug' => 'busca-ativa', 'desc' => 'Casos de evasão, resultado e programa de evasão'],
                ['title' => 'Educacenso/INEP', 'url' => route('bis.theme', ['theme' => 'educacenso']), 'icon' => 'fa-database', 'slug' => 'educacenso', 'desc' => 'Cobertura INEP e registros do Censo Escolar'],
            ];
        @endphp
        @foreach($modules as $m)
            <a href="{{ $m['url'] }}" class="bi-module-card">
                <div class="bi-module-icon {{ $m['slug'] }}"><i class="fa {{ $m['icon'] }}"></i></div>
                <h3>{{ $m['title'] }}</h3>
                <p>{{ $m['desc'] }}</p>
            </a>
        @endforeach
    </div>

    <h2 class="bi-section-title">Resumo gráfico</h2>
    <div class="bi-charts-row">
        @if(!empty($summary['matriculasPorCurso']) && $summary['matriculasPorCurso']->isNotEmpty())
        <div class="bi-chart-card">
            <h3 class="bi-chart-card-title">Matrículas por Segmento ({{ $summary['anoAtual'] }})</h3>
            <div class="bi-chart-container">
                {!! $chartMatriculasCurso ?? '' !!}
            </div>
        </div>
        @endif
        @if(!empty($summary['turmasPorEscola']) && $summary['turmasPorEscola']->isNotEmpty())
        <div class="bi-chart-card" data-chart="turmasEscola">
            <h3 class="bi-chart-card-title">Turmas por Escola ({{ $summary['anoAtual'] }})</h3>
            <div class="bi-chart-container">
                {!! $chartTurmasEscola ?? '' !!}
            </div>
        </div>
        @endif
        @if(!empty($summary['evolucaoAnual']) && $summary['evolucaoAnual']->isNotEmpty())
        <div class="bi-chart-card">
            <h3 class="bi-chart-card-title">Evolução de Matrículas (últimos 5 anos)</h3>
            <div class="bi-chart-container">
                {!! $chartEvolucao ?? '' !!}
            </div>
        </div>
        @endif
    </div>

    @if(empty($summary['matriculasPorCurso']) && empty($summary['turmasPorEscola']) && empty($summary['evolucaoAnual']))
    <div class="alert alert-info" style="margin-top: 24px;">
        Nenhum dado disponível para exibir no momento. Realize matrículas e cadastre turmas para visualizar os gráficos.
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" crossorigin="anonymous"></script>
@if(!empty($chartMatriculasCurso)){!! $chartMatriculasCursoScript ?? '' !!}@endif
@if(!empty($chartTurmasEscola))
<script>window.bisTurmasEscolaTooltips = @json($turmasEscolaTooltips ?? []);</script>
{!! $chartTurmasEscolaScript ?? '' !!}
<script>
(function(){
    var card = document.querySelector('[data-chart="turmasEscola"]');
    if (!card || !window.bisTurmasEscolaTooltips || !window.bisTurmasEscolaTooltips.length) return;
    var canvas = card.querySelector('canvas');
    if (!canvas) return;
    var chart = Chart.instances[canvas.id] || Object.values(Chart.instances || {}).find(function(c){ return c.canvas && c.canvas.id === canvas.id; });
    if (!chart) return;
    var tooltips = window.bisTurmasEscolaTooltips;
    chart.options.tooltips = chart.options.tooltips || {};
    chart.options.tooltips.callbacks = chart.options.tooltips.callbacks || {};
    chart.options.tooltips.callbacks.label = function(item, data) {
        if (!tooltips[item.index]) return '';
        var lines = tooltips[item.index].split('\n');
        return lines.length >= 2 ? [lines[0], lines[1]] : lines;
    };
    chart.options.tooltips.callbacks.afterBody = function(tooltipItems) {
        if (!tooltipItems.length || !tooltips[tooltipItems[0].index]) return [];
        var lines = tooltips[tooltipItems[0].index].split('\n');
        return lines.length >= 3 ? [lines[2]] : [];
    };
})();
</script>
@endif
@if(!empty($chartEvolucao)){!! $chartEvolucaoScript ?? '' !!}@endif
@endpush
