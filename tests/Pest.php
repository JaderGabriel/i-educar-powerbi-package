<?php

/*
|--------------------------------------------------------------------------
| Pest configuração - Pacote BIS
|--------------------------------------------------------------------------
|
| Os testes do pacote BIS rodam no contexto do i-Educar (bootstrap via root).
| Use Tests\TestCase para testes que precisam da aplicação Laravel.
|
*/

uses(Tests\TestCase::class)->in('Unit');
uses(Tests\TestCase::class)->in('Feature');
