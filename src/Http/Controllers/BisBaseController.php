<?php

namespace iEducar\Packages\Bis\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Process;

abstract class BisBaseController extends Controller
{
    public function __construct()
    {
        $this->menu(Process::MENU_BI);
    }
}
