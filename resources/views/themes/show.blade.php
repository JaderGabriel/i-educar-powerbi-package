@extends('layout.default')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    <style>
        @media print {
            .bi-print-wrapper .bi-section { display: none !important; }
            .bi-print-wrapper .bi-section.bi-print-active { display: block !important; }
            .bi-section.bi-print-active .bi-print-section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
            .bi-kpi-cards { display: none !important; }
        }
        .bi-kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .bi-kpi-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 14px 16px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .bi-kpi-card .bi-kpi-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .bi-kpi-card .bi-kpi-value { font-size: 22px; font-weight: 600; color: #333; }
        .bi-kpi-card .bi-kpi-suffix { font-size: 14px; color: #6c757d; }
    </style>
@endpush

@section('content')
    <x-bi-print-wrapper :title="$title" :export-url="$exportUrl ?? null">
        <div class="bi-powered-wrapper" style="margin-bottom: 16px;">
            <x-bi-powered />
        </div>

        <form id="formcadastro" method="get" action="{{ route('bis.theme', ['theme' => $theme]) }}" class="bi-no-print">
            <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" role="presentation">
                <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24"><b>Filtros</b></td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Ano letivo</span> <span class="campo_obrigatorio">*</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="ano" id="ano" class="geral obrigatorio" style="width: 150px;" required>
                            <option value="">Selecione um ano</option>
                            @foreach($anosLetivos ?? [] as $a)
                                <option value="{{ $a->id }}" {{ ($anoSelecionado ?? now()->year) == $a->id ? 'selected' : '' }}>{{ $a->nome }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="separator"></div>
            <div style="text-align: center">
                <button class="btn-green" type="submit"><i class="fa fa-search"></i> Filtrar</button>
                <a href="{{ route('bis.theme', ['theme' => $theme]) }}" class="btn btn-default" style="margin-left: 8px;"><i class="fa fa-eraser"></i> Limpar filtros</a>
            </div>
        </form>

        @if($theme === 'indicadores' && !empty($data))
            @php
                $anoSel = $anoSelecionado ?? now()->year;
                $evasaoVal = collect($data['evasao'] ?? [])->firstWhere('ano', (string)$anoSel)?->taxa ?? 0;
                $reprovVal = collect($data['reprovacao'] ?? [])->firstWhere('ano', (string)$anoSel)?->taxa ?? 0;
                $reclassVal = collect($data['reclassificacao'] ?? [])->firstWhere('ano', (string)$anoSel)?->taxa ?? 0;
                $abandVal = collect($data['abandono'] ?? [])->firstWhere('ano', (string)$anoSel)?->taxa ?? 0;
            @endphp
            @if($evasaoVal > 0 || $reprovVal > 0 || $reclassVal > 0 || $abandVal > 0)
            <div class="bi-kpi-cards bi-no-print">
                <div class="bi-kpi-card"><div class="bi-kpi-label">Evasão</div><div class="bi-kpi-value">{{ number_format($evasaoVal, 1, ',', '.') }}<span class="bi-kpi-suffix">%</span></div></div>
                <div class="bi-kpi-card"><div class="bi-kpi-label">Reprovação</div><div class="bi-kpi-value">{{ number_format($reprovVal, 1, ',', '.') }}<span class="bi-kpi-suffix">%</span></div></div>
                <div class="bi-kpi-card"><div class="bi-kpi-label">Reclassificação</div><div class="bi-kpi-value">{{ number_format($reclassVal, 1, ',', '.') }}<span class="bi-kpi-suffix">%</span></div></div>
                <div class="bi-kpi-card"><div class="bi-kpi-label">Abandono</div><div class="bi-kpi-value">{{ number_format($abandVal, 1, ',', '.') }}<span class="bi-kpi-suffix">%</span></div></div>
            </div>
            @endif
        @endif

        @if(!empty($charts))
            @foreach($charts as $key => $chart)
                <div class="separator bi-no-print"></div>
                <div class="bi-section" data-section="{{ $key }}">
                    <table class="table-default" width="100%">
                        <tr>
                            <td class="titulo-tabela-listagem bi-print-section-title" style="text-align: left;">{{ $chartTitles[$key] ?? $key }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 15px;">
                                <div style="min-height: 300px;">
                                    {!! $chart->container() !!}
                                </div>
                                @if(in_array($theme, ['indicadores', 'educacenso']) && !empty($chartDescriptions[$key]))
                                <div class="bi-chart-description" style="margin-top: 16px; padding: 14px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6; font-size: 13px; color: #475569;">
                                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b;">{{ $chartDescriptions[$key]['titulo'] ?? '' }}</p>
                                    <p style="margin: 0 0 6px 0;">{{ $chartDescriptions[$key]['descricao'] ?? '' }}</p>
                                    <p style="margin: 0; font-size: 12px; color: #64748b;"><strong>Como é calculado:</strong> {{ $chartDescriptions[$key]['calculo'] ?? '' }}</p>
                                </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
            @foreach($charts ?? [] as $chart)
                {!! $chart->script() !!}
            @endforeach
        @else
            <div class="separator bi-no-print"></div>
            <div class="alert alert-info">
                <p class="mb-0">Nenhum dado disponível para exibir no momento.
                    @switch($theme)
                        @case('lancamentos') Realize lançamentos de notas e faltas para visualizar os gráficos. @break
                        @case('inclusao-diversidade') Cadastre matrículas com dados de deficiência, cor/raça e gênero. @break
                        @case('busca-ativa') Registre casos de Busca Ativa para acompanhar evasão. @break
                        @case('educacenso') Configure códigos INEP de escolas, turmas e alunos para o Educacenso. @break
                        @default Cadastre matrículas e situações para visualizar os gráficos.
                    @endswitch
                </p>
            </div>
        @endif
    </x-bi-print-wrapper>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" crossorigin="anonymous"></script>
@endpush
