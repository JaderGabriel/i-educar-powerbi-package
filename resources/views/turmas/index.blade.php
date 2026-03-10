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
    <x-bi-print-wrapper :title="$title" :export-url="null" :show-top-actions="false">
        <div class="bi-powered-wrapper" style="margin-bottom: 16px;">
            <x-bi-powered />
        </div>
        <form id="formcadastro" method="get" action="{{ route('bis.turmas.index') }}" class="bi-no-print">
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
                <a href="{{ route('bis.turmas.index') }}" class="btn btn-default" style="margin-left: 8px;"><i class="fa fa-eraser"></i> Limpar filtros</a>
            </div>
        </form>

        {{-- Relatórios --}}
        @foreach($reports ?? [] as $reportKey => $report)
            @php
                $exportParams = [
                    'ano' => ($filters['ano'] ?? null) ?? 'todos',
                    'ref_cod_instituicao' => $filters['instituicao'] ?? '',
                    'ref_cod_escola' => $filters['escola'] ?? '',
                    'ref_cod_curso' => $filters['curso'] ?? '',
                    'modalidade' => $filters['modalidade'] ?? '',
                    'turno' => $filters['turno'] ?? '',
                ];
                $exportUrl = route('bis.turmas.export', ['report' => $reportKey]) . '?' . http_build_query(array_filter($exportParams, fn($v) => $v !== null && $v !== ''));
            @endphp
            <div class="separator bi-no-print"></div>
            <div class="bi-section" data-section="{{ $reportKey }}" data-section-title="{{ $report['title'] ?? $reportKey }}">
                <table class="table-default" width="100%">
                    <tr>
                        <td class="titulo-tabela-listagem bi-print-section-title" style="text-align: left;">{{ $report['title'] ?? $reportKey }}</td>
                        <td class="titulo-tabela-listagem bi-no-print" style="text-align: right; width: 220px;">
                            <button type="button" class="btn btn-green bi-print-section-btn" style="margin: 0 4px 0 0; padding: 6px 12px; font-size: 12px;" data-section="{{ $reportKey }}" data-section-title="{{ $report['title'] ?? $reportKey }}" title="Imprimir este gráfico (paisagem, com cabeçalho e legenda)">
                                <i class="fa fa-print"></i> Imprimir
                            </button>
                            <a href="{{ $exportUrl }}" class="btn btn-green" style="margin: 0; padding: 6px 12px; font-size: 12px;" title="Exportar para Excel">
                                <i class="fa fa-file-excel-o"></i> Exportar Excel
                            </a>
                        </td>
                    </tr>
                <tr>
                    <td colspan="2" style="padding: 15px;">
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
    var PRINT_PDF_URL = '{{ route('bis.turmas.print-pdf') }}';
    var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.addEventListener('DOMContentLoaded', function() {
        BiTurmas.init();
    });

    var BiTurmas = {
        init: function() {
            this.initFilterCascade();
            this.initPrintButtons();
        },
        initFilterCascade: function() {
            var inst = document.getElementById('ref_cod_instituicao');
            var escola = document.getElementById('ref_cod_escola');
            var curso = document.getElementById('ref_cod_curso');
            if (inst && inst.tagName === 'SELECT') inst.addEventListener('change', function() { if (escola) escola.value = ''; if (curso) curso.value = ''; });
            if (escola) escola.addEventListener('change', function() { if (curso) curso.value = ''; });
        },
        initPrintButtons: function() {
            var self = this;
            document.querySelectorAll('.bi-print-section-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.printSection.call(self, btn.getAttribute('data-section'), btn.getAttribute('data-section-title'));
                });
            });
        },
        printSection: function(sectionId, sectionTitle) {
            var section = document.querySelector('.bi-section[data-section="' + sectionId + '"]');
            if (!section) return;

            var canvas = section.querySelector('canvas');
            var chartImage = canvas && canvas.toDataURL ? canvas.toDataURL('image/png') : '';

            var formData = new FormData();
            formData.append('sectionTitle', sectionTitle || 'Relatório');
            formData.append('chartImage', chartImage);
            formData.append('_token', CSRF_TOKEN);

            fetch(PRINT_PDF_URL, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Erro ao gerar PDF');
                return res.blob();
            })
            .then(function(blob) {
                var url = URL.createObjectURL(blob);
                window.open(url, '_blank', 'noopener');
                setTimeout(function() { URL.revokeObjectURL(url); }, 10000);
            })
            .catch(function(err) {
                alert('Não foi possível gerar o PDF. Tente novamente.');
                console.error(err);
            });
        }
    };
    window.BiTurmas = BiTurmas;
})();
</script>
@endpush
@endsection
