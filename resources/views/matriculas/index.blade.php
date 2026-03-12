@extends('layout.default')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    <style>
        @media print {
            .bi-print-wrapper .bi-section { display: none !important; }
            .bi-print-wrapper .bi-section.bi-print-active { display: block !important; }
            .bi-section.bi-print-active .bi-print-section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        }
    </style>
@endpush

@section('content')
    <x-bi-print-wrapper :title="$title">
        <div class="bi-powered-wrapper" style="margin-bottom: 16px;">
            <x-bi-powered />
        </div>
        <form id="formcadastro" method="get" action="{{ route('bis.matriculas.index') }}" class="bi-no-print">
            @if(!empty($singleInstitution) && ($instituicoes ?? collect())->isNotEmpty())
            <input type="hidden" name="ref_cod_instituicao" id="ref_cod_instituicao" value="{{ $instituicoes->first()->cod_instituicao }}">
            @endif
            <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" role="presentation">
                <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24"><b>Filtros</b></td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Ano</span> <span class="campo_obrigatorio">*</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="ano" id="ano" class="geral obrigatorio" style="width: 150px;" required>
                            <option value="">Selecione um ano</option>
                            <option value="todos" {{ ($filters['ano'] ?? null) === null ? 'selected' : '' }}>Todos os anos</option>
                            @foreach($anosLetivos ?? [] as $a)
                                <option value="{{ $a->id }}" {{ ($filters['ano'] ?? null) == $a->id ? 'selected' : '' }}>{{ $a->nome }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @if(empty($singleInstitution))
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Instituição</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="ref_cod_instituicao" id="ref_cod_instituicao" class="geral" style="width: 300px;">
                            <option value="">Todas</option>
                            @foreach($instituicoes ?? [] as $i)
                                <option value="{{ $i->cod_instituicao }}" {{ ($filters['instituicao'] ?? null) == $i->cod_instituicao ? 'selected' : '' }}>{{ $i->name ?? $i->nm_instituicao ?? '-' }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Escola</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="ref_cod_escola" id="ref_cod_escola" class="geral" style="width: 300px;">
                            <option value="">Todas</option>
                            @foreach($escolas ?? [] as $e)
                                <option value="{{ $e->id }}" {{ ($filters['escola'] ?? null) == $e->id ? 'selected' : '' }}>{{ $e->nome }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Curso</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="ref_cod_curso" id="ref_cod_curso" class="geral" style="width: 300px;">
                            <option value="">Todos</option>
                            @foreach($cursos ?? [] as $c)
                                <option value="{{ $c->id }}" {{ ($filters['curso'] ?? null) == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Situação de enturmação</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="enturmacao" id="enturmacao" class="geral" style="width: 200px;">
                            <option value="" {{ ($filters['enturmacao'] ?? null) === null ? 'selected' : '' }}>Todas</option>
                            <option value="enturmados" {{ ($filters['enturmacao'] ?? null) === 'enturmados' ? 'selected' : '' }}>Enturmados</option>
                            <option value="nao_enturmados" {{ ($filters['enturmacao'] ?? null) === 'nao_enturmados' ? 'selected' : '' }}>Não enturmados</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Modalidade</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="modalidade" id="modalidade" class="geral" style="width: 250px;">
                            <option value="">Todas</option>
                            <option value="1" {{ ($filters['modalidade'] ?? null) == 1 ? 'selected' : '' }}>Ensino regular</option>
                            <option value="2" {{ ($filters['modalidade'] ?? null) == 2 ? 'selected' : '' }}>Educação especial</option>
                            <option value="3" {{ ($filters['modalidade'] ?? null) == 3 ? 'selected' : '' }}>Educação de Jovens e Adultos (EJA)</option>
                            <option value="4" {{ ($filters['modalidade'] ?? null) == 4 ? 'selected' : '' }}>Educação profissional</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Dependência</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="dependencia" id="dependencia" class="geral" style="width: 200px;">
                            <option value="" {{ ($filters['dependencia'] ?? null) === null ? 'selected' : '' }}>Todas</option>
                            <option value="sim" {{ ($filters['dependencia'] ?? null) === 'sim' ? 'selected' : '' }}>Com dependência</option>
                            <option value="nao" {{ ($filters['dependencia'] ?? null) === 'nao' ? 'selected' : '' }}>Sem dependência</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" valign="top"><span class="form">Turno</span></td>
                    <td class="formmdtd" valign="top">
                        <select name="turno" id="turno" class="geral" style="width: 200px;">
                            <option value="">Todos</option>
                            @foreach($turnos ?? [] as $t)
                                <option value="{{ $t->id }}" {{ ($filters['turno'] ?? null) == $t->id ? 'selected' : '' }}>{{ $t->nome }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="separator"></div>
            <div style="text-align: center">
                <button class="btn-green" type="submit"><i class="fa fa-search"></i> Filtrar</button>
                <a href="{{ route('bis.matriculas.index') }}" class="btn btn-default" style="margin-left: 8px;"><i class="fa fa-eraser"></i> Limpar filtros</a>
            </div>
        </form>

        {{-- Relatórios --}}
        @foreach($reports ?? [] as $reportKey => $report)
            <div class="separator bi-no-print"></div>
            <div class="bi-section" data-section="{{ $reportKey }}">
                <table class="table-default" width="100%">
                    <tr>
                        <td class="titulo-tabela-listagem" style="text-align: left;">{{ $report['title'] ?? $reportKey }}</td>
                    </tr>
                <tr>
                    <td style="padding: 15px;">
                        @if(!empty($report['chart']))
                            <div style="min-height: 300px;">
                                {!! $report['chart']->container() !!}
                            </div>
                        @else
                            <p style="margin: 0; color: #666;">Nenhum dado disponível para este relatório com os filtros aplicados.</p>
                        @endif
                    </td>
                </tr>
                </table>
            </div>
        @endforeach

        @foreach($reports ?? [] as $report)
            @if(!empty($report['chart']))
                {!! $report['chart']->script() !!}
            @endif
        @endforeach
    </x-bi-print-wrapper>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js" crossorigin="anonymous"></script>
<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var inst = document.getElementById('ref_cod_instituicao');
        var escola = document.getElementById('ref_cod_escola');
        var curso = document.getElementById('ref_cod_curso');
        if (inst && inst.tagName === 'SELECT') inst.addEventListener('change', function() { if (escola) escola.value = ''; if (curso) curso.value = ''; });
        if (escola) escola.addEventListener('change', function() { if (curso) curso.value = ''; });
    });
})();
</script>
@endpush
@endsection
