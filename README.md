# i-Educar BI (Power BI)

Módulo de Business Intelligence para geração de dashboards e relatórios analíticos no [i-Educar](https://github.com/portabilis/i-educar).

## Repositório

- **URL:** https://github.com/serventecieducar/i-educar-powerbi-package.git

## Dependências

- [Chart.js](https://www.chartjs.org/) (via consoletvs/charts) para gráficos
- [Maatwebsite Excel](https://laravel-excel.com/) para exportação em planilhas

## Instalação

### Via Plug and Play (recomendado para desenvolvimento)

O i-Educar utiliza [dex/composer-plug-and-play](https://github.com/edersoares/composer-plug-and-play). A partir da raiz do i-Educar:

1. Clone este repositório:

```bash
git clone https://github.com/serventecieducar/i-educar-powerbi-package.git packages/serventec/i-educar-bis-package
```

2. Adicione e resolva dependências:

```bash
# (Docker) docker compose exec php composer plug-and-play:add serventec/i-educar-bis-package @dev
# (Docker) docker compose exec php composer plug-and-play
composer plug-and-play:add serventec/i-educar-bis-package @dev
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
    "serventec/i-educar-bis-package": "^1.0"
  }
}
```

Execute `composer update serventec/i-educar-bis-package`.

Execute as migrations (menu BI e permissões):

```bash
# (Docker) docker compose exec php php artisan migrate
php artisan migrate
```

Publique os assets (CSS de impressão):

```bash
# (Docker) docker compose exec php php artisan vendor:publish --tag=bis-assets
php artisan vendor:publish --tag=bis-assets
```

**Requisito:** O i-Educar deve possuir as constantes `Process::BI_*` e `Process::MENU_BI` em `App\Process`. Versões recentes já incluem. Caso necessário, adicione manualmente.

Se você estiver atualizando o i-Educar aconselhamos que execute o seguinte comando:

```bash
# (Docker) docker compose exec php php artisan cache:clear
php artisan cache:clear
```

Isso é necessário para que as mudanças de URL sejam refletidas no cache dos menus do usuário.

## Estrutura de menus

O módulo adiciona o menu **BI** em **Escola**, com submenus por tema:

- **Matrículas** – Dashboard de matrículas por curso, escola, situação e mais
- **Turmas** – Distribuição de turmas por escola, curso e turno
- **Lançamentos** – Notas e faltas por etapa
- **Indicadores** – Evasão, aprovação, reprovação, reclassificação, abandono e indicadores educacionais
- **Inclusão e Diversidade** – Matrículas por deficiência, cor/raça, gênero e AEE
- **Busca Ativa** – Casos de evasão, resultado e programa de evasão
- **Educacenso/INEP** – Cobertura INEP e registros do Censo Escolar

As permissões por submenu podem ser ajustadas em **Configurações > Permissões > Tipos de usuário**.

## Fluxo de trabalho

Todo commit, push e criação de branch de melhorias deverão ocorrer dentro da pasta
`packages/serventec/i-educar-bis-package`, dessa forma você estará manipulando o
repositório do BI e não o repositório principal do i-Educar.

## Impressão

Os dashboards e gráficos suportam impressão com cabeçalho e rodapé padrões do sistema (configuráveis em Configurações gerais).

## Perguntas frequentes (FAQ)

Algumas perguntas aparecem recorrentemente. Olhe primeiro por aqui:
[FAQ](https://github.com/portabilis/i-educar-website/blob/master/docs/faq.md).

---

Powered by [Serventec](https://serventec.com.br/).
