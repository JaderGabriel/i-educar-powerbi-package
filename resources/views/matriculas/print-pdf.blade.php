<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 15mm; size: A4 landscape; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 0; }
        .header { display: table; width: 100%; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #ddd; }
        .section-title { font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        .chart-container { text-align: center; margin: 20px 0; }
        .chart-container img { max-width: 100%; height: auto; }
        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        @include('components.bi-print-header', ['title' => $sectionTitle, 'logoBase64' => $logoBase64 ?? null])
    </div>

    <div class="section-title">{{ $sectionTitle }}</div>

    <div class="chart-container">
        @if(!empty($chartImage))
            <img src="{{ $chartImage }}" alt="Gráfico" style="max-width: 100%;">
        @else
            <p>Nenhum gráfico disponível para impressão.</p>
        @endif
    </div>

    <div class="footer">
        @include('components.bi-print-footer')
    </div>
</body>
</html>
