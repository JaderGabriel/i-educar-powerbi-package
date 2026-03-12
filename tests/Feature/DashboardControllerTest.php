<?php

namespace iEducar\Packages\Bis\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_rota_bis_dashboard_esta_registrada(): void
    {
        $this->assertTrue(
            Route::has('bis.dashboard'),
            'Rota bis.dashboard deve estar registrada'
        );
    }
}
