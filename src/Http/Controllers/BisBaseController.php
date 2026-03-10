<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use App\Http\Controllers\Controller;
use iEducar\Packages\Bis\BisProcess;

abstract class BisBaseController extends Controller
{
    public function __construct()
    {
        $this->menu(BisProcess::menuBi());
    }
}
