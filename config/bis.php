<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crédito de Autoria
    |--------------------------------------------------------------------------
    |
    | Exibido nos módulos do BI (Dashboard, Matrículas, Turmas, etc.).
    |
    */
    'powered_by' => [
        'author' => env('BIS_POWERED_BY_AUTHOR', 'JaderGabriel'),
        'url' => env('BIS_POWERED_BY_URL', 'https://github.com/jadergabriel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Biblioteca de Gráficos
    |--------------------------------------------------------------------------
    |
    | Biblioteca JavaScript utilizada para renderização dos gráficos.
    | Suportado: chartjs, highcharts, apexcharts, chartist, echart, c3
    |
    */
    'chart_library' => env('BIS_CHART_LIBRARY', 'chartjs'),

    /*
    |--------------------------------------------------------------------------
    | Exportação
    |--------------------------------------------------------------------------
    |
    | Configurações para exportação de relatórios em Excel/CSV.
    |
    */
    'export' => [
        'excel' => true,
        'csv' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Tempo de cache (em segundos) para dados agregados dos dashboards.
    | 0 para desabilitar cache.
    |
    */
    'cache_ttl' => env('BIS_CACHE_TTL', 300),

];
