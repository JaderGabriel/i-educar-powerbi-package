@props([
    'title' => 'BI',
])

<div class="bi-print-wrapper" data-bi-print>
    {{-- Cabeçalho de impressão (visível apenas ao imprimir) --}}
    <div class="bi-print-header bi-print-only">
        @include('bis::components.bi-print-header', ['title' => $title])
    </div>

    {{-- Conteúdo (gráficos, tabelas) --}}
    <div class="bi-print-content">
        {{ $slot }}
    </div>

    {{-- Rodapé de impressão (visível apenas ao imprimir) --}}
    <div class="bi-print-footer bi-print-only">
        @include('bis::components.bi-print-footer')
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bi-print.css') }}">
@endpush

