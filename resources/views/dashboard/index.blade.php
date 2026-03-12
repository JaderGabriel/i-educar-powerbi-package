@extends('layout.default')

@push('styles')
    {{-- Fonte principal do dashboard --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">

    {{-- Estilos do dashboard BI publicados pelo pacote --}}
    <link rel="stylesheet" href="{{ asset('css/bi-dashboard.css') }}">

    {{-- CSS global do i-Educar (opcional, quando disponível) --}}
    @if (class_exists('Asset'))
        <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    @endif
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
