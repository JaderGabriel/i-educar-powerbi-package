# Análise do Composer e Testes - Pacote BIS

## Alterações no composer.json

### Correções aplicadas

1. **Remoção de require-dev duplicado**  
   Havia dois blocos `require-dev` com versões diferentes de `laravel/pint` (^1.16 vs ^1.13).

2. **Unificação do autoload de testes**  
   - Removido `iEducar\Packages\Bis\Tests\` do `autoload` (produção)  
   - Mantido apenas em `autoload-dev`, que é o padrão em pacotes.

3. **Inclusão do pestphp/pest-plugin** em `allow-plugins`.

4. **Scripts de teste**  
   - `test`: executa PHPUnit via config do pacote  
   - `test:phpunit`: usa o PHPUnit do projeto raiz (via plug-and-play)

### Estrutura final do composer.json

```json
{
  "autoload": {
    "psr-4": { "iEducar\\Packages\\Bis\\": "src/" }
  },
  "autoload-dev": {
    "psr-4": { "iEducar\\Packages\\Bis\\Tests\\": "tests/" }
  }
}
```

---

## Estrutura de testes (padrão packages)

Baseada no pacote `portabilis/i-educar-educacenso-package`:

```
packages/serventec/i-educar-bis-package/
├── phpunit.package.xml    # Config PHPUnit para rodar do pacote
├── tests/
│   ├── Pest.php           # Config Pest (usa Tests\TestCase do projeto raiz)
│   ├── BisTestCase.php    # Base para testes que precisam do BisProvider
│   ├── Unit/
│   │   ├── BisProcessTest.php
│   │   └── BiDashboardServiceTest.php
│   └── Feature/
│       └── DashboardControllerTest.php
```

### Tipos de teste

| Arquivo | Tipo | Dependência | Descrição |
|---------|------|-------------|-----------|
| BisProcessTest | Unit | Nenhuma | Testa constantes e métodos estáticos |
| BiDashboardServiceTest | Unit | PostgreSQL | Testa serviço que consulta o banco |
| DashboardControllerTest | Feature | Nenhuma | Verifica se a rota está registrada |

### Execução

**Do projeto raiz:**

```bash
# Apenas suite BIS
php vendor/bin/phpunit --testsuite=BIS

# Testes que não exigem banco (BisProcess, rotas)
php vendor/bin/phpunit packages/serventec/i-educar-bis-package/tests/Unit/BisProcessTest.php
php vendor/bin/phpunit packages/serventec/i-educar-bis-package/tests/Feature/DashboardControllerTest.php
```

**Do diretório do pacote:**

```bash
cd packages/serventec/i-educar-bis-package
composer test
# ou
php ../../../vendor/bin/phpunit -c phpunit.package.xml
```

### Autoload de testes no projeto raiz

Em `composer.json` do i-Educar foi adicionado:

```json
"autoload-dev": {
  "psr-4": {
    "iEducar\\Packages\\Bis\\Tests\\": "packages/serventec/i-educar-bis-package/tests/"
  }
}
```

Assim o autoload encontra as classes de teste ao rodar a suite BIS pelo projeto principal.

### phpunit.xml do projeto raiz

Suite BIS incluída:

```xml
<testsuite name="BIS">
  <directory suffix="Test.php">./packages/serventec/i-educar-bis-package/tests</directory>
</testsuite>
```

---

## Testes que exigem banco de dados

`BiDashboardServiceTest` e testes que usam `BisTestCase` com `RefreshDatabase` precisam de PostgreSQL disponível. Sem banco, esses testes falham com `Connection refused`.

Para rodar em CI ou local sem banco, use apenas:

- `BisProcessTest` (PHPUnit puro)
- `DashboardControllerTest` (verificação de rotas, sem conexão com banco)
