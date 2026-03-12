<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use App\Http\Controllers\Controller;
use iEducar\Packages\Bis\BisProcess;
use Illuminate\Support\Facades\Auth;

abstract class BisBaseController extends Controller
{
    public function __construct()
    {
        // Em contexto de console (ex.: php artisan route:list) não há usuário autenticado.
        if (app()->runningInConsole()) {
            return;
        }

        if (! Auth::check()) {
            return;
        }

        $this->menu(BisProcess::menuBi());
    }
}
