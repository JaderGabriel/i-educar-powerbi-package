# i-Educar BIS (Business Intelligence)

Módulo de Business Intelligence para geração de dashboards e relatórios analíticos no [i-Educar](https://github.com/portabilis/i-educar).

## Repositórios

Código do pacote (Power BI / BIS):

- **SSH:** `git@github.com:serventecieducar/i-educar-powerbi-package.git`
- **HTTPS:** https://github.com/serventecieducar/i-educar-powerbi-package

Projeto i-Educar (onde o pacote é utilizado):

- **HTTPS:** https://github.com/portabilis/i-educar

**Compatibilidade:** este pacote é compatível com i-Educar 2.10 e 2.11. A tag `v1.2.10` indica essa versão.

## Dependências

- [Chart.js](https://www.chartjs.org/) via `consoletvs/charts` para gráficos
- PHP >= 8.3
- Laravel 11.x (via projeto i-Educar)

## Estrutura do pacote

```
packages/serventec/i-educar-bis-package/
├── config/
│   └── bis.php              # Configuração (chart_library, cache, etc.)
├── database/migrations/      # Menu BI e permissões
├── resources/
│   ├── assets/css/
│   │   └── bi-print.css      # Estilos para impressão
│   └── views/                # Views Blade (dashboard, matrículas, turmas, etc.)
├── routes/web.php            # Rotas em /bis/*
├── src/
│   ├── BisProcess.php        # Constantes e helpers do BI
│   ├── Http/Controllers/     # Controllers
│   ├── Providers/BisProvider.php
│   └── Services/             # BiDashboardService, BiChartsService, etc.
├── tests/                    # Testes unitários e de feature
└── docs/                     # Documentação técnica
```

## Instalação

### Via Plug and Play (recomendado para desenvolvimento)

O i-Educar utiliza [dex/composer-plug-and-play](https://github.com/edersoares/composer-plug-and-play). A partir da raiz do i-Educar:

1. Clone este repositório (SSH ou HTTPS):

```bash
# SSH
git clone git@github.com:serventecieducar/i-educar-powerbi-package.git packages/serventec/i-educar-bis-package

# HTTPS
git clone https://github.com/serventecieducar/i-educar-powerbi-package.git packages/serventec/i-educar-bis-package
```

2. Adicione e resolva dependências:

```bash
# (Docker)
docker compose exec php composer plug-and-play:add serventec/i-educar-powerbi-package @dev
docker compose exec php composer plug-and-play

# (Local)
composer plug-and-play:add serventec/i-educar-powerbi-package @dev
composer plug-and-play
```

### Via Composer (instalação autônoma)

Adicione ao `composer.json` do i-Educar:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/serventecieducar/i-educar-powerbi-package.git"
    }
  ],
  "require": {
    "serventec/i-educar-powerbi-package": "^1.0"
  }
}
```

Execute `composer update serventec/i-educar-powerbi-package`.

### Pós-instalação

Execute as migrations (menu BI e permissões):

```bash
# (Docker)
docker compose exec php php artisan migrate

# (Local)
php artisan migrate
```

Publique os assets (CSS do BI: impressão e dashboard):

```bash
# (Docker) primeira execução
docker compose exec php php artisan vendor:publish --tag=bis-assets

# (Docker) após atualizar o pacote (sobrescrever arquivos)
docker compose exec php php artisan vendor:publish --tag=bis-assets --force

# (Local) primeira execução
php artisan vendor:publish --tag=bis-assets

# (Local) após atualizar o pacote
php artisan vendor:publish --tag=bis-assets --force
```

Limpe caches para refletir mudanças de rotas, menus e views:

```bash
# (Docker)
docker compose exec php php artisan cache:clear
docker compose exec php php artisan view:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan config:clear

# (Local)
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

**Observação:** se o i-Educar possuir as constantes `Process::BI_*` e `Process::MENU_BI` em `App\Process`, o pacote usará esses mesmos IDs. Caso contrário, o `BisProcess` aplica valores próprios e a migration continua funcionando em instalações novas.

## Estrutura de menus

O módulo adiciona o menu **BI** em **Escola**, com submenus por tema:

- **Matrículas** – Dashboard de matrículas por curso, escola, situação e mais
- **Turmas** – Distribuição de turmas por escola, curso e turno
- **Lançamentos** – Notas e faltas por etapa
- **Indicadores** – Evasão, aprovação, reprovação, reclassificação, abandono
- **Inclusão e Diversidade** – Matrículas por deficiência, cor/raça, gênero e AEE
- **Busca Ativa** – Casos de evasão e resultado do programa
- **Educacenso/INEP** – Cobertura INEP e registros do Censo Escolar

As permissões por submenu podem ser ajustadas em **Configurações > Permissões > Tipos de usuário**.

## Configuração

Arquivo `config/bis.php` (ou variáveis de ambiente):

| Variável              | Descrição                                   | Padrão   |
|-----------------------|---------------------------------------------|----------|
| `BIS_CHART_LIBRARY`   | Biblioteca de gráficos (chartjs, highcharts, etc.) | chartjs |
| `BIS_CACHE_TTL`       | Tempo de cache em segundos (0 = desabilitado)    | 300      |
| `BIS_POWERED_BY_AUTHOR` | Crédito de autoria exibido nos dashboards   | JaderGabriel |

## Testes

**Do projeto raiz (i-Educar):**

```bash
# Suite BIS completa
php vendor/bin/phpunit --testsuite=BIS

# Testes que não exigem banco
php vendor/bin/phpunit packages/serventec/i-educar-bis-package/tests/Unit/BisProcessTest.php
php vendor/bin/phpunit packages/serventec/i-educar-bis-package/tests/Feature/DashboardControllerTest.php
```

**Do diretório do pacote:**

```bash
cd packages/serventec/i-educar-bis-package
composer test
```

Os testes `BiDashboardServiceTest` e similares exigem PostgreSQL. Consulte `docs/ANALISE_COMPOSER_E_TESTES.md` para detalhes.

## Fluxo de trabalho

Todo commit, push e criação de branch de melhorias devem ocorrer em `packages/serventec/i-educar-bis-package`, mantendo o repositório do BI separado do i-Educar principal.

## Impressão

Os dashboards e gráficos suportam impressão com cabeçalho e rodapé padrões (configuráveis em Configurações gerais).

## Capturas de tela

- Dashboard BI: `docs/screenshots/bis-dashboard.png`
- Matrículas BI: `docs/screenshots/bis-matriculas.png`
- Turmas BI: `docs/screenshots/bis-turmas.png`


## FAQ

Perguntas frequentes: [FAQ i-Educar](https://github.com/portabilis/i-educar-website/blob/master/docs/faq.md).

---

Powered by [Serventec](https://serventecassessoria.com.br).
Powered by [JaderGabriel](https://t.me/JaderGabriel).
