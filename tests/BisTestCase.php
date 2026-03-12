<?php

namespace iEducar\Packages\Bis\Tests;

use iEducar\Packages\Bis\Providers\BisProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TestCase base para testes do pacote BIS.
 * Segue o padrão dos pacotes (ex.: EducacensoTestCase).
 */
abstract class BisTestCase extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            BisProvider::class,
        ];
    }
}
